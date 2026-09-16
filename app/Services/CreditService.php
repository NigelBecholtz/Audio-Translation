<?php

namespace App\Services;

use App\Constants\CreditConstants;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only place that changes a user's credit balance. Every change locks the user row and is recorded.
 */
class CreditService
{
    /**
     * Charge a translation/conversion: free translations are used before paid credits.
     *
     * @throws InsufficientCreditsException
     */
    public function deductCredit(User $user, string $description = 'Credits used', ?float $amount = null): void
    {
        $amount ??= (float) config('stripe.default_cost_per_translation');

        DB::transaction(function () use ($user, $description, $amount) {
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->hasUnlimitedCredits()) {
                return;
            }

            if ($lockedUser->translations_used < $lockedUser->translations_limit) {
                $lockedUser->increment('translations_used');

                return;
            }

            $this->changeBalance($lockedUser, -$amount, CreditConstants::TRANSACTION_TYPE_USAGE, $description);
        });
    }

    public function addCredit(User $user, float $amount, string $type, string $description, ?int $adminId = null): void
    {
        DB::transaction(function () use ($user, $amount, $type, $description, $adminId) {
            $this->changeBalance(User::lockForUpdate()->findOrFail($user->id), $amount, $type, $description, $adminId);
        });
    }

    /**
     * @throws InsufficientCreditsException
     */
    public function removeCredit(User $user, float $amount, string $type, string $description, ?int $adminId = null): void
    {
        DB::transaction(function () use ($user, $amount, $type, $description, $adminId) {
            $this->changeBalance(User::lockForUpdate()->findOrFail($user->id), -$amount, $type, $description, $adminId);
        });
    }

    /**
     * Complete a pending Stripe payment and credit its owner. Safe to call from both the webhook and
     * the success page: only the first call credits, later calls return false.
     */
    public function completePurchase(Payment $payment, ?string $paymentIntentId): bool
    {
        return DB::transaction(function () use ($payment, $paymentIntentId) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status !== CreditConstants::PAYMENT_STATUS_PENDING) {
                return false;
            }

            $lockedPayment->update([
                'status' => CreditConstants::PAYMENT_STATUS_COMPLETED,
                'stripe_payment_intent_id' => $paymentIntentId,
                'completed_at' => now(),
            ]);

            $this->changeBalance(
                User::lockForUpdate()->findOrFail($lockedPayment->user_id),
                (float) $lockedPayment->credits_purchased,
                CreditConstants::TRANSACTION_TYPE_PURCHASE,
                "Credits purchased via Stripe (€{$lockedPayment->amount})"
            );

            return true;
        });
    }

    /**
     * Mark a payment as refunded and take its credits back when the user still has them. Idempotent.
     */
    public function refundPurchase(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === CreditConstants::PAYMENT_STATUS_REFUNDED) {
                return;
            }

            $lockedPayment->update(['status' => CreditConstants::PAYMENT_STATUS_REFUNDED]);

            $user = User::lockForUpdate()->findOrFail($lockedPayment->user_id);
            $credits = (float) $lockedPayment->credits_purchased;

            if ((float) $user->credits < $credits) {
                Log::warning('User has insufficient credits for refund', [
                    'user_id' => $user->id,
                    'user_credits' => $user->credits,
                    'refund_amount' => $credits,
                    'payment_id' => $lockedPayment->id,
                ]);

                return;
            }

            $this->changeBalance($user, -$credits, CreditConstants::TRANSACTION_TYPE_REFUND, "Refund for payment (€{$lockedPayment->amount})");
        });
    }

    /**
     * Apply a balance change to an already locked user and record it.
     *
     * @throws InsufficientCreditsException
     */
    private function changeBalance(User $lockedUser, float $amount, string $type, string $description, ?int $adminId = null): void
    {
        if ($amount < 0 && (float) $lockedUser->credits < abs($amount)) {
            throw new InsufficientCreditsException("Insufficient credits. Current balance: {$lockedUser->credits} credits.");
        }

        if ($amount >= 0) {
            $lockedUser->increment('credits', $amount);
        } else {
            $lockedUser->decrement('credits', abs($amount));
        }

        CreditTransaction::create([
            'user_id' => $lockedUser->id,
            'admin_id' => $adminId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'balance_after' => $lockedUser->fresh()->credits,
        ]);
    }
}

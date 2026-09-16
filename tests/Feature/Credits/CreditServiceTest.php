<?php

use App\Constants\CreditConstants;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pendingPaymentFor(User $user, int $credits = 10): Payment
{
    return Payment::create([
        'user_id' => $user->id,
        'stripe_session_id' => 'cs_test_'.uniqid(),
        'amount' => 5.00,
        'credits_purchased' => $credits,
        'status' => CreditConstants::PAYMENT_STATUS_PENDING,
        'currency' => 'eur',
    ]);
}

it('adds credits and records the new balance', function () {
    $user = User::factory()->create(['credits' => 2]);

    app(CreditService::class)->addCredit($user, 3, CreditConstants::TRANSACTION_TYPE_ADMIN_ADD, 'Bonus');

    expect((float) $user->fresh()->credits)->toBe(5.0);
    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 3,
        'type' => CreditConstants::TRANSACTION_TYPE_ADMIN_ADD,
        'balance_after' => 5,
    ]);
});

it('removes credits and records a negative transaction', function () {
    $user = User::factory()->create(['credits' => 5]);

    app(CreditService::class)->removeCredit($user, 2, CreditConstants::TRANSACTION_TYPE_ADMIN_REMOVE, 'Correction');

    expect((float) $user->fresh()->credits)->toBe(3.0);
    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => -2,
        'type' => CreditConstants::TRANSACTION_TYPE_ADMIN_REMOVE,
        'balance_after' => 3,
    ]);
});

it('refuses to remove more credits than the user has', function () {
    $user = User::factory()->create(['credits' => 1]);

    expect(fn () => app(CreditService::class)->removeCredit($user, 2, CreditConstants::TRANSACTION_TYPE_ADMIN_REMOVE, 'Too much'))
        ->toThrow(InsufficientCreditsException::class);

    expect((float) $user->fresh()->credits)->toBe(1.0)
        ->and(CreditTransaction::count())->toBe(0);
});

it('credits a purchase only once when both the webhook and the success page complete it', function () {
    $user = User::factory()->create(['credits' => 0]);
    $payment = pendingPaymentFor($user, 10);
    $service = app(CreditService::class);

    expect($service->completePurchase($payment, 'pi_test_1'))->toBeTrue()
        ->and($service->completePurchase($payment, 'pi_test_1'))->toBeFalse();

    $payment->refresh();
    expect((float) $user->fresh()->credits)->toBe(10.0)
        ->and($payment->status)->toBe(CreditConstants::PAYMENT_STATUS_COMPLETED)
        ->and($payment->stripe_payment_intent_id)->toBe('pi_test_1')
        ->and($payment->completed_at)->not->toBeNull()
        ->and(CreditTransaction::where('type', CreditConstants::TRANSACTION_TYPE_PURCHASE)->count())->toBe(1);
});

it('refunds a completed purchase only once', function () {
    $user = User::factory()->create(['credits' => 0]);
    $payment = pendingPaymentFor($user, 10);
    $service = app(CreditService::class);
    $service->completePurchase($payment, 'pi_test_2');

    $service->refundPurchase($payment);
    $service->refundPurchase($payment);

    expect((float) $user->fresh()->credits)->toBe(0.0)
        ->and($payment->fresh()->status)->toBe(CreditConstants::PAYMENT_STATUS_REFUNDED)
        ->and(CreditTransaction::where('type', CreditConstants::TRANSACTION_TYPE_REFUND)->count())->toBe(1);
});

it('marks a refund without removing credits the user already spent', function () {
    $user = User::factory()->create(['credits' => 0]);
    $payment = pendingPaymentFor($user, 10);
    $service = app(CreditService::class);
    $service->completePurchase($payment, 'pi_test_3');
    $user->forceFill(['credits' => 4])->save();

    $service->refundPurchase($payment);

    expect((float) $user->fresh()->credits)->toBe(4.0)
        ->and($payment->fresh()->status)->toBe(CreditConstants::PAYMENT_STATUS_REFUNDED)
        ->and(CreditTransaction::where('type', CreditConstants::TRANSACTION_TYPE_REFUND)->count())->toBe(0);
});

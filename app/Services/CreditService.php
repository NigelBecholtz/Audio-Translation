<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Deduct credit for a translation/conversion
     *
     * @param User $user
     * @param string $description
     * @param float $amount
     * @return void
     * @throws \Exception if user has insufficient credits
     */
    public function deductCredit(User $user, string $description = 'Credits used', float $amount = null): void
    {
        // Use default cost if not specified
        $amount = $amount ?? config('stripe.default_cost_per_translation');
        
        // Use free translations first
        if ($user->translations_used < $user->translations_limit) {
            // Use database transaction with locking for free translations
            DB::transaction(function() use ($user) {
                $lockedUser = User::lockForUpdate()->find($user->id);
                
                // Double-check after lock
                if ($lockedUser->translations_used < $lockedUser->translations_limit) {
                    $lockedUser->increment('translations_used');
                }
            });
            return;
        }

        // Then use credits with database transaction and row-level locking
        DB::transaction(function() use ($user, $description, $amount) {
            // Lock the user row for update to prevent concurrent modifications
            $lockedUser = User::lockForUpdate()->find($user->id);
            
            // Verify user has sufficient credits after lock
            if ($lockedUser->credits < $amount) {
                throw new \Exception('Insufficient credits. Current balance: ' . $lockedUser->credits);
            }
            
            $lockedUser->decrement('credits', $amount);
            $newBalance = $lockedUser->fresh()->credits;
            
            CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'admin_id' => null,
                'amount' => -$amount,
                'type' => 'usage',
                'description' => $description,
                'balance_after' => $newBalance,
            ]);
        });
    }

    /**
     * Add credits to user (for purchases or admin actions)
     *
     * @param User $user
     * @param float $amount
     * @param string $type
     * @param string $description
     * @param int|null $adminId
     * @return void
     */
    public function addCredit(User $user, float $amount, string $type, string $description, ?int $adminId = null): void
    {
        DB::transaction(function() use ($user, $amount, $type, $description, $adminId) {
            // Lock the user row for update to prevent concurrent modifications
            $lockedUser = User::lockForUpdate()->find($user->id);
            
            $lockedUser->increment('credits', $amount);
            $newBalance = $lockedUser->fresh()->credits;
            
            CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'admin_id' => $adminId,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'balance_after' => $newBalance,
            ]);
        });
    }
}


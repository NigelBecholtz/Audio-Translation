<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Constants\CreditConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handleStripe(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));
        
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $event->type,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle successful checkout session
     */
    private function handleCheckoutCompleted($session)
    {
        Log::info('Checkout session completed', ['session_id' => $session->id]);

        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if (!$payment) {
            Log::warning('Payment record not found for session', ['session_id' => $session->id]);
            return;
        }

        // Only process if payment is still pending
        if ($payment->status !== CreditConstants::PAYMENT_STATUS_PENDING) {
            Log::info('Payment already processed', [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);
            return;
        }

        // Use database transaction with row-level locking to prevent race conditions
        \DB::transaction(function() use ($payment, $session) {
            // Lock the user row to prevent concurrent credit modifications
            $user = User::lockForUpdate()->find($payment->user_id);
            $credits = $payment->credits_purchased;
            
            // Update payment status
            $payment->update([
                'status' => CreditConstants::PAYMENT_STATUS_COMPLETED,
                'stripe_payment_intent_id' => $session->payment_intent,
                'completed_at' => now(),
            ]);

            // Add credits to user account
            $user->increment('credits', $credits);
            $newBalance = $user->fresh()->credits;

            // Create credit transaction
            CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => null,
                'amount' => $credits,
                'type' => CreditConstants::TRANSACTION_TYPE_PURCHASE,
                'description' => "Credits purchased via Stripe (€{$payment->amount})",
                'balance_after' => $newBalance,
            ]);

            Log::info('Credits added via webhook', [
                'user_id' => $user->id,
                'credits' => $credits,
                'payment_id' => $payment->id
            ]);
        });
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Payment intent succeeded', ['payment_intent_id' => $paymentIntent->id]);
        
        // Payment is already handled in checkout.session.completed
        // This is just for logging/monitoring
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent)
    {
        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_message' => $paymentIntent->last_payment_error->message ?? 'Unknown error'
        ]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update([
                'status' => CreditConstants::PAYMENT_STATUS_FAILED,
            ]);
        }
    }

    /**
     * Handle refund
     */
    private function handleRefund($charge)
    {
        Log::info('Charge refunded', ['charge_id' => $charge->id]);

        $payment = Payment::where('stripe_payment_intent_id', $charge->payment_intent)->first();

        if (!$payment) {
            Log::warning('Payment not found for refund', ['payment_intent' => $charge->payment_intent]);
            return;
        }

        if ($payment->status === CreditConstants::PAYMENT_STATUS_REFUNDED) {
            Log::info('Payment already refunded', ['payment_id' => $payment->id]);
            return;
        }

        // Use database transaction with row-level locking to prevent race conditions
        \DB::transaction(function() use ($payment) {
            // Lock the user row to prevent concurrent credit modifications
            $user = User::lockForUpdate()->find($payment->user_id);
            $credits = $payment->credits_purchased;
            
            // Update payment status
            $payment->update([
                'status' => CreditConstants::PAYMENT_STATUS_REFUNDED,
            ]);

            // Remove credits from user
            if ($user->credits >= $credits) {
                $user->decrement('credits', $credits);
                $newBalance = $user->fresh()->credits;

                // Create transaction record
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'admin_id' => null,
                    'amount' => -$credits,
                    'type' => CreditConstants::TRANSACTION_TYPE_REFUND,
                    'description' => "Refund for payment (€{$payment->amount})",
                    'balance_after' => $newBalance,
                ]);

                Log::info('Credits removed due to refund', [
                    'user_id' => $user->id,
                    'credits' => $credits,
                    'payment_id' => $payment->id
                ]);
            } else {
                Log::warning('User has insufficient credits for refund', [
                    'user_id' => $user->id,
                    'user_credits' => $user->credits,
                    'refund_amount' => $credits
                ]);
            }
        });
    }
}
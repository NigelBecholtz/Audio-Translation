<?php

namespace App\Http\Controllers;

use App\Constants\CreditConstants;
use App\Models\Payment;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(private CreditService $credits) {}

    /**
     * Handle Stripe webhook events
     */
    public function handleStripe(Request $request)
    {
        Stripe::setApiKey(config('stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::error('Stripe webhook secret not configured');

            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                    // Credits are added on checkout.session.completed; this event is only logged
                    Log::info('Payment intent succeeded', ['payment_intent_id' => $event->data->object->id]);
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
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function handleCheckoutCompleted($session): void
    {
        Log::info('Checkout session completed', ['session_id' => $session->id]);

        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if (! $payment) {
            Log::warning('Payment record not found for session', ['session_id' => $session->id]);

            return;
        }

        if ($this->credits->completePurchase($payment, $session->payment_intent)) {
            Log::info('Credits added via webhook', [
                'user_id' => $payment->user_id,
                'credits' => $payment->credits_purchased,
                'payment_id' => $payment->id,
            ]);
        } else {
            Log::info('Payment already processed', ['payment_id' => $payment->id]);
        }
    }

    private function handlePaymentFailed($paymentIntent): void
    {
        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent->id,
            'failure_message' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
        ]);

        Payment::where('stripe_payment_intent_id', $paymentIntent->id)
            ->where('status', CreditConstants::PAYMENT_STATUS_PENDING)
            ->update(['status' => CreditConstants::PAYMENT_STATUS_FAILED]);
    }

    private function handleRefund($charge): void
    {
        Log::info('Charge refunded', ['charge_id' => $charge->id]);

        $payment = Payment::where('stripe_payment_intent_id', $charge->payment_intent)->first();

        if (! $payment) {
            Log::warning('Payment not found for refund', ['payment_intent' => $charge->payment_intent]);

            return;
        }

        $this->credits->refundPurchase($payment);
    }
}

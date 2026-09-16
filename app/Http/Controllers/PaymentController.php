<?php

namespace App\Http\Controllers;

use App\Constants\CreditConstants;
use App\Models\Payment;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function __construct(private CreditService $credits)
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function showCredits()
    {
        $user = Auth::user();
        $creditPackage = config('stripe.credit_packages.starter');

        return view('payment.credits', compact('user', 'creditPackage'));
    }

    public function createCheckoutSession(Request $request)
    {
        $user = Auth::user();
        $creditPackage = config('stripe.credit_packages.starter');

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $creditPackage['name'],
                            'description' => $creditPackage['description'],
                        ],
                        'unit_amount' => $creditPackage['price'] * 100, // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'credits' => $creditPackage['credits'],
                ],
            ]);

            Payment::create([
                'user_id' => $user->id,
                'stripe_session_id' => $session->id,
                'amount' => $creditPackage['price'],
                'credits_purchased' => $creditPackage['credits'],
                'status' => CreditConstants::PAYMENT_STATUS_PENDING,
                'currency' => 'eur',
                'stripe_metadata' => $session->metadata->toArray(),
            ]);

            return redirect($session->url);

        } catch (ApiErrorException $e) {
            return back()->with('error', 'An error occurred while creating the payment: '.$e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()->route('audio.index')->with('error', 'No session ID found.');
        }

        try {
            $session = Session::retrieve($sessionId);

            $payment = Payment::where('stripe_session_id', $sessionId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $payment) {
                return redirect()->route('audio.index')->with('error', 'Payment not found.');
            }

            if ($session->payment_status === 'paid') {
                // The webhook may already have credited this payment; completePurchase only credits once
                $this->credits->completePurchase($payment, $session->payment_intent);

                return redirect()->route('audio.index')->with('success',
                    "Payment successful! You have received {$payment->credits_purchased} credits."
                );
            }

            if ($payment->isPending()) {
                $payment->update(['status' => CreditConstants::PAYMENT_STATUS_FAILED]);
            }

            return redirect()->route('audio.index')->with('error', 'Payment not completed.');

        } catch (ApiErrorException $e) {
            return redirect()->route('audio.index')->with('error',
                'An error occurred while processing the payment.'
            );
        }
    }

    public function cancel()
    {
        return redirect()->route('payment.credits')->with('error', 'Payment cancelled.');
    }
}

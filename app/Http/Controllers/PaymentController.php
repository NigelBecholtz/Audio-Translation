<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\CreditTransaction;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    public function __construct()
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
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'credits' => $creditPackage['credits'],
                ],
            ]);

            // Create payment record
            Payment::create([
                'user_id' => $user->id,
                'stripe_session_id' => $session->id,
                'amount' => $creditPackage['price'],
                'credits_purchased' => $creditPackage['credits'],
                'status' => 'pending',
                'currency' => 'eur',
                'stripe_metadata' => $session->metadata->toArray(),
            ]);

            return redirect($session->url);
            
        } catch (ApiErrorException $e) {
            return back()->with('error', 'An error occurred while creating the payment: ' . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return redirect()->route('audio.index')->with('error', 'No session ID found.');
        }

        try {
            $session = Session::retrieve($sessionId);
            
            // Find the payment record
            $payment = Payment::where('stripe_session_id', $sessionId)->first();
            
            if (!$payment) {
                return redirect()->route('audio.index')->with('error', 'Payment not found.');
            }
            
            if ($session->payment_status === 'paid') {
                $credits = $session->metadata->credits;
                
                // Use database transaction with row-level locking to prevent race conditions
                \DB::transaction(function() use ($payment, $session, $credits) {
                    // Lock the user row to prevent concurrent credit modifications
                    $user = \App\Models\User::lockForUpdate()->find(Auth::id());
                    
                    // Update payment status
                    $payment->update([
                        'status' => 'completed',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'completed_at' => now(),
                    ]);
                    
                    // Add credits to user account
                    $user->increment('credits', $credits);
                    $newBalance = $user->fresh()->credits;
                    
                    // Create credit transaction record
                    CreditTransaction::create([
                        'user_id' => $user->id,
                        'admin_id' => null, // System transaction
                        'amount' => $credits,
                        'type' => 'purchase',
                        'description' => "Credits purchased via Stripe (€{$payment->amount})",
                        'balance_after' => $newBalance,
                    ]);
                });
                
                return redirect()->route('audio.index')->with('success', 
                    "Payment successful! You have received {$credits} credits."
                );
            } else {
                // Update payment status to failed
                $payment->update(['status' => 'failed']);
                
                return redirect()->route('audio.index')->with('error', 'Payment not completed.');
            }
            
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

@extends('layouts.app')

@section('title', 'Admin - Payments')

@section('content')
<x-page-header title="Payments" description="{{ $payments->total() }} payments total." :back="route('admin.dashboard')" backLabel="Back to overview" />

<x-panel>
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Credits</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    @php
                        $paymentLabel = [
                            'pending' => 'Pending',
                            'completed' => 'Paid',
                            'failed' => 'Failed',
                            'refunded' => 'Refunded',
                        ][$payment->status] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-ink">{{ $payment->user->name }}</p>
                            <p class="text-sm text-muted">{{ $payment->user->email }}</p>
                        </td>
                        <td>
                            <p class="font-medium text-ink">€{{ number_format($payment->amount, 2) }}</p>
                            <p class="text-sm text-muted">{{ strtoupper($payment->currency) }}</p>
                        </td>
                        <td>{{ $payment->credits_purchased }}</td>
                        <td><x-status :status="$payment->status" :label="$paymentLabel" /></td>
                        <td>
                            <p class="whitespace-nowrap text-sm text-ink">{{ $payment->created_at->format('d M Y') }}</p>
                            <p class="text-sm text-muted">{{ $payment->created_at->format('H:i') }}</p>
                        </td>
                        <td>
                            <p class="font-mono text-xs text-muted">{{ Str::limit($payment->stripe_session_id, 20) }}</p>
                            @if ($payment->stripe_payment_intent_id)
                                <p class="font-mono text-xs text-muted">{{ Str::limit($payment->stripe_payment_intent_id, 20) }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-muted">No payments have been processed yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payments->hasPages())
        <div class="mt-4">{{ $payments->links() }}</div>
    @endif
</x-panel>
@endsection

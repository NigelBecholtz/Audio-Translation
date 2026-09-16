@extends('layouts.app')

@section('title', 'Credit History')

@section('content')
<x-page-header title="Credit history" description="{{ $user->name }} ({{ $user->email }})" :back="route('admin.users')" backLabel="Back to users" />

<x-panel class="mb-6">
    <dl class="flex flex-wrap gap-x-10 gap-y-4 text-sm">
        <div>
            <dt class="text-muted">Current balance</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ number_format($user->credits, 2) }} credits</dd>
        </div>
        <div>
            <dt class="text-muted">Member since</dt>
            <dd class="mt-1 font-medium text-ink">{{ $user->created_at->format('d M Y') }}</dd>
        </div>
        <div>
            <dt class="text-muted">Translations used</dt>
            <dd class="mt-1 font-medium text-ink">{{ $user->translations_used }} / {{ $user->translations_limit }}</dd>
        </div>
    </dl>
</x-panel>

<x-panel title="Credit transactions">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance after</th>
                    <th>Description</th>
                    <th>Admin</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    @php
                        $typeLabel = [
                            'admin_add' => 'Admin addition',
                            'admin_remove' => 'Admin removal',
                            'purchase' => 'Purchase',
                            'usage' => 'Usage',
                        ][$transaction->type] ?? str($transaction->type)->replace('_', ' ')->ucfirst();
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap">{{ $typeLabel }}</td>
                        <td class="whitespace-nowrap font-medium {{ $transaction->isPositive() ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->isPositive() ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                        </td>
                        <td>{{ number_format($transaction->balance_after, 2) }}</td>
                        <td class="max-w-xs">{{ $transaction->description }}</td>
                        <td>
                            @if ($transaction->admin)
                                <p class="text-ink">{{ $transaction->admin->name }}</p>
                                <p class="text-sm text-muted">{{ $transaction->admin->email }}</p>
                            @else
                                <span class="text-muted">System</span>
                            @endif
                        </td>
                        <td>
                            <p class="whitespace-nowrap text-sm text-ink">{{ $transaction->created_at->format('d M Y') }}</p>
                            <p class="text-sm text-muted">{{ $transaction->created_at->format('H:i') }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-muted">No credit transactions for this user yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif
</x-panel>
@endsection

@extends('layouts.app')

@section('title', 'Admin overview')

@section('content')
<x-page-header title="Overview" description="Key figures across payments, users and audio files.">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <x-button type="submit" variant="ghost" size="sm" icon="arrow-right-from-bracket">Admin logout</x-button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="panel mb-8 overflow-hidden">
    <dl class="grid grid-cols-2 divide-x divide-y divide-line sm:grid-cols-4">
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Revenue</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">€{{ number_format($totalRevenue, 2) }}</dd>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Payments completed</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $totalPayments }}</dd>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Payments pending</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $pendingPayments }}</dd>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Payments failed</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $failedPayments }}</dd>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Users</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $totalUsers }}</dd>
            <p class="mt-1 text-xs text-faint">{{ $usersWithCredits }} with credits, {{ $usersWithPayments }} with payments</p>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Audio files completed</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $completedAudioFiles }}</dd>
            <p class="mt-1 text-xs text-faint">{{ $totalAudioFiles }} total</p>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Audio files processing</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $processingAudioFiles }}</dd>
        </div>
        <div class="p-4 sm:p-5">
            <dt class="text-sm text-muted">Audio files failed</dt>
            <dd class="mt-1 font-display text-2xl font-bold text-ink">{{ $failedAudioFiles }}</dd>
        </div>
    </dl>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <x-panel title="Recent payments">
        <x-slot:actions>
            <a href="{{ route('admin.payments') }}" class="text-sm font-medium text-accent no-underline hover:underline">All payments</a>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentPayments as $payment)
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
                                <p class="text-sm text-muted">{{ $payment->credits_purchased }} credits</p>
                            </td>
                            <td>€{{ number_format($payment->amount, 2) }}</td>
                            <td><x-status :status="$payment->status" :label="$paymentLabel" /></td>
                            <td class="text-sm text-muted">{{ $payment->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm text-muted">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    <x-panel title="Recent users">
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" class="text-sm font-medium text-accent no-underline hover:underline">All users</a>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Credits</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentUsers as $recentUser)
                        <tr>
                            <td>
                                <p class="font-medium text-ink">{{ $recentUser->name }}</p>
                                <p class="text-sm text-muted">{{ $recentUser->email }}</p>
                            </td>
                            <td>{{ number_format($recentUser->credits, 2) }}</td>
                            <td class="text-sm text-muted">{{ $recentUser->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-sm text-muted">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</div>

<x-panel title="Monthly revenue" description="Completed payments by month." class="mt-6">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Revenue</th>
                    <th>Payments</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($monthlyRevenue as $month)
                    <tr>
                        <td>{{ $month->month }}</td>
                        <td>€{{ number_format($month->revenue, 2) }}</td>
                        <td class="text-muted">{{ $month->payments }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-8 text-center text-sm text-muted">No payments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-panel>
@endsection

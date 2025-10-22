@extends('layouts.app')

@section('title', 'Admin - Payments')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Payments</h1>
                    <p class="text-gray-600">Overview of all payments</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Credits
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stripe ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $payment->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $payment->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</div>
                                    <div class="text-sm text-gray-500">{{ strtoupper($payment->currency) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $payment->credits_purchased }}</div>
                                    <div class="text-sm text-gray-500">credits</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($payment->status === 'completed') bg-green-100 text-green-800
                                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($payment->status === 'failed') bg-red-100 text-red-800
                                        @elseif($payment->status === 'refunded') bg-gray-100 text-gray-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if($payment->status === 'completed')
                                            <i class="fas fa-check-circle mr-1"></i>
                                        @elseif($payment->status === 'pending')
                                            <i class="fas fa-clock mr-1"></i>
                                        @elseif($payment->status === 'failed')
                                            <i class="fas fa-times-circle mr-1"></i>
                                        @elseif($payment->status === 'refunded')
                                            <i class="fas fa-undo mr-1"></i>
                                        @endif
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $payment->created_at->format('d-m-Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $payment->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-mono">{{ Str::limit($payment->stripe_session_id, 20) }}</div>
                                    @if($payment->stripe_payment_intent_id)
                                        <div class="text-sm text-gray-500 font-mono">{{ Str::limit($payment->stripe_payment_intent_id, 20) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="text-blue-600 hover:text-blue-900" title="View details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($payment->status === 'completed')
                                            <button class="text-orange-600 hover:text-orange-900" title="Refund">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-credit-card text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">No payments found</p>
                                        <p class="text-sm">No payments have been processed yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($payments->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-euro-sign text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ number_format($payments->where('status', 'completed')->sum('amount'), 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Completed Payments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $payments->where('status', 'completed')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Payments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $payments->where('status', 'pending')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Failed Payments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $payments->where('status', 'failed')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

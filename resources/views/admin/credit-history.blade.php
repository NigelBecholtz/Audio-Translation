@extends('layouts.app')

@section('title', 'Credit History')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Credit History</h1>
                    <p class="text-gray-600">History of {{ $user->name }}</p>
                </div>
                <a href="{{ route('admin.users') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Users
                </a>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16">
                    <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-user text-gray-600 text-2xl"></i>
                    </div>
                </div>
                <div class="ml-6">
                    <h3 class="text-xl font-medium text-gray-900">{{ $user->name }}</h3>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    <div class="mt-2 flex items-center space-x-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-coins mr-1"></i>
                            {{ $user->credits }} credits
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-calendar mr-1"></i>
                            Member since {{ $user->created_at->format('d-m-Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Transactions Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Credit Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Balance After
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Admin
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($transaction->type === 'admin_add') bg-green-100 text-green-800
                                        @elseif($transaction->type === 'admin_remove') bg-red-100 text-red-800
                                        @elseif($transaction->type === 'purchase') bg-blue-100 text-blue-800
                                        @elseif($transaction->type === 'usage') bg-orange-100 text-orange-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if($transaction->type === 'admin_add')
                                            <i class="fas fa-plus mr-1"></i>
                                            Admin Addition
                                        @elseif($transaction->type === 'admin_remove')
                                            <i class="fas fa-minus mr-1"></i>
                                            Admin Removal
                                        @elseif($transaction->type === 'purchase')
                                            <i class="fas fa-shopping-cart mr-1"></i>
                                            Purchase
                                        @elseif($transaction->type === 'usage')
                                            <i class="fas fa-file-audio mr-1"></i>
                                            Usage
                                        @else
                                            <i class="fas fa-question mr-1"></i>
                                            {{ ucfirst($transaction->type) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium 
                                        @if($transaction->isPositive()) text-green-600
                                        @else text-red-600 @endif">
                                        @if($transaction->isPositive())
                                            +{{ number_format($transaction->amount, 2) }}
                                        @else
                                            {{ number_format($transaction->amount, 2) }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ number_format($transaction->balance_after, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $transaction->description }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->admin)
                                        <div class="text-sm text-gray-900">{{ $transaction->admin->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->admin->email }}</div>
                                    @else
                                        <span class="text-sm text-gray-500">System</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $transaction->created_at->format('d-m-Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $transaction->created_at->format('H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-history text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">No transactions found</p>
                                        <p class="text-sm">No credit transactions for this user yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-plus text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Added</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($transactions->where('amount', '>', 0)->sum('amount'), 2) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-minus text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Used</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format(abs($transactions->where('amount', '<', 0)->sum('amount')), 2) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shopping-cart text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Purchases</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $transactions->where('type', 'purchase')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-audio text-orange-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Translations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $transactions->where('type', 'usage')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

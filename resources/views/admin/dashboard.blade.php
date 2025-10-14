@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
                    <p class="text-gray-300">Overzicht van alle betalingen en gebruikers</p>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
                    <span class="text-sm text-gray-300">
                        Ingelogd als: <strong class="text-white">{{ auth()->user()->name }}</strong>
                    </span>
                    <form method="POST" action="{{ route('admin.logout') }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Admin Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats Grid - Responsive -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <!-- Revenue Card -->
            <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-euro-sign text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ number_format($totalRevenue, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Payments Card -->
            <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-credit-card text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Payments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalPayments }}</p>
                        <p class="text-xs text-gray-500">{{ $pendingPayments }} pending, {{ $failedPayments }} failed</p>
                    </div>
                </div>
            </div>

            <!-- Users Card -->
            <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-purple-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</p>
                        <p class="text-xs text-gray-500">{{ $usersWithCredits }} with credits</p>
                    </div>
                </div>
            </div>

            <!-- Audio Files Card -->
            <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-audio text-orange-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Audio Files</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalAudioFiles }}</p>
                        <p class="text-xs text-gray-500">{{ $completedAudioFiles }} completed, {{ $processingAudioFiles }} processing</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables - Responsive -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-8 mb-8">
            <!-- Monthly Revenue Chart -->
            <div class="bg-white shadow-lg rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Revenue</h3>
                <div class="space-y-3">
                    @forelse($monthlyRevenue as $month)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">{{ $month->month }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm font-medium text-gray-900">€{{ number_format($month->revenue, 2) }}</span>
                                <span class="text-xs text-gray-500">({{ $month->payments }} payments)</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No payments yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Status Overview -->
            <div class="bg-white shadow-lg rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Overview</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">Completed Payments</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $totalPayments }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">Pending Payments</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $pendingPayments }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">Failed Payments</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $failedPayments }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                            <span class="text-sm text-gray-600">Users with Credits</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900">{{ $usersWithCredits }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity - Responsive -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-8">
            <!-- Recent Payments -->
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Payments</h3>
                    <a href="{{ route('admin.payments') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">All payments</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentPayments as $payment)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->credits_purchased }} credits</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</p>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($payment->status === 'completed') bg-green-100 text-green-800
                                    @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($payment->status === 'failed') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No payments yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Users</h3>
                    <a href="{{ route('admin.users') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">All users</a>
                </div>
                <div class="space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">{{ $user->credits }} credits</p>
                                <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No users yet</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions - Responsive -->
        <div class="mt-8 bg-white shadow-lg rounded-xl p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <a href="{{ route('admin.payments') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-credit-card text-blue-600 text-xl mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-900">Payments</p>
                        <p class="text-sm text-gray-600">View all payments</p>
                    </div>
                </a>
                <a href="{{ route('admin.users') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-users text-green-600 text-xl mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-900">Users</p>
                        <p class="text-sm text-gray-600">Manage users</p>
                    </div>
                </a>
                <a href="{{ route('admin.audio-files') }}" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <i class="fas fa-file-audio text-purple-600 text-xl mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-900">Audio Files</p>
                        <p class="text-sm text-gray-600">View all audio files</p>
                    </div>
                </a>
        <a href="{{ route('admin.csv-translations.index') }}" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
            <i class="fas fa-language text-orange-600 text-xl mr-3"></i>
            <div>
                <p class="font-medium text-gray-900">File Translations</p>
                <p class="text-sm text-gray-600">Translate CSV & XLSX files</p>
            </div>
        </a>
            </div>
        </div>
    </div>
</div>
@endsection

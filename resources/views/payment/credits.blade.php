@extends('layouts.app')

@section('title', 'Buy Credits')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 48px; font-weight: bold; color: #ffffff; margin-bottom: 16px;">Buy Credits</h1>
            <p style="font-size: 20px; color: #ffffff;">Buy credits for more audio translations</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Current Status -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 shadow-2xl rounded-2xl border-2 border-gray-600 p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-wallet mr-2 text-blue-400"></i>
                        Your Credits
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="bg-gradient-to-r from-gray-700 to-gray-600 p-4 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-300">Current Credits</span>
                                <span class="text-2xl font-bold text-blue-400">{{ $user->credits }}</span>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-gray-700 to-gray-600 p-4 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-300">Free Translations</span>
                                <span class="text-2xl font-bold text-green-400">{{ $user->getRemainingTranslations() }}</span>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-gray-700 to-gray-600 p-4 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-300">Account Type</span>
                                <span class="text-sm font-bold text-purple-400 capitalize">{{ $user->subscription_type }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Package -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 shadow-2xl rounded-2xl border-2 border-gray-600 p-8">
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-gift text-white text-2xl"></i>
                        </div>
                        <h2 class="text-3xl font-bold text-white mb-2">{{ $creditPackage['name'] }}</h2>
                        <p class="text-lg text-gray-300">{{ $creditPackage['description'] }}</p>
                    </div>

                    <!-- Package Details -->
                    <div class="grid grid-cols-3 gap-6 mb-8">
                        <div class="text-center">
                            <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-coins text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-white">{{ $creditPackage['credits'] }}</h3>
                            <p class="text-sm text-gray-300">Credits</p>
                        </div>
                        <div class="text-center">
                            <div class="bg-green-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-euro-sign text-green-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-white">€{{ number_format($creditPackage['price'], 2) }}</h3>
                            <p class="text-sm text-gray-300">Total</p>
                        </div>
                        <div class="text-center">
                            <div class="bg-purple-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-percentage text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="font-bold text-white">€{{ number_format($creditPackage['price_per_credit'], 2) }}</h3>
                            <p class="text-sm text-gray-300">Per Translation</p>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-gray-700 rounded-xl p-6 mb-8">
                        <h3 class="font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            What do you get?
                        </h3>
                        <ul class="space-y-2">
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                {{ $creditPackage['credits'] }} audio translations
                            </li>
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                All 22 supported languages
                            </li>
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                High quality AI translation
                            </li>
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                Credits never expire
                            </li>
                            <li class="flex items-center text-gray-300">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                Secure payment via Stripe
                            </li>
                        </ul>
                    </div>

                    <!-- Purchase Button -->
                    <form method="POST" action="{{ route('payment.checkout') }}">
                        @csrf
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-4 px-8 rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl font-bold text-lg">
                            <i class="fas fa-credit-card mr-2"></i>
                            Buy {{ $creditPackage['credits'] }} Credits for €{{ number_format($creditPackage['price'], 2) }}
                        </button>
                    </form>

                    <!-- Security Notice -->
                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-400 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-green-400 mr-2"></i>
                            Secure payment via Stripe • SSL secured
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

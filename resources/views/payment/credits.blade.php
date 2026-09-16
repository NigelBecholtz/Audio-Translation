@extends('layouts.app')

@section('title', 'Credits')

@section('content')
<x-page-header title="Credits" description="Buy credits to keep translating once your free translations run out." />

<div class="grid gap-6 sm:grid-cols-2">
    <x-panel title="Your balance">
        <p class="text-sm text-muted">Translations left</p>
        <p class="font-display text-5xl font-bold text-ink">{{ $user->hasUnlimitedCredits() ? '∞' : $user->getRemainingTranslations() }}</p>

        <dl class="mt-6 flex flex-col gap-3 border-t border-line pt-4 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-muted">Credits</dt>
                <dd class="font-medium">{{ $user->hasUnlimitedCredits() ? 'Unlimited (admin)' : $user->credits }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-muted">Account type</dt>
                <dd class="font-medium capitalize">{{ $user->subscription_type }}</dd>
            </div>
        </dl>
    </x-panel>

    <x-panel title="{{ $creditPackage['name'] }}" description="{{ $creditPackage['credits'] }} audio translations for €{{ number_format($creditPackage['price'], 2) }}.">
        <div class="flex items-baseline gap-2">
            <span class="font-display text-4xl font-bold text-ink">€{{ number_format($creditPackage['price'], 2) }}</span>
            <span class="text-sm text-muted">for {{ $creditPackage['credits'] }} credits · €{{ number_format($creditPackage['price_per_credit'], 2) }} per translation</span>
        </div>

        <ul class="mt-5 flex flex-col gap-2 text-sm text-muted">
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-success mt-1" aria-hidden="true"></i>
                {{ $creditPackage['credits'] }} audio translations
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-success mt-1" aria-hidden="true"></i>
                All {{ count(config('audio.languages')) }} supported languages
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-success mt-1" aria-hidden="true"></i>
                Credits never expire
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-check text-success mt-1" aria-hidden="true"></i>
                Secure payment via Stripe
            </li>
        </ul>

        <form method="POST" action="{{ route('payment.checkout') }}" class="mt-6">
            @csrf
            <x-button type="submit" variant="primary" size="lg" icon="credit-card" class="w-full">
                Buy {{ $creditPackage['credits'] }} credits — €{{ number_format($creditPackage['price'], 2) }}
            </x-button>
        </form>
    </x-panel>
</div>
@endsection

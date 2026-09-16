@extends('layouts.guest')

@section('title', 'Audio Translator — translate a recording, reviewed at every step')

@section('content')

    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-4 pb-16 pt-12 sm:px-8 sm:pb-24 sm:pt-20">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-16">
            <div class="max-w-xl">
                <h1 class="text-4xl sm:text-5xl">Turn one recording into the same message, in another language and voice.</h1>
                <p class="mt-5 text-lg text-muted">Upload an audio file and Audio Translator writes out what's said, translates it, and speaks it back in a new voice. You review the transcript and the translation before either one moves on.</p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @auth
                        <x-button :href="route('audio.index')" size="lg">Go to dashboard</x-button>
                    @else
                        <x-button :href="route('register')" size="lg">Create free account</x-button>
                        <x-button :href="route('login')" variant="secondary" size="lg">Log in</x-button>
                    @endauth
                </div>
            </div>

            <div class="panel">
                <div class="panel-body">
                    <p class="text-sm font-medium text-muted">One recording's journey</p>
                    <p class="mt-4 text-ink">"Kunt u de offerte voor vrijdag klaar hebben?"</p>
                    <p class="mt-2 text-muted">"Czy możesz przygotować ofertę na piątek?"</p>
                    <div class="mt-6">
                        <x-language-pair from="nl" to="pl" size="lg" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-8">
            <h2 class="text-2xl sm:text-3xl">How it works</h2>
            <ol class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => 'Upload your audio', 'desc' => 'MP3, WAV, or M4A up to ' . config('audio.max_upload_size', 100) . 'MB.'],
                    ['title' => 'Review the transcript', 'desc' => 'We transcribe it with Whisper; you correct anything before it moves on.'],
                    ['title' => 'Review the translation', 'desc' => 'We translate it; you edit the wording before audio is generated.'],
                    ['title' => 'Download the new audio', 'desc' => 'We generate natural speech in the target language and voice.'],
                ] as $i => $step)
                    <li>
                        <span class="font-display text-2xl text-accent">{{ $i + 1 }}</span>
                        <h3 class="mt-2 text-lg">{{ $step['title'] }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ $step['desc'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- What's included --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-8">
        <h2 class="text-2xl sm:text-3xl">What's included</h2>
        <div class="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="font-display text-3xl">{{ count(config('audio.languages')) }}</p>
                <p class="mt-1 text-muted">Languages</p>
                <p class="mt-2 text-sm text-muted">Choose your target language from {{ count(config('audio.languages')) }} options.</p>
            </div>
            <div>
                <p class="font-display text-3xl">{{ count(config('audio.available_voices')) }}</p>
                <p class="mt-1 text-muted">Voices</p>
                <p class="mt-2 text-sm text-muted">Natural voices, male and female, from Gemini TTS.</p>
            </div>
            <div>
                <p class="font-display text-3xl">{{ config('audio.max_upload_size', 100) }}MB</p>
                <p class="mt-1 text-muted">Max file size</p>
                <p class="mt-2 text-sm text-muted">Upload MP3, WAV, or M4A files.</p>
            </div>
            <div>
                <p class="font-display text-3xl">2×</p>
                <p class="mt-1 text-muted">Human review</p>
                <p class="mt-2 text-sm text-muted">You approve the transcript and the translation before audio is generated.</p>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="border-t border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-8">
            <h2 class="text-2xl sm:text-3xl">Simple, transparent pricing</h2>
            <p class="mt-2 text-muted">No subscriptions, no hidden fees — pay only for what you use.</p>

            <div class="mt-8 grid max-w-3xl gap-6 sm:grid-cols-2">
                <div class="panel">
                    <div class="panel-body">
                        <p class="text-sm font-medium text-muted">Free</p>
                        <p class="mt-2 font-display text-4xl">€0</p>
                        <p class="mt-1 text-sm text-muted">No credit card required</p>
                        <ul class="mt-6 space-y-2 text-sm text-muted">
                            <li>2 free translations</li>
                            <li>All {{ count(config('audio.languages')) }} languages</li>
                            <li>{{ config('audio.max_upload_size', 100) }}MB file size</li>
                            <li>All voices</li>
                        </ul>
                        <div class="mt-6">
                            @auth
                                <x-button :href="route('audio.index')" variant="secondary" class="w-full justify-center">Go to dashboard</x-button>
                            @else
                                <x-button :href="route('register')" variant="secondary" class="w-full justify-center">Get started free</x-button>
                            @endauth
                        </div>
                    </div>
                </div>

                <div class="panel border-line-strong">
                    <div class="panel-body">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-accent">Starter pack</p>
                            <span class="rounded-full bg-accent-soft px-3 py-1 text-xs font-semibold text-accent">Popular</span>
                        </div>
                        <p class="mt-2 font-display text-4xl">€5</p>
                        <p class="mt-1 text-sm text-muted">€0.50 per translation</p>
                        <ul class="mt-6 space-y-2 text-sm text-muted">
                            <li>10 translations</li>
                            <li>All {{ count(config('audio.languages')) }} languages</li>
                            <li>{{ config('audio.max_upload_size', 100) }}MB file size</li>
                            <li>All voices</li>
                            <li>Credits never expire</li>
                        </ul>
                        <div class="mt-6">
                            @auth
                                <x-button :href="route('payment.credits')" class="w-full justify-center">Buy credits</x-button>
                            @else
                                <x-button :href="route('register')" class="w-full justify-center">Start now</x-button>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-8">
        <div class="panel">
            <div class="panel-body flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl">Ready to translate your first recording?</h2>
                    <p class="mt-1 text-muted">Create a free account and get 2 free translations.</p>
                </div>
                @auth
                    <x-button :href="route('audio.create')" size="lg">Upload audio</x-button>
                @else
                    <x-button :href="route('register')" size="lg">Create free account</x-button>
                @endauth
            </div>
        </div>
    </section>

@endsection

@extends('layouts.guest')

@section('title', 'Create account — Audio Translator')

@section('content')

    <div class="mx-auto max-w-md px-4 py-16 sm:px-8">
        <h1 class="text-3xl">Create your account</h1>
        <p class="mt-2 text-muted">Start with 2 free translations across {{ count(config('audio.languages')) }} languages. No credit card needed.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <x-field label="Full name" for="name" error="name">
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       class="input @error('name') is-invalid @enderror"
                       placeholder="Your name"
                       autocomplete="name"
                       required>
            </x-field>

            <x-field label="Email address" for="email" error="email">
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="input @error('email') is-invalid @enderror"
                       placeholder="you@example.com"
                       autocomplete="email"
                       required>
            </x-field>

            <x-field label="Password" for="password" error="password">
                <input type="password"
                       id="password"
                       name="password"
                       class="input @error('password') is-invalid @enderror"
                       placeholder="Min. 8 characters"
                       autocomplete="new-password"
                       required>
            </x-field>

            <x-field label="Confirm password" for="password_confirmation">
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="input"
                       placeholder="Repeat your password"
                       autocomplete="new-password"
                       required>
            </x-field>

            <x-button type="submit" size="lg" class="w-full justify-center">Create free account</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Log in</a>
        </p>
    </div>

@endsection

@extends('layouts.guest')

@section('title', 'Log in — Audio Translator')

@section('content')

    <div class="mx-auto max-w-md px-4 py-16 sm:px-8">
        <h1 class="text-3xl">Welcome back</h1>
        <p class="mt-2 text-muted">Log in to continue translating audio into {{ count(config('audio.languages')) }} languages.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

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
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required>
            </x-field>

            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" id="remember">
                Remember me for 30 days
            </label>

            <x-button type="submit" size="lg" class="w-full justify-center">Log in</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-accent hover:underline">Create one free</a>
        </p>
    </div>

@endsection

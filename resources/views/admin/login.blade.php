@extends('layouts.guest')

@section('title', 'Admin login — Audio Translator')

@section('content')

    <div class="mx-auto max-w-md px-4 py-16 sm:px-8">
        <h1 class="text-3xl">Admin login</h1>
        <p class="mt-2 text-muted">For administrators only.</p>

        <form method="POST" action="{{ route('admin.login') }}" class="mt-8 space-y-5">
            @csrf

            <x-field label="Email address" for="email" error="email">
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="input @error('email') is-invalid @enderror"
                       placeholder="admin@example.com"
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
                <input type="checkbox" name="remember">
                Remember me
            </label>

            <x-button type="submit" size="lg" class="w-full justify-center">Admin login</x-button>
        </form>

        <p class="mt-6 text-sm text-muted">This page is only accessible to administrators with admin rights.</p>

        <p class="mt-4 text-sm">
            <a href="{{ route('audio.index') }}" class="font-medium text-muted hover:text-ink">Back to main site</a>
        </p>
    </div>

@endsection

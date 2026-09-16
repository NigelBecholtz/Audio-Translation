<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Audio Translator')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;500;700&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 no-underline">
                <span class="grid h-8 w-8 place-items-center rounded-control bg-ink text-white">
                    <i class="fa-solid fa-language" aria-hidden="true"></i>
                </span>
                <span class="font-display text-lg font-bold">Audio Translator</span>
            </a>
            <nav class="flex items-center gap-2" aria-label="Account">
                @auth
                    <x-button :href="route('audio.index')" size="sm">Go to dashboard</x-button>
                @else
                    <x-button :href="route('login')" variant="ghost" size="sm">Log in</x-button>
                    <x-button :href="route('register')" size="sm">Create account</x-button>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @if (session('success') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-8">
                @if (session('success'))
                    <x-alert type="success">{{ session('success') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-6 text-sm text-muted sm:px-8">
            <p>&copy; {{ date('Y') }} Audio Translator</p>
            <p>Made by <a href="https://becholtz.com" class="font-medium text-ink hover:text-accent">Nigel Becholtz</a></p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

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
<body class="min-h-screen">
@php
    $user = auth()->user();
    $primaryNav = [
        ['route' => 'audio.index', 'active' => 'audio.index', 'icon' => 'house', 'label' => 'Dashboard'],
        ['route' => 'audio.create', 'active' => ['audio.create', 'audio.show', 'audio.additional-translations'], 'icon' => 'microphone-lines', 'label' => 'Translate audio'],
        ['route' => 'text-to-audio.index', 'active' => 'text-to-audio.*', 'icon' => 'align-left', 'label' => 'Text to speech'],
        ['route' => 'style-presets.index', 'active' => 'style-presets.*', 'icon' => 'sliders', 'label' => 'Voice styles'],
        ['route' => 'payment.credits', 'active' => 'payment.*', 'icon' => 'wallet', 'label' => 'Credits'],
    ];
    $adminNav = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'chart-simple', 'label' => 'Overview'],
        ['route' => 'admin.users', 'active' => 'admin.users*', 'icon' => 'users', 'label' => 'Users'],
        ['route' => 'admin.payments', 'active' => 'admin.payments', 'icon' => 'receipt', 'label' => 'Payments'],
        ['route' => 'admin.audio-files', 'active' => 'admin.audio-files', 'icon' => 'file-audio', 'label' => 'Audio files'],
        ['route' => 'admin.csv-translations.index', 'active' => 'admin.csv-translations.*', 'icon' => 'table', 'label' => 'File translation'],
    ];
@endphp

<div class="lg:grid lg:grid-cols-[16rem_1fr]">
    {{-- Sidebar (desktop) / slide-down menu (mobile) --}}
    <aside class="border-b border-line bg-surface lg:border-r lg:border-b-0">
      <div class="lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
        <div class="flex h-16 items-center justify-between px-4 lg:px-5">
            <a href="{{ route('audio.index') }}" class="flex items-center gap-2.5 no-underline">
                <span class="grid h-8 w-8 place-items-center rounded-control bg-ink text-white">
                    <i class="fa-solid fa-language" aria-hidden="true"></i>
                </span>
                <span class="font-display text-lg font-bold">Audio Translator</span>
            </a>
            <button type="button" class="btn btn-ghost btn-sm lg:hidden" data-menu-toggle aria-expanded="false" aria-controls="app-navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                <span class="sr-only">Menu</span>
            </button>
        </div>

        <nav id="app-navigation" class="hidden flex-1 flex-col gap-6 overflow-y-auto px-3 pb-4 lg:flex" aria-label="Main">
            <ul class="flex flex-col gap-0.5">
                @foreach ($primaryNav as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" class="nav-item" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                            <i class="fa-solid fa-{{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($user?->isAdmin())
                <div>
                    <p class="px-3 pb-1.5 text-sm font-semibold text-faint">Admin</p>
                    <ul class="flex flex-col gap-0.5">
                        @foreach ($adminNav as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" class="nav-item" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                                    <i class="fa-solid fa-{{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($user)
                <div class="mt-auto flex flex-col gap-3 border-t border-line px-3 pt-4">
                    <a href="{{ route('payment.credits') }}" class="block no-underline">
                        <span class="block text-sm text-muted">Translations left</span>
                        <span class="font-display text-2xl font-bold">{{ $user->hasUnlimitedCredits() ? '∞' : $user->getRemainingTranslations() }}</span>
                    </a>
                    <div class="flex items-center justify-between gap-2">
                        <span class="min-w-0 truncate text-sm font-medium" title="{{ $user->email }}">{{ $user->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Log out
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </nav>
      </div>
    </aside>

    <main class="min-w-0 px-4 py-8 sm:px-8 lg:px-12 lg:py-10">
        <div class="max-w-[65rem]">
            @if (session('success'))
                <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
            @endif
            @if (session('error'))
                <x-alert type="error" class="mb-6">{{ session('error') }}</x-alert>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script>
    document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nav = document.getElementById(button.getAttribute('aria-controls'));
            const open = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(open));
            nav.classList.toggle('hidden', !open);
            nav.classList.toggle('flex', open);
        });
    });
</script>
@stack('scripts')
</body>
</html>

@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'panel']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-lg">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-sm text-muted">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="panel-body">
        {{ $slot }}
    </div>
</section>

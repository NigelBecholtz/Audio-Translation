@props([
    'title',
    'description' => null,
    'back' => null,
    'backLabel' => 'Back',
])

<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    @if ($back)
        <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-muted no-underline hover:text-ink">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ $backLabel }}
        </a>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-2xl">
            <h1 class="text-3xl sm:text-[2.125rem]">{{ $title }}</h1>
            @if ($description)
                <p class="mt-2 text-muted">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>

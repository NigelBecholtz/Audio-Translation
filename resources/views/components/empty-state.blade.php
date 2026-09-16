@props([
    'title',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-start gap-3 px-1 py-8']) }}>
    @if ($icon)
        <i class="fa-solid fa-{{ $icon }} text-2xl text-faint" aria-hidden="true"></i>
    @endif
    <div>
        <h3 class="text-lg">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 max-w-md text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($action)
        {{ $action }}
    @endisset
</div>

@props([
    'type' => 'info',
])

@php
    $icon = match ($type) {
        'success' => 'circle-check',
        'error' => 'circle-exclamation',
        'warning' => 'triangle-exclamation',
        default => 'circle-info',
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "alert alert-{$type}"]) }}>
    <i class="fa-solid fa-{{ $icon }} mt-1 shrink-0" aria-hidden="true"></i>
    <div class="min-w-0">{{ $slot }}</div>
</div>

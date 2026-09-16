@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    $classes = trim("btn btn-{$variant} ".($size ? "btn-{$size}" : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </button>
@endif

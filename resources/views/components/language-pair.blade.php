@props([
    'from',
    'to',
    'size' => 'md',
])

@php
    $names = config('audio.languages');
    $fromName = $names[$from] ?? strtoupper($from);
    $toName = $names[$to] ?? strtoupper($to);
@endphp

<span {{ $attributes->merge(['class' => "lang-pair lang-pair-{$size}"]) }} title="{{ $fromName }} to {{ $toName }}">
    <span class="lang-code">{{ $from }}</span>
    <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
    <span class="lang-code">{{ $to }}</span>
    <span class="sr-only">({{ $fromName }} to {{ $toName }})</span>
</span>

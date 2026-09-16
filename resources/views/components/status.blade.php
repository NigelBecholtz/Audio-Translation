@props([
    'status',
    'label' => null,
])

@php
    [$variant, $defaultLabel] = match ($status) {
        'completed' => ['success', 'Ready'],
        'failed' => ['danger', 'Failed'],
        'pending_approval' => ['review', 'Review transcript'],
        'pending_tts_approval' => ['review', 'Review translation'],
        'uploaded', 'pending' => ['neutral', 'Queued'],
        'transcribing' => ['progress', 'Transcribing'],
        'translating' => ['progress', 'Translating'],
        'generating_audio' => ['progress', 'Generating audio'],
        'processing' => ['progress', 'Processing'],
        'refunded' => ['neutral', 'Refunded'],
        default => ['neutral', str((string) $status)->replace('_', ' ')->ucfirst()],
    };
@endphp

<span {{ $attributes->merge(['class' => "status status-{$variant}"]) }}>{{ $label ?? $defaultLabel }}</span>

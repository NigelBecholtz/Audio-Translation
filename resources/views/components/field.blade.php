@props([
    'label',
    'for' => null,
    'hint' => null,
    'error' => null,
])

<div {{ $attributes }}>
    <label @if ($for) for="{{ $for }}" @endif class="field-label">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="field-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        @error($error)
            <p class="field-error">{{ $message }}</p>
        @enderror
    @endif
</div>

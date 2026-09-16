{{--
    Renders <option>s for a language <select>.
    @param string|null   $selectedLanguage  code to mark as selected
    @param iterable|null $languageOptions   code => name; defaults to config('audio.languages')
--}}
@php
    $languageOptions = collect($languageOptions ?? config('audio.languages'));
    $selectedLanguage = $selectedLanguage ?? null;
    $englishLanguages = $languageOptions->filter(fn ($name, $code) => str_starts_with($code, 'en'));
    $otherLanguages = $languageOptions->diffKeys($englishLanguages)->sort();
@endphp
@foreach ([__('English') => $englishLanguages, __('Other languages') => $otherLanguages] as $groupLabel => $group)
    @if ($group->isNotEmpty())
        <optgroup label="{{ $groupLabel }}">
            @foreach ($group as $code => $name)
                <option value="{{ $code }}"{{ $selectedLanguage === $code ? ' selected' : '' }}>{{ $name }}</option>
            @endforeach
        </optgroup>
    @endif
@endforeach

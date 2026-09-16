<?php

use App\Services\LanguageDetectionService;

it('lists 30 languages including the Nordic languages and Polish', function () {
    $languages = config('audio.languages');

    expect($languages)->toHaveCount(30)
        ->and($languages)->toHaveKeys(['no', 'da', 'sv', 'pl'])
        ->and($languages['no'])->toBe('Norwegian')
        ->and($languages['da'])->toBe('Danish')
        ->and($languages['sv'])->toBe('Swedish')
        ->and($languages['pl'])->toBe('Polish');
});

it('no longer exposes the duplicated language keys', function () {
    expect(config('audio.available_languages'))->toBeNull()
        ->and(config('audio.language_codes'))->toBeNull();
});

it('appends the new languages to the CSV preset without reordering it', function () {
    $preset = app(LanguageDetectionService::class)->getPresetLanguages();

    expect($preset)->toBe([
        'en', 'es', 'es_AR', 'de', 'fr', 'it', 'nl', 'ro', 'el', 'gr', 'sq', 'al', 'sk', 'lv', 'bg', 'fi', 'ca',
        'no', 'da', 'sv', 'pl',
    ]);
});

it('has a Gemini voice and supported-language entry for every language', function () {
    $voiceMapping = config('gemini.tts.voice_mapping');
    $supported = config('gemini.tts.supported_languages');

    foreach (array_keys(config('audio.languages')) as $code) {
        expect($voiceMapping)->toHaveKey($code);
    }

    expect($supported)->toHaveKeys(['no', 'da', 'sv', 'pl']);
});

<?php

use App\Support\LanguageCode;

it('reduces language codes to their base language', function (string $code, string $base) {
    expect(LanguageCode::base($code))->toBe($base);
})->with([
    ['en-gb', 'en'],
    ['EN-US', 'en'],
    ['es_AR', 'es'],
    ['nl', 'nl'],
    [' no ', 'no'],
    ['fil', 'fil'],
]);

it('treats regional variants of one language as the same language', function () {
    expect(LanguageCode::isSameLanguage('en-gb', 'en-us'))->toBeTrue()
        ->and(LanguageCode::isSameLanguage('nl', 'pl'))->toBeFalse();
});

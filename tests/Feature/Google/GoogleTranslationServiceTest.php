<?php

use App\Services\GoogleTranslationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesGoogleOAuth;

uses(FakesGoogleOAuth::class);

beforeEach(function () {
    $this->fakeGoogleOAuth();
});

function fakeGoogleTranslations(array $translatedTexts): void
{
    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'translations' => array_map(fn ($text) => ['translatedText' => $text], $translatedTexts),
        ]),
    ]);
}

it('sends plain text with explicit source and target language to Google Translate v3', function () {
    fakeGoogleTranslations(['hei verden']);

    $result = app(GoogleTranslationService::class)->translateText('hello world', 'no', 'en-gb');

    expect($result)->toBe('Hei verden');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://translation.googleapis.com/v3/projects/demo-project:translateText'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request['contents'] === ['hello world']
        && $request['mimeType'] === 'text/plain'
        && $request['sourceLanguageCode'] === 'en'
        && $request['targetLanguageCode'] === 'no');
});

it('lets Google detect the source language when none is given', function () {
    fakeGoogleTranslations(['Hej']);

    app(GoogleTranslationService::class)->translateText('Hallo', 'da');

    Http::assertSent(fn (Request $request) => ! array_key_exists('sourceLanguageCode', $request->data())
        && $request['targetLanguageCode'] === 'da');
});

it('maps app language codes to Google language codes', function (string $appCode, string $googleCode) {
    fakeGoogleTranslations(['x']);

    app(GoogleTranslationService::class)->translateText('Hello', $appCode, 'en');

    Http::assertSent(fn (Request $request) => $request['targetLanguageCode'] === $googleCode);
})->with([
    ['al', 'sq'],
    ['gr', 'el'],
    ['es_AR', 'es-AR'],
    ['NL', 'nl'],
]);

it('returns texts unchanged without calling Google when source and target are the same language', function () {
    Http::fake();

    $result = app(GoogleTranslationService::class)->translateBatch(['colour', ''], 'en-us', 'en-gb');

    expect($result)->toBe(['colour', '']);
    Http::assertNothingSent();
});

it('keeps empty entries in place and only sends non-empty texts', function () {
    fakeGoogleTranslations(['hei', 'ha det']);

    $result = app(GoogleTranslationService::class)->translateBatch(['', 'hello', '  ', 'goodbye'], 'no', 'en');

    expect($result)->toBe(['', 'Hei', '', 'Ha det']);
    Http::assertSent(fn (Request $request) => $request['contents'] === ['hello', 'goodbye']);
});

it('splits batches so a request stays under 30,000 codepoints', function () {
    Http::fake(fn (Request $request) => Http::response([
        'translations' => array_map(fn ($text) => ['translatedText' => $text], $request['contents']),
    ]));
    $texts = array_fill(0, 40, str_repeat('a', 999).'.');

    $result = app(GoogleTranslationService::class)->translateBatch($texts, 'nl', 'en');

    expect($result)->toHaveCount(40);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => count($request['contents']) === 30);
});

it('never sends more than 1024 texts in one request', function () {
    Http::fake(fn (Request $request) => Http::response([
        'translations' => array_map(fn ($text) => ['translatedText' => $text], $request['contents']),
    ]));

    $result = app(GoogleTranslationService::class)->translateBatch(array_fill(0, 1030, 'hi'), 'nl', 'en');

    expect($result)->toHaveCount(1030);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => count($request['contents']) === 1024);
});

it('retries when Google is temporarily unavailable', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Service unavailable']], 503)
            ->push(['translations' => [['translatedText' => 'hei']]]),
    ]);

    expect(app(GoogleTranslationService::class)->translateText('hi', 'no', 'en'))->toBe('Hei');
    Http::assertSentCount(2);
});

it('throws Google\'s error message and does not retry client errors', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response(['error' => ['message' => 'Invalid target language.']], 400),
    ]);

    expect(fn () => app(GoogleTranslationService::class)->translateText('hi', 'xx', 'en'))
        ->toThrow(RuntimeException::class, 'Invalid target language.');
    Http::assertSentCount(1);
});

it('throws a clear error when no Google Cloud project is configured', function () {
    $this->fakeGoogleOAuth(projectId: null);
    Http::fake();

    expect(fn () => app(GoogleTranslationService::class)->translateText('hi', 'no', 'en'))
        ->toThrow(RuntimeException::class, 'Google Cloud project ID not configured');
    Http::assertNothingSent();
});

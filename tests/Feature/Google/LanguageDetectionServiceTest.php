<?php

use App\Services\LanguageDetectionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesGoogleOAuth;

uses(FakesGoogleOAuth::class);

it('returns the language code from Google\'s detectLanguage response', function () {
    $this->fakeGoogleOAuth();
    Http::fake([
        'translation.googleapis.com/*' => Http::response(['languages' => [['languageCode' => 'sv', 'confidence' => 0.97]]]),
    ]);

    expect(app(LanguageDetectionService::class)->detectLanguage('Hej, det här är ett test.'))->toBe('sv');
    Http::assertSent(fn (Request $request) => $request->url() === 'https://translation.googleapis.com/v3/projects/demo-project:detectLanguage'
        && $request['content'] === 'Hej, det här är ett test.'
        && $request['mimeType'] === 'text/plain');
});

it('can be created and falls back to English without a Google Cloud project', function () {
    $this->fakeGoogleOAuth(projectId: null);
    Http::fake();

    expect(app(LanguageDetectionService::class)->detectLanguage('Hallo'))->toBe('en');
    Http::assertNothingSent();
});

it('falls back to English when Google returns an error', function () {
    $this->fakeGoogleOAuth();
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

    expect(app(LanguageDetectionService::class)->detectLanguage('Hallo'))->toBe('en');
});

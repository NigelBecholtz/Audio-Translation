<?php

use App\Jobs\ProcessAdditionalAudioTranslation;
use App\Jobs\ProcessAudioTranslationJob;
use App\Models\AudioFile;
use App\Models\AudioTranslation;
use App\Models\User;
use App\Services\AudioProcessingService;
use App\Services\GoogleOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\Concerns\FakesGoogleOAuth;

uses(RefreshDatabase::class, FakesGoogleOAuth::class);

beforeEach(function () {
    $this->fakeGoogleOAuth();
    $this->user = User::factory()->create(['credits' => 100]);
});

function audioFileWithTranscription(User $user, string $source, string $target, string $status = 'translating'): AudioFile
{
    return AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'test.mp3',
        'file_path' => 'audio/test.mp3',
        'file_size' => 1000,
        'source_language' => $source,
        'target_language' => $target,
        'voice' => 'kore',
        'transcription' => 'Dit is een korte test.',
        'status' => $status,
    ]);
}

it('translates the approved transcription with Google using the audio source language', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['translations' => [['translatedText' => 'to jest krótki test.']]])]);
    $audioFile = audioFileWithTranscription($this->user, 'nl', 'pl');

    ProcessAudioTranslationJob::dispatchSync($audioFile);

    $audioFile->refresh();
    expect($audioFile->status)->toBe('pending_tts_approval')
        ->and($audioFile->translated_text)->toBe('To jest krótki test.');
    Http::assertSent(fn (Request $request) => $request['sourceLanguageCode'] === 'nl'
        && $request['targetLanguageCode'] === 'pl'
        && $request['contents'] === ['Dit is een korte test.']);
});

it('keeps the transcription without calling Google for an accent-only change', function () {
    Http::fake();
    $audioFile = audioFileWithTranscription($this->user, 'en-gb', 'en-us');

    ProcessAudioTranslationJob::dispatchSync($audioFile);

    $audioFile->refresh();
    expect($audioFile->status)->toBe('pending_tts_approval')
        ->and($audioFile->translated_text)->toBe('Dit is een korte test.');
    Http::assertNothingSent();
});

it('marks the audio file as failed when Google rejects the translation', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => ['message' => 'Invalid target language.']], 400)]);
    $audioFile = audioFileWithTranscription($this->user, 'nl', 'pl');

    expect(fn () => ProcessAudioTranslationJob::dispatchSync($audioFile))->toThrow(RuntimeException::class);

    $audioFile->refresh();
    expect($audioFile->status)->toBe('failed')
        ->and($audioFile->error_message)->toContain('Invalid target language.');
});

it('translates additional languages from the audio source language', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['translations' => [['translatedText' => 'dette er en kort test.']]])]);
    $this->mock(AudioProcessingService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateAudio')
            ->once()
            ->withArgs(fn ($text, $language) => $text === 'Dette er en kort test.' && $language === 'da')
            ->andReturn('audio/translated-da.mp3');
        $mock->shouldReceive('deductCredits')->once();
    });
    $audioFile = audioFileWithTranscription($this->user, 'nl', 'en', 'completed');
    $translation = AudioTranslation::create([
        'audio_file_id' => $audioFile->id,
        'target_language' => 'da',
        'translated_text' => '',
        'voice' => 'kore',
        'status' => 'pending',
    ]);

    ProcessAdditionalAudioTranslation::dispatchSync($translation);

    $translation->refresh();
    expect($translation->status)->toBe('completed')
        ->and($translation->translated_text)->toBe('Dette er en kort test.');
    Http::assertSent(fn (Request $request) => $request['sourceLanguageCode'] === 'nl'
        && $request['targetLanguageCode'] === 'da');
});

it('shows the CSV translation page when Google Cloud is not configured', function () {
    $this->app->forgetInstance(GoogleOAuthService::class);
    config([
        'services.google_cloud.project_id' => null,
        'services.google_cloud.credentials_path' => storage_path('framework/testing/missing-google-credentials.json'),
    ]);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin.csv-translations.index'))->assertOk();
});

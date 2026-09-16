<?php

use App\Jobs\ProcessAdditionalAudioTranslation;
use App\Jobs\ProcessAudioTTSJob;
use App\Jobs\ProcessTextToAudioJob;
use App\Models\AudioFile;
use App\Models\AudioTranslation;
use App\Models\TextToAudio;
use App\Models\User;
use App\Services\AudioProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mock(AudioProcessingService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateAudio')->andThrow(new RuntimeException('TTS quota exceeded'));
    });
});

it('marks text-to-audio as failed with the original error when audio generation fails', function () {
    $record = TextToAudio::create([
        'user_id' => $this->user->id,
        'text_content' => 'Hallo wereld, dit is een test.',
        'language' => 'nl',
        'voice' => 'kore',
        'status' => 'processing',
    ]);

    expect(fn () => ProcessTextToAudioJob::dispatchSync($record))->toThrow(RuntimeException::class);

    $record->refresh();
    expect($record->status)->toBe('failed')
        ->and($record->error_message)->toBe('TTS quota exceeded');
});

it('marks an audio file as failed with processing details when TTS fails', function () {
    $audioFile = AudioFile::create([
        'user_id' => $this->user->id,
        'original_filename' => 'test.mp3',
        'file_path' => 'audio/test.mp3',
        'file_size' => 1000,
        'source_language' => 'nl',
        'target_language' => 'pl',
        'voice' => 'kore',
        'translated_text' => 'To jest test.',
        'status' => 'generating_audio',
    ]);

    expect(fn () => ProcessAudioTTSJob::dispatchSync($audioFile))->toThrow(RuntimeException::class);

    $audioFile->refresh();
    expect($audioFile->status)->toBe('failed')
        ->and($audioFile->processing_stage)->toBe('failed')
        ->and($audioFile->processing_progress)->toBe(0)
        ->and($audioFile->error_message)->toBe('TTS quota exceeded');
});

it('does not crash when an additional translation was deleted before its failure is handled', function () {
    $audioFile = AudioFile::create([
        'user_id' => $this->user->id,
        'original_filename' => 'test.mp3',
        'file_path' => 'audio/test.mp3',
        'file_size' => 1000,
        'source_language' => 'nl',
        'target_language' => 'pl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);
    $translation = AudioTranslation::create([
        'audio_file_id' => $audioFile->id,
        'target_language' => 'da',
        'translated_text' => '',
        'voice' => 'kore',
        'status' => 'pending',
    ]);
    $job = new ProcessAdditionalAudioTranslation($translation);
    $translation->delete();

    $job->failed(new RuntimeException('boom'));

    expect(AudioTranslation::count())->toBe(0);
});

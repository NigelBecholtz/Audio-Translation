<?php

use App\Jobs\ProcessAdditionalAudioTranslation;
use App\Jobs\ProcessAudioJob;
use App\Jobs\ProcessTextToAudioJob;
use App\Models\AudioFile;
use App\Models\AudioTranslation;
use App\Models\TextToAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();

    $this->user = User::factory()->create([
        'credits' => 100,
        'translations_used' => 0,
        'translations_limit' => 2,
    ]);
});

function completedAudioFileFor(User $user): AudioFile
{
    return AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'test.mp3',
        'file_path' => 'audio/test.mp3',
        'file_size' => 1000,
        'source_language' => 'en',
        'target_language' => 'nl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);
}

it('accepts a new language as source and target for audio uploads', function (string $code) {
    $this->actingAs($this->user)->post(route('audio.store'), [
        'audio' => $this->fakeMp3(),
        'source_language' => $code,
        'target_language' => $code,
        'voice' => 'kore',
    ])->assertSessionHasNoErrors();

    expect(AudioFile::where('source_language', $code)->where('target_language', $code)->exists())->toBeTrue();
    Queue::assertPushed(ProcessAudioJob::class);
})->with('new languages');

it('rejects languages outside the central list for audio uploads', function () {
    $this->actingAs($this->user)->post(route('audio.store'), [
        'audio' => $this->fakeMp3(),
        'source_language' => 'hu',
        'target_language' => 'hu',
        'voice' => 'kore',
    ])->assertSessionHasErrors(['source_language', 'target_language']);
});

it('accepts a new language for text-to-audio', function (string $code) {
    $this->actingAs($this->user)->post(route('text-to-audio.store'), [
        'text_content' => 'Dit is een testtekst voor audio.',
        'language' => $code,
        'voice' => 'kore',
    ])->assertSessionHasNoErrors();

    expect(TextToAudio::where('language', $code)->exists())->toBeTrue();
    Queue::assertPushed(ProcessTextToAudioJob::class);
})->with('new languages');

it('rejects languages outside the central list for text-to-audio', function () {
    $this->actingAs($this->user)->post(route('text-to-audio.store'), [
        'text_content' => 'Dit is een testtekst voor audio.',
        'language' => 'hu',
        'voice' => 'kore',
    ])->assertSessionHasErrors(['language']);
});

it('accepts a new language for additional translations', function (string $code) {
    $audioFile = completedAudioFileFor($this->user);

    $this->actingAs($this->user)->post(route('audio.store-additional-translations', $audioFile->id), [
        'additional_languages' => $code,
        'voice' => 'kore',
    ])->assertSessionHasNoErrors();

    expect(AudioTranslation::where('audio_file_id', $audioFile->id)->where('target_language', $code)->exists())->toBeTrue();
    Queue::assertPushed(ProcessAdditionalAudioTranslation::class);
})->with('new languages');

it('rejects languages outside the central list for additional translations', function () {
    $audioFile = completedAudioFileFor($this->user);

    $this->actingAs($this->user)->post(route('audio.store-additional-translations', $audioFile->id), [
        'additional_languages' => 'hu',
        'voice' => 'kore',
    ])->assertSessionHasErrors(['additional_languages']);
});

<?php

use App\Models\AudioFile;
use App\Models\AudioTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create(['credits' => 5, 'translations_used' => 0, 'translations_limit' => 2]);
});

function makeDetailAudioFile(User $user, array $overrides = []): AudioFile
{
    return AudioFile::create(array_merge([
        'user_id' => $user->id,
        'original_filename' => 'sample.mp3',
        'file_path' => 'audio/sample.mp3',
        'file_size' => 2048,
        'source_language' => 'en',
        'target_language' => 'nl',
        'voice' => 'kore',
        'status' => 'uploaded',
    ], $overrides));
}

it('renders the show page for an uploaded audio file', function () {
    $audioFile = makeDetailAudioFile($this->user);

    $this->actingAs($this->user)
        ->get(route('audio.show', $audioFile->id))
        ->assertOk()
        ->assertViewIs('audio.show')
        ->assertSee('sample.mp3')
        ->assertSee('Pipeline');
});

it('renders the transcript review step for a pending_approval audio file', function () {
    $audioFile = makeDetailAudioFile($this->user, [
        'status' => 'pending_approval',
        'transcription' => 'Hello world, this is the transcript.',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.show', $audioFile->id))
        ->assertOk()
        ->assertSee('name="transcription"', false)
        ->assertSee('Hello world, this is the transcript.')
        ->assertSee('action="'.route('audio.approve-transcription', $audioFile->id).'"', false)
        ->assertSee('Approve transcript');
});

it('renders the translation review step for a pending_tts_approval audio file', function () {
    $audioFile = makeDetailAudioFile($this->user, [
        'status' => 'pending_tts_approval',
        'transcription' => 'Hello world.',
        'translated_text' => 'Hallo wereld.',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.show', $audioFile->id))
        ->assertOk()
        ->assertSee('name="translated_text"', false)
        ->assertSee('Hallo wereld.')
        ->assertSee('action="'.route('audio.approve-tts', $audioFile->id).'"', false)
        ->assertSee('Generate audio');
});

it('renders a completed audio file with an additional translation', function () {
    $audioFile = makeDetailAudioFile($this->user, [
        'status' => 'completed',
        'transcription' => 'Hello world.',
        'translated_text' => 'Hallo wereld.',
        'translated_audio_path' => 'audio/translated.mp3',
    ]);

    AudioTranslation::create([
        'audio_file_id' => $audioFile->id,
        'target_language' => 'fr',
        'translated_text' => 'Bonjour le monde.',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.show', $audioFile->id))
        ->assertOk()
        ->assertSee('Additional translations')
        ->assertSee('Add more languages')
        ->assertSee('Download');
});

it('renders the error message for a failed audio file', function () {
    $audioFile = makeDetailAudioFile($this->user, [
        'status' => 'failed',
        'error_message' => 'Whisper transcription timed out.',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.show', $audioFile->id))
        ->assertOk()
        ->assertSee('Processing failed')
        ->assertSee('Whisper transcription timed out.');
});

it('renders the additional translations page for a completed audio file', function () {
    $audioFile = makeDetailAudioFile($this->user, [
        'status' => 'completed',
        'transcription' => 'Hello world.',
        'translated_text' => 'Hallo wereld.',
        'translated_audio_path' => 'audio/translated.mp3',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.additional-translations', $audioFile->id))
        ->assertOk()
        ->assertViewIs('audio.additional-translations')
        ->assertSee('Add more languages')
        ->assertSee('sample.mp3')
        ->assertSee('name="additional_languages"', false)
        ->assertSee('name="voice"', false)
        ->assertSee('name="style_instruction"', false)
        ->assertDontSee('<option value="en"', false);
});

<?php

use App\Models\AudioFile;
use App\Models\AudioTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks every pipeline step as done for a completed recording', function () {
    $user = User::factory()->create();
    $audioFile = AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'demo.mp3',
        'file_path' => 'audio/missing.mp3',
        'file_size' => 1000,
        'source_language' => 'en-gb',
        'target_language' => 'da',
        'voice' => 'kore',
        'transcription' => 'Our spring collection is built to last.',
        'translated_text' => 'Vores forårskollektion er bygget til at holde.',
        'translated_audio_path' => 'audio/missing-da.mp3',
        'status' => 'completed',
    ]);
    AudioTranslation::create([
        'audio_file_id' => $audioFile->id,
        'target_language' => 'sv',
        'translated_text' => 'Vår vårkollektion är byggd för att hålla.',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    $this->actingAs($user)
        ->get(route('audio.show', $audioFile))
        ->assertOk()
        ->assertDontSee('will generate the translated audio')
        ->assertDontSee('Queued for processing.')
        ->assertSee('Audio is ready to download.');
});

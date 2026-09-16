<?php

use App\Models\AudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the dashboard with translations in different statuses', function () {
    $user = User::factory()->create(['credits' => 5, 'translations_used' => 0, 'translations_limit' => 2]);

    AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'completed.mp3',
        'file_path' => 'audio/completed.mp3',
        'file_size' => 1000,
        'source_language' => 'en',
        'target_language' => 'nl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    AudioFile::create([
        'user_id' => $user->id,
        'original_filename' => 'awaiting-review.mp3',
        'file_path' => 'audio/awaiting-review.mp3',
        'file_size' => 1000,
        'source_language' => 'en',
        'target_language' => 'fr',
        'voice' => 'kore',
        'status' => 'pending_approval',
    ]);

    $this->actingAs($user)
        ->get(route('audio.index'))
        ->assertOk()
        ->assertViewIs('audio.index')
        ->assertSee('completed.mp3')
        ->assertSee('awaiting-review.mp3')
        ->assertSee('Review transcript');
});

it('renders the dashboard empty state when there are no files', function () {
    $user = User::factory()->create(['credits' => 5, 'translations_used' => 0, 'translations_limit' => 2]);

    $this->actingAs($user)
        ->get(route('audio.index'))
        ->assertOk()
        ->assertViewIs('audio.index')
        ->assertSee('No audio translations yet')
        ->assertSee('No text-to-audio yet');
});

it('renders the audio upload form', function () {
    $user = User::factory()->create(['credits' => 5, 'translations_used' => 0, 'translations_limit' => 2]);

    $this->actingAs($user)
        ->get(route('audio.create'))
        ->assertOk()
        ->assertViewIs('audio.create')
        ->assertSee('Translate audio')
        ->assertSee('name="audio"', false)
        ->assertSee('name="source_language"', false)
        ->assertSee('name="target_language"', false)
        ->assertSee('name="voice"', false)
        ->assertSee('name="style_instruction"', false);
});

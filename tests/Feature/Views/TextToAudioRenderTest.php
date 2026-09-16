<?php

use App\Models\TextToAudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'credits' => 100,
        'translations_used' => 0,
        'translations_limit' => 5,
    ]);
});

it('renders the index page with items', function () {
    TextToAudio::create([
        'user_id' => $this->user->id,
        'text_content' => 'Hello world, this is a sample text for the audio conversion list view.',
        'language' => 'nl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    $this->actingAs($this->user)
        ->get(route('text-to-audio.index'))
        ->assertOk()
        ->assertSee('Text to speech')
        ->assertSee('NL')
        ->assertSee('Hello world', false);
});

it('renders the index page when empty', function () {
    $count = count(config('audio.languages'));

    $this->actingAs($this->user)
        ->get(route('text-to-audio.index'))
        ->assertOk()
        ->assertSee('No text to speech yet')
        ->assertSee("{$count} languages");
});

it('renders the create page', function () {
    $this->actingAs($this->user)
        ->get(route('text-to-audio.create'))
        ->assertOk()
        ->assertSee('New text to speech')
        ->assertSee('name="text_content"', false)
        ->assertSee('id="language"', false)
        ->assertSee('id="voice"', false)
        ->assertSee('id="style_instruction"', false);
});

it('renders the show page while processing', function () {
    $item = TextToAudio::create([
        'user_id' => $this->user->id,
        'text_content' => 'Processing text sample.',
        'language' => 'fr',
        'voice' => 'kore',
        'status' => 'processing',
    ]);

    $this->actingAs($this->user)
        ->get(route('text-to-audio.show', $item->id))
        ->assertOk()
        ->assertSee('Processing')
        ->assertSee('id="pollingIndicator"', false);
});

it('renders the show page when completed with audio', function () {
    Storage::fake('public');
    Storage::disk('public')->put('text-to-audio/sample.mp3', 'fake-audio-content');

    $item = TextToAudio::create([
        'user_id' => $this->user->id,
        'text_content' => 'Completed text sample.',
        'language' => 'es',
        'voice' => 'kore',
        'status' => 'completed',
        'audio_path' => 'text-to-audio/sample.mp3',
    ]);

    $this->actingAs($this->user)
        ->get(route('text-to-audio.show', $item->id))
        ->assertOk()
        ->assertSee('Ready')
        ->assertSee('<audio', false)
        ->assertSee(route('text-to-audio.download', $item->id), false);
});

it('renders the show page when failed', function () {
    $item = TextToAudio::create([
        'user_id' => $this->user->id,
        'text_content' => 'Failed text sample.',
        'language' => 'de',
        'voice' => 'kore',
        'status' => 'failed',
        'error_message' => 'Something went wrong generating the audio.',
    ]);

    $this->actingAs($this->user)
        ->get(route('text-to-audio.show', $item->id))
        ->assertOk()
        ->assertSee('Failed')
        ->assertSee('Something went wrong generating the audio.');
});

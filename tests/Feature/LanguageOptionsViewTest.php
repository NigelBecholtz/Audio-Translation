<?php

use App\Models\AudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'credits' => 100,
        'translations_used' => 0,
        'translations_limit' => 2,
    ]);
});

it('shows every configured language in both audio upload dropdowns', function () {
    $response = $this->actingAs($this->user)->get(route('audio.create'))->assertOk();

    foreach (array_keys(config('audio.languages')) as $code) {
        // Source and target select each render the option once.
        expect(substr_count($response->getContent(), 'value="'.$code.'"'))->toBe(2);
    }
    $response->assertDontSee('value="hu"', false);
});

it('shows every configured language in the text-to-audio dropdown', function () {
    $response = $this->actingAs($this->user)->get(route('text-to-audio.create'))->assertOk();

    foreach (array_keys(config('audio.languages')) as $code) {
        $response->assertSee('value="'.$code.'"', false);
    }
    $response->assertDontSee('value="hu"', false);
});

it('keeps the previously chosen language selected after a validation error', function () {
    $this->actingAs($this->user)
        ->withSession(['_old_input' => ['language' => 'pl']])
        ->get(route('text-to-audio.create'))
        ->assertSee('<option value="pl" selected>', false);
});

it('offers new languages for additional translations but hides the source language', function (string $code) {
    $audioFile = AudioFile::create([
        'user_id' => $this->user->id,
        'original_filename' => 'test.mp3',
        'file_path' => 'audio/test.mp3',
        'file_size' => 1000,
        'source_language' => 'en',
        'target_language' => 'nl',
        'voice' => 'kore',
        'status' => 'completed',
    ]);

    $this->actingAs($this->user)
        ->get(route('audio.additional-translations', $audioFile->id))
        ->assertOk()
        ->assertSee('value="'.$code.'"', false)
        ->assertDontSee('<option value="en"', false);
})->with('new languages');

it('lists the new languages and the extended preset order in the CSV admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.csv-translations.index'))->assertOk();

    foreach (['no' => 'Norwegian', 'da' => 'Danish', 'sv' => 'Swedish', 'pl' => 'Polish'] as $code => $name) {
        $response->assertSee('name="languages[]" value="'.$code.'"', false)->assertSee($name);
    }
    $response->assertDontSee('name="languages[]" value="en"', false);

    // Includes the alias headers customer files use (ES_AR, GR, AL).
    $presetOrder = 'EN → ES → ES_AR → DE → FR → IT → NL → RO → EL → GR → SQ → AL → SK → LV → BG → FI → CA → NO → DA → SV → PL';
    expect(substr_count($response->getContent(), $presetOrder))->toBe(2);
});

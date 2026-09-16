<?php

use App\Models\StyleInstructionPreset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the credits page', function () {
    $user = User::factory()->create(['credits' => 5, 'translations_used' => 0, 'translations_limit' => 2]);

    $this->actingAs($user)
        ->get(route('payment.credits'))
        ->assertOk()
        ->assertSee('Credits')
        ->assertSee('Buy');
});

it('renders the voice styles index with no presets', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('style-presets.index'))
        ->assertOk()
        ->assertSee('No voice styles yet');
});

it('renders the voice styles index with a preset', function () {
    $user = User::factory()->create();
    StyleInstructionPreset::create([
        'user_id' => $user->id,
        'name' => 'Calm narrator',
        'instruction' => 'Calm and warm, slow pace.',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->get(route('style-presets.index'))
        ->assertOk()
        ->assertSee('Calm narrator')
        ->assertSee('Calm and warm, slow pace.');
});

it('renders the voice style create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('style-presets.create'))
        ->assertOk()
        ->assertSee('New voice style');
});

it('renders the voice style edit form', function () {
    $user = User::factory()->create();
    $preset = StyleInstructionPreset::create([
        'user_id' => $user->id,
        'name' => 'Calm narrator',
        'instruction' => 'Calm and warm, slow pace.',
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->get(route('style-presets.edit', $preset->id))
        ->assertOk()
        ->assertSee('Edit voice style')
        ->assertSee('Calm narrator');
});

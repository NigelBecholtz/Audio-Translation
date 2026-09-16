<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the configured language count on public pages', function (string $url) {
    $count = count(config('audio.languages'));

    $response = $this->get($url)->assertOk();

    expect($response->getContent())->toMatch("/{$count} [Ll]anguages/")
        ->not->toMatch('/(22|50)\+? ([Ll]anguages|options)/');
})->with(['/', '/login', '/register']);

it('shows the configured language count on the welcome page', function () {
    $count = count(config('audio.languages'));

    $response = $this->get('/')->assertOk();

    $response->assertSee("from {$count} options");

    $text = preg_replace('/\s+/', ' ', strip_tags($response->getContent()));

    expect($text)->toContain("{$count} Languages")
        ->not->toMatch('/\b(22|50)\+? ([Ll]anguages|options)\b/');
});

it('shows the configured language count on text-to-audio pages', function (string $route) {
    $user = User::factory()->create(['credits' => 100, 'translations_used' => 0, 'translations_limit' => 2]);
    $count = count(config('audio.languages'));

    $this->actingAs($user)->get(route($route))
        ->assertOk()
        ->assertSee("{$count} languages")
        ->assertDontSee('50+ languages')
        ->assertDontSee('22 languages');
})->with(['text-to-audio.create', 'text-to-audio.index']);

it('shows the configured language count on the credits page', function () {
    $user = User::factory()->create(['credits' => 100, 'translations_used' => 0, 'translations_limit' => 2]);
    $count = count(config('audio.languages'));

    $this->actingAs($user)->get(route('payment.credits'))
        ->assertOk()
        ->assertSee("{$count} supported languages")
        ->assertDontSee('50+');
});

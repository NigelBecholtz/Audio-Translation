<?php

use App\Models\CsvTranslationJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('renders the file translation status page while processing', function () {
    $job = CsvTranslationJob::create([
        'user_id' => $this->admin->id,
        'original_filename' => 'phrases.csv',
        'file_path' => 'temp/phrases.csv',
        'status' => 'processing',
        'total_items' => 100,
        'processed_items' => 40,
        'failed_items' => 0,
        'target_languages' => ['es', 'fr'],
        'use_smart_fallback' => false,
        'started_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.csv-translations.status', $job))
        ->assertOk()
        ->assertSee('phrases.csv')
        ->assertSee('Processing')
        ->assertSee('es')
        ->assertSee('fr');
});

it('renders the file translation status page when completed', function () {
    $job = CsvTranslationJob::create([
        'user_id' => $this->admin->id,
        'original_filename' => 'phrases.csv',
        'file_path' => 'temp/phrases.csv',
        'output_path' => 'translated/phrases.xlsx',
        'status' => 'completed',
        'total_items' => 100,
        'processed_items' => 100,
        'failed_items' => 0,
        'target_languages' => ['es', 'fr'],
        'use_smart_fallback' => false,
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.csv-translations.status', $job))
        ->assertOk()
        ->assertSee('phrases.csv')
        ->assertSee('Download translated file')
        ->assertSee(route('admin.csv-translations.download', $job->id), false);
});

it('renders the file translation status page when failed', function () {
    $job = CsvTranslationJob::create([
        'user_id' => $this->admin->id,
        'original_filename' => 'phrases.csv',
        'file_path' => 'temp/phrases.csv',
        'status' => 'failed',
        'total_items' => 100,
        'processed_items' => 12,
        'failed_items' => 3,
        'target_languages' => ['es', 'fr'],
        'use_smart_fallback' => true,
        'error_message' => 'Google Translate API quota exceeded',
        'started_at' => now()->subMinutes(2),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.csv-translations.status', $job))
        ->assertOk()
        ->assertSee('phrases.csv')
        ->assertSee('Translation failed')
        ->assertSee('Google Translate API quota exceeded')
        ->assertSee('Smart fallback');
});

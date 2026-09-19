<?php

use App\Jobs\ProcessCsvTranslationJob;
use App\Models\CsvTranslationJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesGoogleOAuth;

uses(RefreshDatabase::class, FakesGoogleOAuth::class);

/**
 * Customer files use alternative column headers (es_AR, gr, al) instead of the
 * official ISO codes (es, el, sq). They must be selectable and must keep their
 * original spelling in the exported file.
 */
beforeEach(function () {
    $this->fakeGoogleOAuth();
});

function runCsvJob(string $csv, ?array $targetLanguages): array
{
    $dir = storage_path('app/public/temp');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'test_'.uniqid().'.csv';
    file_put_contents($dir.'/'.$name, $csv);

    $job = CsvTranslationJob::create([
        'user_id' => User::factory()->create()->id,
        'original_filename' => $name,
        'file_path' => 'temp/'.$name,
        'status' => 'pending',
        'target_languages' => $targetLanguages,
        'use_smart_fallback' => false,
    ]);

    ProcessCsvTranslationJob::dispatchSync($job);

    $output = storage_path('app/public/'.$job->fresh()->output_path);
    $contents = file_get_contents($output);
    @unlink($output);

    return ['job' => $job->fresh(), 'output' => $contents];
}

it('offers the alternative column headers in the CSV language picker', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.csv-translations.index'))->assertOk();

    foreach (['es_AR', 'gr', 'al'] as $code) {
        $response->assertSee('name="languages[]" value="'.$code.'"', false);
    }
});

it('translates selected alternative headers and keeps their spelling in the export', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['translations' => [['translatedText' => 'vertaald']]])]);

    $result = runCsvJob(
        "key,en,es_AR,fr,gr,al\nproven_funds,\"Proven funds\",,,,\n",
        ['es_AR', 'fr', 'gr', 'al'],
    );

    expect($result['job']->status)->toBe('completed')
        ->and($result['output'])->toContain('key,en,es_AR,fr,gr,al')
        ->and($result['output'])->toContain('proven_funds,"Proven funds",Vertaald,Vertaald,Vertaald,Vertaald');

    $targets = [];
    Http::assertSent(function (Request $request) use (&$targets) {
        $targets[] = $request['targetLanguageCode'];

        return true;
    });
    expect($targets)->toEqualCanonicalizing(['es-AR', 'fr', 'el', 'sq']);
});

it('only translates columns that exist in the file', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['translations' => [['translatedText' => 'vertaald']]])]);

    // 'de' is selected but has no column; the trailing empty headers are no languages.
    $result = runCsvJob(
        "key,en,fr,,\nproven_funds,\"Proven funds\",,,\n",
        ['fr', 'de'],
    );

    expect($result['output'])->toContain('proven_funds,"Proven funds",Vertaald');

    $targets = [];
    Http::assertSent(function (Request $request) use (&$targets) {
        $targets[] = $request['targetLanguageCode'];

        return true;
    });
    expect($targets)->toBe(['fr']);
});

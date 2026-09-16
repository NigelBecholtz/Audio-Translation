# Talen centraliseren + NO/DA/SV/PL Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Noors, Deens, Zweeds en Pools overal beschikbaar maken, met `config/audio.php` als enige bron van taallijsten.

**Architecture:** `config/audio.php` krijgt `languages` (30 talen) en `csv_preset_languages`. FormRequests, `AudioController` en `LanguageDetectionService` lezen daaruit. Eén Blade-partial rendert de `<option>`s voor alle taaldropdowns; de CSV-admin en taaltellingen in copy worden afgeleid uit dezelfde config.

**Tech Stack:** Laravel 12, PHP 8.2+, Pest 4 (tests in PHPUnit-klassestijl en Pest-functiestijl beide aanwezig), SQLite in-memory voor tests, Blade.

**Spec:** `docs/superpowers/specs/2026-09-15-talen-centraliseren-design.md`

## Global Constraints

- Taalcodes: `no`, `da`, `sv`, `pl` (niet `nb`, `dk`).
- `config('audio.languages')` bevat exact 30 entries: de 27 bestaande + `no` => `Norwegian`, `da` => `Danish`, `pl` => `Polish`.
- CSV-preset = bestaande volgorde `en, es, es_AR, de, fr, it, nl, ro, el, gr, sq, al, sk, lv, bg, fi, ca` + `no, da, sv, pl` achteraan.
- Geen styling-, inline-style- of JS-wijzigingen aan views buiten het vervangen van taallijsten en taaltellingen.
- **Geen git**: de repo is momenteel onleesbaar door OneDrive (`.git/packed-refs`). Sla alle commit-stappen over tot de gebruiker anders aangeeft.
- Tests draaien met `php artisan test` (volledige suite duurt ~80s) of `php artisan test --filter=<naam>`.
- Shell is PowerShell op Windows; gebruik `php vendor/bin/pint` in plaats van `./vendor/bin/pint`.

---

### Task 1: Bestaande testsuite groen maken

Nulmeting: 9 van 18 tests falen vóór enige wijziging. Zonder groene basis kan niet worden aangetoond dat de talenwijziging niets breekt.

**Files:**
- Modify: `tests/TestCase.php`
- Modify: `tests/Feature/AudioUploadTest.php`
- Modify: `tests/Feature/AuthorizationTest.php`
- Modify: `app/Http/Controllers/AudioController.php:105-125` (`destroy`)

**Interfaces:**
- Produces: `Tests\TestCase::fakeMp3(string $name = 'test.mp3'): \Illuminate\Http\UploadedFile` — een upload die door `mimes:mp3` én de finfo-controle in `StoreAudioRequest` komt. `Tests\TestCase::setUp()` roept `withoutVite()` aan voor alle feature-tests.

- [ ] **Step 1: Bevestig de nulmeting**

Run: `php artisan test`
Expected: `Tests: 9 failed, 9 passed`. Falend: AudioUploadTest (index page, upload, own files), AuthorizationTest (view, download, delete, admin dashboard), ExampleTest, PaymentTest (credits page).

- [ ] **Step 2: `tests/TestCase.php` vervangen**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests render Blade layouts without a Vite build present.
        $this->withoutVite();
    }

    /**
     * An upload with real MPEG frame headers, so finfo reports audio/mpeg.
     * UploadedFile::fake()->create() produces an empty file (application/x-empty).
     */
    protected function fakeMp3(string $name = 'test.mp3'): UploadedFile
    {
        $frame = "\xFF\xFB\x90\x64" . str_repeat("\x00", 413);

        return UploadedFile::fake()->createWithContent($name, "ID3\x03\x00\x00\x00\x00\x00\x00" . str_repeat($frame, 20));
    }
}
```

- [ ] **Step 3: `AudioUploadTest` laten uploaden met geldige inhoud**

In `tests/Feature/AudioUploadTest.php` in **alle drie** de tests die `UploadedFile::fake()->create('test.mp3', 1000)` gebruiken (`test_user_can_upload_audio_file_with_free_translations`, `test_user_cannot_upload_without_credits`, `test_validation_fails_with_invalid_languages`) vervangen:

```php
        $file = UploadedFile::fake()->create('test.mp3', 1000);
```

door:

```php
        $file = $this->fakeMp3();
```

En in `test_user_can_only_view_their_own_audio_files` vervangen:

```php
        // User2 tries to access User1's file
        $response = $this->actingAs($user2)->get(route('audio.show', $audioFile->id));

        $response->assertForbidden();
```

door:

```php
        // User2 tries to access User1's file; scoped lookup hides its existence
        $response = $this->actingAs($user2)->get(route('audio.show', $audioFile->id));

        $response->assertNotFound();
```

- [ ] **Step 4: `AuthorizationTest` afstemmen op scoped lookups (404)**

In `tests/Feature/AuthorizationTest.php`:
- `test_user_cannot_view_another_users_audio_file`: `$response->assertForbidden();` → `$response->assertNotFound();`
- `test_user_cannot_download_another_users_audio_file`: `$response->assertForbidden();` → `$response->assertNotFound();`
- `test_user_cannot_delete_another_users_audio_file`: `$response->assertForbidden();` → `$response->assertNotFound();` (de `assertDatabaseHas` eronder blijft staan)

- [ ] **Step 5: Draai de suite, verwacht nog 1 falende test**

Run: `php artisan test`
Expected: alleen `AuthorizationTest > user cannot delete another users audio file` faalt met `Failed asserting that 302 is identical to 404` — `destroy` vangt de `ModelNotFoundException` af.

- [ ] **Step 6: `AudioController::destroy` — lookup buiten de try**

Vervang in `app/Http/Controllers/AudioController.php`:

```php
    public function destroy($id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);
            
            // Delete audio files using trait
```

door:

```php
    public function destroy($id)
    {
        // Use user's audioFiles relationship for automatic authorization (404 for other users' files)
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);

        try {
            // Delete audio files using trait
```

- [ ] **Step 7: Volledige suite groen**

Run: `php artisan test`
Expected: `Tests: 18 passed`. Faalt er iets anders, stop en onderzoek (superpowers:systematic-debugging) voordat je verdergaat.

---

### Task 2: Centrale taalconfig + backend-validatie

**Files:**
- Modify: `config/audio.php`
- Modify: `config/gemini.php:31-85`
- Modify: `app/Services/LanguageDetectionService.php:112-139`
- Modify: `app/Http/Requests/StoreAudioRequest.php:25-26, 67-78, 151-167`
- Modify: `app/Http/Requests/StoreTextToAudioRequest.php:25, 34-38`
- Modify: `app/Http/Controllers/AudioController.php:16, 393, 417-421`
- Modify: `tests/Pest.php` (gedeelde dataset `new languages`)
- Create: `tests/Feature/LanguageConfigTest.php`
- Create: `tests/Feature/LanguageValidationTest.php`

**Interfaces:**
- Consumes: `Tests\TestCase::fakeMp3()` (Task 1).
- Produces: `config('audio.languages')` — `array<string,string>` code → Engelse naam, 30 entries. `config('audio.csv_preset_languages')` — `list<string>`. `LanguageDetectionService::getPresetLanguages(): array` retourneert die config. Sleutels `audio.available_languages` en `audio.language_codes` bestaan niet meer.

- [ ] **Step 1: Schrijf de config-tests**

Create `tests/Feature/LanguageConfigTest.php`:

Voeg eerst onderaan `tests/Pest.php` de gedeelde dataset toe (Pest staat geen twee datasets met dezelfde naam toe, en Task 3 gebruikt hem ook):

```php
dataset('new languages', ['no', 'da', 'sv', 'pl']);
```

```php
<?php

use App\Services\LanguageDetectionService;

it('lists 30 languages including the Nordic languages and Polish', function () {
    $languages = config('audio.languages');

    expect($languages)->toHaveCount(30)
        ->and($languages)->toHaveKeys(['no', 'da', 'sv', 'pl'])
        ->and($languages['no'])->toBe('Norwegian')
        ->and($languages['da'])->toBe('Danish')
        ->and($languages['sv'])->toBe('Swedish')
        ->and($languages['pl'])->toBe('Polish');
});

it('no longer exposes the duplicated language keys', function () {
    expect(config('audio.available_languages'))->toBeNull()
        ->and(config('audio.language_codes'))->toBeNull();
});

it('appends the new languages to the CSV preset without reordering it', function () {
    $preset = app(LanguageDetectionService::class)->getPresetLanguages();

    expect($preset)->toBe([
        'en', 'es', 'es_AR', 'de', 'fr', 'it', 'nl', 'ro', 'el', 'gr', 'sq', 'al', 'sk', 'lv', 'bg', 'fi', 'ca',
        'no', 'da', 'sv', 'pl',
    ]);
});

it('has a Gemini voice and supported-language entry for every language', function () {
    $voiceMapping = config('gemini.tts.voice_mapping');
    $supported = config('gemini.tts.supported_languages');

    foreach (array_keys(config('audio.languages')) as $code) {
        expect($voiceMapping)->toHaveKey($code);
    }

    expect($supported)->toHaveKeys(['no', 'da', 'sv', 'pl']);
});
```

- [ ] **Step 2: Schrijf de validatietests**

Create `tests/Feature/LanguageValidationTest.php`:

```php
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
```

- [ ] **Step 3: Draai de nieuwe tests, verwacht falen**

Run: `php artisan test tests/Feature/LanguageConfigTest.php tests/Feature/LanguageValidationTest.php`
Expected: FAIL — o.a. `toHaveCount(30)` (is 27), preset mist `no/da/sv/pl`, `no/da/pl` bij additional translations geweigerd, `hu` bij audio-upload en text-to-audio geaccepteerd (staat nog in `language_codes`).

- [ ] **Step 4: `config/audio.php` aanpassen**

In `$defaultLanguages` na `'sv' => 'Swedish',` toevoegen:

```php
    'no' => 'Norwegian',
    'da' => 'Danish',
    'pl' => 'Polish',
```

Vervang het blok:

```php
    'languages' => $defaultLanguages,
    'available_languages' => $defaultLanguages,
    'available_voices' => $defaultVoices,

    'language_codes' => 'en-us,en-gb,en-au,en-ca,en-in,en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
```

door:

```php
    // Single source of truth for every language dropdown and validation rule.
    'languages' => $defaultLanguages,
    'available_voices' => $defaultVoices,

    // Fixed target order for CSV smart-fallback translation (alternative codes es_AR, gr, al included).
    'csv_preset_languages' => [
        'en', 'es', 'es_AR', 'de', 'fr', 'it', 'nl', 'ro', 'el', 'gr', 'sq', 'al', 'sk', 'lv', 'bg', 'fi', 'ca',
        'no', 'da', 'sv', 'pl',
    ],
```

- [ ] **Step 5: `config/gemini.php` aanvullen**

In `tts.supported_languages` na `'sv' => 'Swedish',` toevoegen:

```php
            'no' => 'Norwegian',
            'da' => 'Danish',
            'pl' => 'Polish',
```

In `tts.voice_mapping` na `'sv' => 'Aoede',` toevoegen:

```php
            'no' => 'Aoede',
            'da' => 'Aoede',
            'pl' => 'Aoede',
```

- [ ] **Step 6: `LanguageDetectionService::getPresetLanguages()` uit config**

Vervang in `app/Services/LanguageDetectionService.php` de docblock en methode (regels 112-139):

```php
    /**
     * Get preset language configuration
     *
     * @return array Array of language codes in specific order
     */
    public function getPresetLanguages(): array
    {
        return config('audio.csv_preset_languages');
    }
```

- [ ] **Step 7: FormRequests op de centrale lijst**

`app/Http/Requests/StoreAudioRequest.php`:
- Regel 26: `$languageCodes = explode(',', config('audio.language_codes'));` → `$languageCodes = array_keys(config('audio.languages'));`
- Verwijder de ongebruikte private methode `getBaseLanguageCode()` inclusief docblock (regels 151-167); de klasse eindigt dan na `failedAuthorization()`.

`app/Http/Requests/StoreTextToAudioRequest.php`:
- Regel 25: `$languageCodes = explode(',', config('audio.language_codes'));` → `$languageCodes = array_keys(config('audio.languages'));`

- [ ] **Step 8: `AudioController` op de centrale lijst**

In de imports na `use Illuminate\Http\Request;` toevoegen:

```php
use Illuminate\Validation\Rule;
```

In `showAdditionalTranslations` (regel 393): `collect(config('audio.available_languages'))` → `collect(config('audio.languages'))`.

In `storeAdditionalTranslations` vervang:

```php
            'additional_languages' => 'required|string|in:' . implode(',', array_keys(config('audio.available_languages'))),
```

door:

```php
            'additional_languages' => ['required', 'string', Rule::in(array_keys(config('audio.languages')))],
```

- [ ] **Step 9: Controleer dat nergens nog oude sleutels gebruikt worden**

Run (Grep-tool, niet shell): patroon `available_languages|language_codes` in `app`, `resources`, `config`, `routes`, `tests`.
Expected: alleen treffers in `tests/Feature/LanguageConfigTest.php`.

- [ ] **Step 10: Nieuwe tests groen**

Run: `php artisan test tests/Feature/LanguageConfigTest.php tests/Feature/LanguageValidationTest.php`
Expected: alle tests PASS (4 config + 15 validatie = 19).

- [ ] **Step 11: Volledige suite groen**

Run: `php artisan test`
Expected: 0 failed.

---

### Task 3: Eén partial voor taaldropdowns + CSV-admin uit config

**Files:**
- Create: `resources/views/partials/language-options.blade.php`
- Modify: `resources/views/audio/create.blade.php:72-136, 147-211`
- Modify: `resources/views/audio/additional-translations.blade.php:93-157`
- Modify: `resources/views/text-to-audio/create.blade.php:73-151`
- Modify: `resources/views/admin/csv-translations/index.blade.php:84-91, 159, 185`
- Create: `tests/Feature/LanguageOptionsViewTest.php`

**Interfaces:**
- Consumes: `config('audio.languages')`, `config('audio.csv_preset_languages')` (Task 2); `$availableLanguages` (Collection code → naam) uit `AudioController::showAdditionalTranslations`.
- Produces: `@include('partials.language-options', ['selectedLanguage' => ?string, 'languageOptions' => ?iterable])` — rendert alleen `<optgroup>`/`<option>`-elementen, geen `<select>`.

- [ ] **Step 1: Schrijf de view-tests**

Create `tests/Feature/LanguageOptionsViewTest.php`:

```php
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
        expect(substr_count($response->getContent(), 'value="' . $code . '"'))->toBe(2);
    }
    $response->assertDontSee('value="hu"', false);
});

it('shows every configured language in the text-to-audio dropdown', function () {
    $response = $this->actingAs($this->user)->get(route('text-to-audio.create'))->assertOk();

    foreach (array_keys(config('audio.languages')) as $code) {
        $response->assertSee('value="' . $code . '"', false);
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
        ->assertSee('value="' . $code . '"', false)
        ->assertDontSee('<option value="en"', false);
})->with('new languages');

it('lists the new languages and the extended preset order in the CSV admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get(route('admin.csv-translations.index'))->assertOk();

    foreach (['no' => 'Norwegian', 'da' => 'Danish', 'sv' => 'Swedish', 'pl' => 'Polish'] as $code => $name) {
        $response->assertSee('name="languages[]" value="' . $code . '"', false)->assertSee($name);
    }
    $response->assertDontSee('name="languages[]" value="en"', false);

    $presetOrder = 'EN → ES → DE → FR → IT → NL → RO → EL → SQ → SK → LV → BG → FI → CA → NO → DA → SV → PL';
    expect(substr_count($response->getContent(), $presetOrder))->toBe(2);
});
```

- [ ] **Step 2: Draai de tests, verwacht falen**

Run: `php artisan test tests/Feature/LanguageOptionsViewTest.php`
Expected: FAIL — `hu` staat nog in de hardcoded dropdowns, `no/da/pl` ontbreken bij additional translations (niet in oude config), CSV-admin mist de nieuwe checkboxes en preset-tekst, `<option value="pl" selected>` niet gevonden (oude markup `selected` met spaties).

- [ ] **Step 3: Maak de partial**

Create `resources/views/partials/language-options.blade.php`:

```blade
{{--
    Renders <option>s for a language <select>.
    @param string|null   $selectedLanguage  code to mark as selected
    @param iterable|null $languageOptions   code => name; defaults to config('audio.languages')
--}}
@php
    $languageOptions = collect($languageOptions ?? config('audio.languages'));
    $selectedLanguage = $selectedLanguage ?? null;
    $englishLanguages = $languageOptions->filter(fn ($name, $code) => str_starts_with($code, 'en'));
    $otherLanguages = $languageOptions->diffKeys($englishLanguages)->sort();
@endphp
@foreach ([__('English') => $englishLanguages, __('Other languages') => $otherLanguages] as $groupLabel => $group)
    @if ($group->isNotEmpty())
        <optgroup label="{{ $groupLabel }}">
            @foreach ($group as $code => $name)
                <option value="{{ $code }}"{{ $selectedLanguage === $code ? ' selected' : '' }}>{{ $name }}</option>
            @endforeach
        </optgroup>
    @endif
@endforeach
```

- [ ] **Step 4: `audio/create.blade.php`**

Vervang in de bron-select alles tussen `<option value="">{{ __('Select source...') }}</option>` en `</select>` (regels 72-136, de zes `<optgroup>`-blokken) door:

```blade
                            @include('partials.language-options', ['selectedLanguage' => old('source_language')])
```

Vervang in de doel-select alles tussen `<option value="">{{ __('Select target...') }}</option>` en `</select>` (regels 147-211) door:

```blade
                            @include('partials.language-options', ['selectedLanguage' => old('target_language')])
```

- [ ] **Step 5: `audio/additional-translations.blade.php`**

Vervang alles tussen `<option value="">{{ __('Select a language...') }}</option>` en `</select>` (regels 93-157) door:

```blade
                        @include('partials.language-options', ['languageOptions' => $availableLanguages, 'selectedLanguage' => old('additional_languages')])
```

- [ ] **Step 6: `text-to-audio/create.blade.php`**

Vervang alles tussen `<option value="">Select language</option>` en `</select>` (regels 73-151) door:

```blade
                                @include('partials.language-options', ['selectedLanguage' => old('language')])
```

- [ ] **Step 7: `admin/csv-translations/index.blade.php`**

Vervang het `@php`-blok (regels 84-91):

```blade
                            @php
                            $langs = [
                                'es' => 'Spanish', 'de' => 'German', 'fr' => 'French', 'it' => 'Italian',
                                'nl' => 'Dutch', 'ro' => 'Romanian', 'el' => 'Greek', 'sq' => 'Albanian',
                                'sk' => 'Slovak', 'lv' => 'Latvian', 'bg' => 'Bulgarian', 'fi' => 'Finnish',
                                'ca' => 'Catalan',
                            ];
                            @endphp
```

door:

```blade
                            @php
                            $languageNames = config('audio.languages');
                            $presetCodes = collect(config('audio.csv_preset_languages'))->filter(fn ($code) => isset($languageNames[$code]));
                            $langs = $presetCodes->reject(fn ($code) => $code === 'en')
                                ->mapWithKeys(fn ($code) => [$code => $languageNames[$code]])
                                ->all();
                            $presetOrder = $presetCodes->map(fn ($code) => strtoupper($code))->implode(' → ');
                            @endphp
```

Vervang op **beide** plekken (regel 159 en 185) de tekst `EN → ES → DE → FR → IT → NL → RO → EL → SQ → SK → LV → BG → FI → CA` door `{{ $presetOrder }}`. Laat de omringende `<p>`/`<strong>`-markup ongewijzigd.

- [ ] **Step 8: View-tests groen**

Run: `php artisan test tests/Feature/LanguageOptionsViewTest.php`
Expected: 8 PASS (audio-create 1, text-to-audio 1, old-input 1, additional translations 4 via dataset, CSV-admin 1).

- [ ] **Step 9: Geen hardcoded taallijsten meer in views**

Run (Grep-tool): patroon `value="(hu|cs|th|sw)"` in `resources/views`.
Expected: geen treffers.

- [ ] **Step 10: Volledige suite groen**

Run: `php artisan test`
Expected: 0 failed.

---

### Task 4: Taaltellingen in copy uit config

**Files:**
- Modify: `resources/views/welcome.blade.php:212, 249, 276`
- Modify: `resources/views/auth/login.blade.php:39`
- Modify: `resources/views/auth/register.blade.php:39`
- Modify: `resources/views/text-to-audio/index.blade.php:130`
- Modify: `resources/views/text-to-audio/create.blade.php` (regel met `'Select from 50+ languages and 30 AI voices'`)
- Create: `tests/Feature/LanguageCountCopyTest.php`

**Interfaces:**
- Consumes: `config('audio.languages')` (Task 2).

- [ ] **Step 1: Schrijf de test**

Create `tests/Feature/LanguageCountCopyTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the configured language count on public pages', function (string $url) {
    $count = count(config('audio.languages'));

    $response = $this->get($url)->assertOk();

    expect($response->getContent())->toMatch("/{$count} [Ll]anguages/")
        ->not->toMatch('/(22|50\+) [Ll]anguages/');
})->with(['/', '/login', '/register']);

it('shows the configured language count on text-to-audio pages', function (string $route) {
    $user = User::factory()->create(['credits' => 100, 'translations_used' => 0, 'translations_limit' => 2]);
    $count = count(config('audio.languages'));

    $this->actingAs($user)->get(route($route))
        ->assertOk()
        ->assertSee("{$count} languages")
        ->assertDontSee('50+ languages')
        ->assertDontSee('22 languages');
})->with(['text-to-audio.create', 'text-to-audio.index']);
```

- [ ] **Step 2: Draai de test, verwacht falen**

Run: `php artisan test tests/Feature/LanguageCountCopyTest.php`
Expected: FAIL — pagina's bevatten `22 Languages` of `50+ languages`. (`text-to-audio.index` toont de `22 languages`-tekst alleen in de lege staat; een nieuwe user heeft geen records, dus die staat wordt getoond.)

- [ ] **Step 3: Copy aanpassen**

`resources/views/welcome.blade.php`:
- Regel 212: `'title'=>'22 Languages'` → `'title'=>count(config('audio.languages')) . ' Languages'`
- Regel 249: `'All 22 languages'` → `'All ' . count(config('audio.languages')) . ' languages'`
- Regel 276: `'All 22 languages'` → `'All ' . count(config('audio.languages')) . ' languages'`

`resources/views/auth/login.blade.php` regel 39: `>50+ Languages</p>` → `>{{ count(config('audio.languages')) }} Languages</p>`

`resources/views/auth/register.blade.php` regel 39: `'text' => 'Access to all 50+ languages'` → `'text' => 'Access to all ' . count(config('audio.languages')) . ' languages'`

`resources/views/text-to-audio/index.blade.php` regel 130: `and 22 languages.` → `and {{ count(config('audio.languages')) }} languages.`

`resources/views/text-to-audio/create.blade.php`: `'desc' => 'Select from 50+ languages and 30 AI voices'` → `'desc' => 'Select from ' . count(config('audio.languages')) . ' languages and 30 AI voices'`

- [ ] **Step 4: Test groen**

Run: `php artisan test tests/Feature/LanguageCountCopyTest.php`
Expected: 5 PASS. Faalt `text-to-audio.index` omdat de empty-state-tekst niet rendert, lees `resources/views/text-to-audio/index.blade.php` rond regel 120-130 en pas alleen de test aan zodat die de pagina in de lege staat bevraagt; wijzig de view-logica niet.

- [ ] **Step 5: Volledige suite groen**

Run: `php artisan test`
Expected: 0 failed.

---

### Task 5: Volledige verificatie

Geen codewijzigingen tenzij een verificatiestap een fout vindt; dan eerst superpowers:systematic-debugging.

**Files:** geen (alleen bij gevonden fouten).

- [ ] **Step 1: Pint op gewijzigde PHP-bestanden**

Run: `php vendor/bin/pint config/audio.php config/gemini.php app/Services/LanguageDetectionService.php app/Http/Requests/StoreAudioRequest.php app/Http/Requests/StoreTextToAudioRequest.php app/Http/Controllers/AudioController.php tests/TestCase.php tests/Feature/AudioUploadTest.php tests/Feature/AuthorizationTest.php tests/Feature/LanguageConfigTest.php tests/Feature/LanguageValidationTest.php tests/Feature/LanguageOptionsViewTest.php tests/Feature/LanguageCountCopyTest.php`
Expected: bestanden geformatteerd zonder fouten. Draai daarna opnieuw `php artisan test` (Pint kan whitespace wijzigen).

- [ ] **Step 2: Config- en view-cache leeg + suite**

Run: `composer run test`
Expected: `config:clear` ok, 0 failed. Noteer het exacte aantal geslaagde tests.

- [ ] **Step 3: Frontend build + dev-stack**

Run: `npm run build` (verwacht succes), daarna in de achtergrond `php artisan serve` en `php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3`. Controleer `.env` op `OPENAI_API_KEY`, `GEMINI_API_KEY` en `storage/app/google-service-account.json` (alleen aanwezigheid, waarden niet tonen).

- [ ] **Step 4: Browsercontrole (Playwright)**

Log in met een testgebruiker met credits (maak aan via `php artisan tinker` indien nodig; admin via `$user->setAdmin(true)`). Controleer per pagina: HTTP 200, geen console-errors, screenshot.
- `/audio/create`: beide dropdowns tonen groepen "English" (6) en "Other languages" (24) met Norwegian, Danish, Swedish, Polish.
- `/text-to-audio/create`: idem, één dropdown.
- `/audio/{id}/additional-translations` voor een voltooid bestand: brontaal ontbreekt, nieuwe talen aanwezig.
- `/admin/csv-translations`: 17 checkboxes incl. NO/DA/SV/PL; preset-regel eindigt op `CA → NO → DA → SV → PL` (2×).
- `/`, `/login`, `/register`: "30 Languages"/"30 languages", geen "22" of "50+".
- Formuliervalidatie: text-to-audio indienen met te korte tekst en taal Polish → foutmelding verschijnt en Polish blijft geselecteerd.

- [ ] **Step 5: End-to-end met echte API's (alleen als keys uit Step 3 aanwezig zijn)**

- Text-to-audio: korte tekst in het Noors, taal Norwegian → wacht op `completed`, speel de audio af / controleer dat het bestand > 0 bytes is.
- Audio-vertaling: upload een kort audiobestand, bron English → doel Polish → keur transcriptie en vertaling goed → `completed`, audio aanwezig.
- Extra vertaling op dat bestand: Danish → `completed`.
- CSV-admin: upload een CSV met header `en;no;da;sv;pl` en 3 rijen met lege doelcellen → status `completed`, download bevat gevulde NO/DA/SV/PL-kolommen.
- Controleer `storage/logs/laravel.log` op errors tijdens deze runs.

Als keys ontbreken: sla deze stap over en meld dat expliciet aan de gebruiker als niet-uitgevoerd.

- [ ] **Step 6: Rapportage**

Meld aan de gebruiker: aantal tests geslaagd/gefaald (exacte output), welke browserchecks en E2E-runs zijn uitgevoerd met resultaat, en wat eventueel is overgeslagen en waarom. Stop de achtergrondprocessen.

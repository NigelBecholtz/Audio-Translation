# Talen centraliseren + NO/DA/SV/PL toevoegen — Design

**Datum:** 2026-09-15
**Deelproject:** 1 van 3 (talen → code opschonen → UI-redesign)
**Status:** Goedgekeurd in chat, wacht op review van dit document

## Doel

Noors (`no`), Deens (`da`), Zweeds (`sv`) en Pools (`pl`) beschikbaar maken in alle onderdelen van de applicatie, en tegelijk de taallijsten die nu op minstens zes plekken verschillend hardcoded staan terugbrengen tot één bron van waarheid.

## Uitgangssituatie

- `config/audio.php` bevat `$defaultLanguages` (27 talen, `sv` aanwezig, `no`/`da`/`pl` ontbreken), een dubbele sleutel `available_languages` en een losse `language_codes`-string met ~70 codes die voor validatie wordt gebruikt.
- `config/gemini.php` bevat `tts.supported_languages` en `tts.voice_mapping` zonder `no`/`da`/`pl`.
- `LanguageDetectionService::getPresetLanguages()` bevat een hardcoded preset-volgorde voor CSV-bulkvertaling (gebruikt door `ProcessCsvTranslationJob:259`).
- Hardcoded taallijsten in views: `audio/create.blade.php` (bron + doel, 2×), `audio/additional-translations.blade.php`, `text-to-audio/create.blade.php` (afwijkende lijst), `admin/csv-translations/index.blade.php` (`$langs`-map + "Preset Order"-tekst op 2 plekken).
- Inconsistente taaltellingen in copy: "22 Languages" (welcome) en "50+ languages" (login, register, credits).
- `GeminiTtsService::getLanguageCode()` (`no` → `nb-NO`, `da` → `da-DK`, `sv` → `sv-SE`, `pl` → `pl-PL`) en `GoogleTranslationService::$languageMapping` ondersteunen de nieuwe codes al.

## Beslissingen

| Onderwerp | Keuze |
|---|---|
| Scope nieuwe talen | Overal: audio-vertaling (bron + doel), extra vertalingen, text-to-audio, CSV-admin |
| CSV-preset | NO, DA, SV, PL worden **achteraan** de bestaande preset toegevoegd |
| Omvang centrale lijst | Compact: huidige 27 talen + `no`, `da`, `pl` = 30 talen |
| Taalcodes | `no`, `da`, `sv`, `pl` (ISO 639-1; niet `nb`/`dk`) |
| Vlag-emoji's in dropdowns | Vervallen (worden in deelproject 3 opnieuw ontworpen) |

## Ontwerp

### 1. Bron van waarheid — `config/audio.php`

- `languages`: code → Engelse naam, 30 entries (bestaande 27 + `no` Norwegian, `da` Danish, `pl` Polish).
- `csv_preset_languages`: geordende array, gelijk aan de huidige preset (`en, es, es_AR, de, fr, it, nl, ro, el, gr, sq, al, sk, lv, bg, fi, ca`) gevolgd door `no, da, sv, pl`. De bestaande alternatieve codes (`es_AR`, `gr`, `al`) blijven ongewijzigd; beoordeling daarvan valt onder deelproject 2.
- Verwijderd: `available_languages` en `language_codes`. Alle gebruikers daarvan worden omgezet.

### 2. Backend

- `StoreAudioRequest`, `StoreTextToAudioRequest`: `Rule::in(array_keys(config('audio.languages')))`.
- `AudioController`: inline validatie van extra vertalingen gebruikt dezelfde regel; `$availableLanguages` komt uit `config('audio.languages')`.
- `LanguageDetectionService::getPresetLanguages()` retourneert `config('audio.csv_preset_languages')`.
- `config/gemini.php`: `no`, `da`, `pl` toegevoegd aan `tts.supported_languages` en `tts.voice_mapping` (voice `Aoede`, gelijk aan de rest).
- Bestaande database-records met een taalcode buiten de lijst blijven ongewijzigd en worden nog steeds getoond (als hoofdletter-code, zoals nu). Alleen nieuwe invoer wordt tegen de lijst gevalideerd.

### 3. Views

- Nieuwe partial `resources/views/partials/language-options.blade.php`:
  - Parameters: `$selectedLanguage` (string|null) en optioneel `$languageOptions` (code → naam; standaard `config('audio.languages')`). Unieke namen om botsing met variabelen uit de parent-view te voorkomen.
  - Rendert een `<optgroup label="English">` met de `en*`-varianten en een `<optgroup label="Other languages">` met de overige talen alfabetisch op naam. Lege groepen worden niet gerenderd.
  - Markeert `$selectedLanguage` als `selected`.
- Vervangt de hardcoded `<option>`-lijsten in `audio/create` (2×, met `old()`), `audio/additional-translations` (met de door de controller gefilterde `$availableLanguages`), `text-to-audio/create` (met `old()`).
- `admin/csv-translations/index`: de checkboxes en het talen-overzicht tonen de preset-talen (behalve `en`) met hun naam uit `config('audio.languages')`: de huidige 13 + NO, DA, SV, PL. Beide "Preset Order"-regels worden opgebouwd uit `config('audio.csv_preset_languages')` (alleen codes die in `audio.languages` staan, in hoofdletters, gescheiden door ` → `), zodat de getoonde tekst gelijk blijft aan nu plus `→ NO → DA → SV → PL`.
- Taaltellingen in welcome, login, register en credits: `count(config('audio.languages'))`.
- Geen andere wijzigingen aan deze views (styling, inline styles, JS blijven zoals ze zijn).

### 4. Foutafhandeling

Geen nieuwe foutpaden. Een ongeldige taalcode levert de bestaande Laravel-validatiefout op het betreffende veld op.

## Testen

Het werk is pas af als **alles** hieronder groen/geverifieerd is.

**Nulmeting (2026-09-15):** 9 van 18 bestaande tests falen al vóór deze wijziging:
- 3× `Vite manifest not found` (geen build aanwezig in testomgeving) → oplossen met `$this->withoutVite()` in `Tests\TestCase`.
- 1× upload-test: `UploadedFile::fake()->create()` levert een leeg bestand (`application/x-empty`) dat de finfo-controle afwijst → testbestand met geldige MPEG-frames gebruiken.
- 4× tests verwachten 403, maar de controller scopet via `auth()->user()->audioFiles()->findOrFail()` en geeft 404 (bestaan van andermans bestand wordt niet gelekt — gewenst gedrag). Tests worden aangepast naar 404; `AudioController::destroy` vangt de `ModelNotFoundException` nu af en redirect (302) → `findOrFail` buiten de try zetten zodat ook daar 404 volgt.
- 1× `ExampleTest` (welcome-pagina) — zelfde Vite-oorzaak.

**Automatisch (Pest, TDD — tests eerst):**
- Feature: audio-upload accepteert `no`, `da`, `sv`, `pl` als bron- en doeltaal; weigert `hu`.
- Feature: text-to-audio accepteert `no`, `da`, `sv`, `pl`; weigert `hu`.
- Feature: extra vertalingen accepteert `no`, `da`, `sv`, `pl`; weigert `hu`.
- Feature: `audio/create`, `text-to-audio/create`, `additional-translations` en `admin/csv-translations` renderen opties voor alle vier nieuwe talen.
- Unit: `getPresetLanguages()` eindigt op `['no', 'da', 'sv', 'pl']` en begint met de ongewijzigde bestaande preset.
- Unit: elke code in `audio.languages` heeft een entry in `gemini.tts.voice_mapping`.
- Volledige suite `composer run test` slaagt (inclusief bestaande tests).
- `./vendor/bin/pint` zonder fouten op gewijzigde bestanden.

**Handmatig in de browser (dev-stack draaiend):**
- Alle gewijzigde pagina's openen zonder fouten in console of log; dropdowns tonen 30 talen in twee groepen.
- Formulieren indienen met een nieuwe taal (audio-upload, text-to-audio, extra vertaling, CSV-upload) en controleren dat de job met de juiste taalcode in de queue komt.
- Indien API-keys aanwezig: één volledige end-to-end run per flow met een nieuwe taal (bijv. NL → PL audio, text-to-audio in NO, CSV-bulkvertaling met DA/SV-kolommen) inclusief afspelen van de gegenereerde audio.

## Buiten scope

- Keuze OpenAI vs. Google voor vertaling in de hoofdflow (deelproject 2).
- Opschonen van `AudioProcessingService::translateText()`-namenlijst en `LanguageDetectionService::getPopularLanguages()` (deelproject 2).
- Visuele vormgeving van dropdowns, vlaggen, vertaling van UI-copy (deelproject 3).

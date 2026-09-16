# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Start full dev stack (server + queue worker + Vite)
composer run dev:full

# Individual services
php artisan serve
npm run dev
php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3

# Build frontend
npm run build

# Run tests
composer run test

# Run a single test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Lint with Pint
./vendor/bin/pint

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Queue management
php artisan queue:failed
php artisan queue:retry all
```

## Architecture

This is a Laravel 12 SaaS for AI-powered audio translation. The core flow:

1. **Upload** → User uploads audio (max 25MB for processing, 100MB for upload)
2. **Transcription** → `ProcessAudioJob` sends to OpenAI Whisper → stored in `audio_files.transcription`
3. **Human approval** → User reviews/edits transcription (status: `pending_approval`)
4. **Translation** → `GoogleTranslationService` translates text (status: `translating`)
5. **Human approval** → User reviews/edits translation (status: `pending_tts_approval`)
6. **TTS** → `GeminiTtsService` generates audio (status: `generating_audio` → `completed`)

### Status flow for `AudioFile`
```
uploaded → transcribing → pending_approval → translating → pending_tts_approval → generating_audio → completed
```

### Key layers

**Actions** (`app/Actions/`) — Orchestrate multi-step business logic (e.g., `CreateAudioTranslationAction`)

**Jobs** (`app/Jobs/`) — All long-running work runs async via the database queue driver:
- `ProcessAudioJob` — Transcription with Whisper; stops at `pending_approval`
- `ProcessAudioTranslationJob` — Translation via `GoogleTranslationService` after transcription approval; stops at `pending_tts_approval`
- `ProcessAudioTTSJob` — TTS after translation approval
- `ProcessTextToAudioJob` — Direct text-to-speech
- `ProcessAdditionalAudioTranslation` — Extra language variants
- `ProcessCsvTranslationJob` — Bulk CSV/XLSX translation (7200s timeout)

**Services** (`app/Services/`):
- `GeminiTtsService` — Primary TTS via `gemini-2.5-pro-tts`; auto-chunks texts >900 bytes; falls back to `SimpleTtsService` (OpenAI) on 429 errors
- `GoogleTranslationService` — The only translation provider (Google Cloud Translation v3, plain text) for the main flow, additional translations and CSV; `LanguageDetectionService` uses the same API
- `AudioProcessingService` — Orchestrator for text-to-audio flow
- `CsvParserService` / `ExcelParserService` / `MultiSheetService` — CSV/XLSX parsing

### TTS chunking
Gemini TTS has a 900-byte limit (configured in `config/gemini.php`). `GeminiTtsService` splits long texts at sentence boundaries, generates audio per chunk, then concatenates. Texts under 900 bytes get a single voice; longer texts may have minor voice variation between chunks.

### Fallback chain
Gemini TTS (quota error 429) → OpenAI TTS (`SimpleTtsService`)

### Credit system
Users pay per translation via Stripe. `Payment` + `CreditTransaction` models track the ledger. Admins can manually add/remove credits. Stripe webhooks hit `/webhook/stripe` (throttled).

### Key config files
- `config/gemini.php` — Gemini model, timeout, chunk size, rate limit
- `config/audio.php` — Supported languages (22+), voices (30+), file size limits
- `config/stripe.php` — Stripe keys, credit packages
- `config/queue.php` — Database queue, retry settings

### Admin panel
Routes under `/admin/*` protected by `AdminMiddleware` (checks `users.is_admin`). Separate login at `/admin/login`. Admin can manage users, view payments, add/remove credits, and run CSV bulk translations.

### Frontend
Blade templates + Tailwind CSS 4 via Vite. No SPA — server-side rendering with status polling via JS `fetch` against `/audio/{id}/status` endpoints (excluded from rate limiting).

Design system (see `docs/superpowers/specs/2026-09-15-ui-redesign-design.md`): light theme tokens and component classes live in `resources/css/app.css` (`btn`, `panel`, `input`/`select`/`textarea`, `status`, `alert`, `table`, `nav-item`, `lang-pair`). Pages extend `layouts.app` (sidebar shell) or `layouts.guest` (welcome/auth) and use the anonymous components in `resources/views/components` (`x-button`, `x-panel`, `x-page-header`, `x-status`, `x-alert`, `x-field`, `x-language-pair`, `x-empty-state`). No inline styles except dynamic widths; Font Awesome free icons (`fa-solid`) only. Run `npm run build` after changing classes (tests use `withoutVite()`).

### Environment
```bash
OPENAI_API_KEY=        # Whisper transcription + fallback TTS
GEMINI_API_KEY=        # Primary TTS
GEMINI_TIMEOUT=120     # HTTP timeout in seconds
GOOGLE_CLOUD_PROJECT_ID=   # Fallback when the service account file has no project_id
GOOGLE_APPLICATION_CREDENTIALS=   # Optional path to the service account JSON
STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
```

Google service account credentials stored in `storage/app/google-service-account.json` (used for Cloud Translation and Text-to-Speech).

### Queue worker requirement
The app will not process audio without a running queue worker. Always run `php artisan queue:work` alongside `php artisan serve` in development. Use `composer run dev:full` to start all three processes together.

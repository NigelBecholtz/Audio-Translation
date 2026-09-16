# 01 – Overview

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.2+, Laravel 12 |
| **Frontend** | Blade templates, Tailwind CSS, Vite 7 |
| **Database** | SQLite (default) or MySQL/PostgreSQL via `.env` |
| **Queue** | Database driver (`QUEUE_CONNECTION=database`) |
| **Cache / Session** | Database (configurable) |
| **APIs** | Google Cloud (Translation, Text-to-Speech), OpenAI (Whisper), Stripe (payments) |

## Main Features

1. **Audio translation pipeline**
   - User uploads audio → stored in `storage/app/public/`
   - **Transcription**: OpenAI Whisper (source language)
   - **Translation**: Google Cloud Translation API v3 (batch)
   - **TTS**: Google Cloud Text-to-Speech (Gemini 2.5 Pro TTS)
   - Optional: user can approve/edit transcription and translated text before TTS

2. **Text-to-audio**
   - User enters text + language + optional voice/style → job generates audio via Gemini TTS

3. **Admin panel**
   - Dashboard, users, payments, audio files
   - Credit management (add/remove credits)
   - **CSV/XLSX translation**: upload file → background job translates empty cells

4. **Credits & payments**
   - Pay-per-use credits; Stripe Checkout; webhook updates balance

## High-Level Architecture

```
User (browser)
    → Laravel routes (web.php)
    → Controllers (AudioController, TextToAudioController, PaymentController, Admin)
    → Services (AudioProcessingService, GeminiTtsService, GoogleTranslationService)
    → External APIs (OpenAI Whisper, Google Translation, Google TTS)
    → Queue jobs (ProcessAudioJob, ProcessTextToAudioJob, ProcessCsvTranslationJob)
    → Database
```

## Important Paths

| Path | Purpose |
|------|---------|
| `app/Services/` | TTS, translation, transcription, CSV parsing, credits |
| `app/Jobs/` | Queue job classes |
| `app/Http/Controllers/` | Web endpoints |
| `app/Models/` | Eloquent models |
| `config/audio.php`, `config/gemini.php`, `config/services.php`, `config/stripe.php` | Configuration |
| `storage/app/google-service-account.json` | Default Google credentials location |

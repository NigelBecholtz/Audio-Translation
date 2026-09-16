# Audio Translation – Complete Project Documentation

Handover documentation for the **Audio Translation** Laravel application. Written in English for developer handover.

---

## 1. Overview

### Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Blade, Tailwind CSS, Vite 7 |
| Database | SQLite (default) or MySQL/PostgreSQL |
| Queue | Database driver |
| APIs | Google Cloud (Translation, TTS), OpenAI (Whisper), Stripe |

### Main Features

- **Audio translation**: Upload audio → Whisper transcription → Google Translation → Gemini TTS
- **Text-to-audio**: Text → Gemini TTS → downloadable audio
- **CSV/XLSX translation**: Admin uploads spreadsheets → background job translates empty cells
- **Credits & payments**: Stripe checkout, admin credit management

---

## 2. Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- OpenAI API key
- Google Cloud service account (Translation + Text-to-Speech)
- Stripe account (optional, for payments)

### Setup

```bash
git clone <repo-url>
cd Audio-Translation
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link
```

### Run

```bash
composer run dev:full
```

Or separately: `php artisan serve`, `php artisan queue:work database --timeout=600`, `npm run dev`.

---

## 3. Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_KEY` | Yes | Laravel encryption key |
| `OPENAI_API_KEY` | Yes | OpenAI API key (Whisper) |
| `GOOGLE_CLOUD_PROJECT_ID` | Yes | Google Cloud project ID |
| `GOOGLE_SERVICE_ACCOUNT_JSON` | Or file | Base64-encoded service account JSON |
| `GOOGLE_SERVICE_ACCOUNT_PATH` | Or env | Path to JSON file (default: `storage/app/google-service-account.json`) |
| `STRIPE_KEY` | For payments | Stripe publishable key |
| `STRIPE_SECRET` | For payments | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | For payments | Stripe webhook signing secret |
| `GEMINI_TIMEOUT` | No | TTS request timeout (default: 180) |
| `QUEUE_CONNECTION` | No | `database` (default) |

---

## 4. Architecture

### Key Paths

| Path | Purpose |
|------|---------|
| `app/Services/GeminiTtsService.php` | Gemini TTS with chunking |
| `app/Services/GoogleOAuthService.php` | Service account auth, token cache |
| `app/Services/GoogleTranslationService.php` | Translation API v3 |
| `app/Services/LanguageDetectionService.php` | Detect language |
| `app/Services/AudioProcessingService.php` | Orchestrates transcription, translation, TTS |
| `app/Jobs/` | ProcessAudioJob, ProcessTextToAudioJob, ProcessCsvTranslationJob, etc. |
| `config/audio.php` | Languages, voices, limits |
| `config/gemini.php` | TTS timeout, rate limit |
| `config/services.php` | Google Cloud config |
| `config/stripe.php` | Credit packages |

### Routes (web.php)

- `/` – Welcome
- `/login`, `/register` – Auth
- `/audio` – Audio upload, show, status, download
- `/text-to-audio` – Text-to-audio create, show, status, download
- `/credits` – Payment/credits
- `/admin/*` – Admin dashboard, users, payments, CSV translations
- `/webhook/stripe` – Stripe webhook (no auth)

---

## 5. External Services

### Google Cloud

- **Cloud Translation API** – `translate.googleapis.com/v3`
- **Cloud Text-to-Speech API** – `texttospeech.googleapis.com/v1/text:synthesize` (Gemini 2.5 Pro TTS)
- **Vertex AI API** – Must be enabled for Gemini TTS

**Service account roles**: Cloud Translation API User, Cloud Text-to-Speech User, Service Usage Consumer.

### OpenAI

- **Whisper** – Transcription
- Optional TTS fallback (SimpleTtsService) – not primary

### Stripe

- Checkout for credit packages
- Webhook updates user credits

---

## 6. Database

### Models

- `User` – credits, is_admin, audioFiles, textToAudioFiles, payments
- `AudioFile` – uploads, transcription, translation, TTS, status
- `TextToAudio` – text-to-audio jobs
- `Translation` – additional translations per audio file
- `AudioTranslation` – audio translation variants
- `Payment` – Stripe payments
- `CreditTransaction` – credit history
- `CsvTranslationJob` – CSV translation jobs
- `StyleInstructionPreset` – TTS style presets

---

## 7. Queues & Jobs

| Job | Timeout | Purpose |
|-----|---------|---------|
| ProcessAudioJob | 600s | Full audio pipeline (transcribe, translate, TTS) |
| ProcessAudioTTSJob | 600s | TTS only for audio file |
| ProcessTextToAudioJob | 600s | Text-to-audio |
| ProcessCsvTranslationJob | 7200s | CSV/XLSX translation |
| ProcessAdditionalAudioTranslation | 600s | Additional translations |
| ProcessAudioTranslationJob | 600s | Audio translation variant |

**Worker**: `php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3`

---

## 8. Deployment (VPS)

1. Deploy code (git pull, composer install, npm run build)
2. Set `.env` (production values)
3. Place `google-service-account.json` in `storage/app/` or use `GOOGLE_SERVICE_ACCOUNT_JSON`
4. Run migrations: `php artisan migrate --force`
5. Storage link: `php artisan storage:link`
6. Start queue worker (supervisor/systemd): `php artisan queue:work database --timeout=600`
7. Configure web server (Apache/Nginx) to point to `public/`
8. Logs: `storage/logs/laravel.log`

---

## 9. Troubleshooting

| Error | Solution |
|-------|----------|
| `Vertex AI API has not been used` | Enable Vertex AI API in Google Cloud Console |
| `cURL error 28: Operation timed out` | Increase `GEMINI_TIMEOUT` (180+), ensure queue worker `--timeout=600` |
| `Google service account JSON file not found` | Add file to `storage/app/` or set `GOOGLE_SERVICE_ACCOUNT_JSON` |
| `403 Permission denied` | Check service account roles (Translation, TTS, Service Usage) |
| `429 Rate limit` | Gemini quota; wait or upgrade |
| Job stuck | Ensure queue worker is running |

---

## 10. Google Service Account Setup

1. **Google Cloud Console** → Create/select project
2. **Billing** → Link billing account
3. **APIs & Services → Library** → Enable: Cloud Translation API, Cloud Text-to-Speech API, Vertex AI API
4. **IAM & Admin → Service Accounts** → Create service account
5. Add roles: Cloud Translation API User, Cloud Text-to-Speech User, Service Usage Consumer
6. **Keys** → Create new key → JSON → Download
7. Place JSON at `storage/app/google-service-account.json` or base64 encode and set `GOOGLE_SERVICE_ACCOUNT_JSON`
8. Set `GOOGLE_CLOUD_PROJECT_ID` in `.env`
9. `php artisan config:clear` and `php artisan cache:clear`

---

## License

MIT License

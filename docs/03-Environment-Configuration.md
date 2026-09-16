# 03 – Environment & Configuration

## Required Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel encryption key (generate with `php artisan key:generate`) |
| `OPENAI_API_KEY` | OpenAI API key for Whisper transcription |
| `GOOGLE_CLOUD_PROJECT_ID` | Google Cloud project ID |
| Google credentials | Either `GOOGLE_SERVICE_ACCOUNT_JSON` (base64) or file at `storage/app/google-service-account.json` |

## Optional Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `GOOGLE_SERVICE_ACCOUNT_PATH` | `storage/app/google-service-account.json` | Path to service account JSON file |
| `GOOGLE_SERVICE_ACCOUNT_JSON` | - | Base64-encoded service account JSON (alternative to file) |
| `GEMINI_TIMEOUT` | 180 | TTS request timeout (seconds) |
| `GEMINI_CONNECT_TIMEOUT` | 30 | Connection timeout (seconds) |
| `GEMINI_RATE_LIMIT_ATTEMPTS` | 60 | Max TTS requests per time window |
| `GEMINI_RATE_LIMIT_DECAY` | 1 | Time window (minutes) |
| `QUEUE_CONNECTION` | database | Queue driver |
| `DB_CONNECTION` | sqlite | Database driver |
| `CACHE_STORE` | database | Cache driver |
| `SESSION_DRIVER` | database | Session driver |

## Stripe (for payments)

| Variable | Description |
|----------|-------------|
| `STRIPE_KEY` | Publishable key |
| `STRIPE_SECRET` | Secret key |
| `STRIPE_WEBHOOK_SECRET` | Webhook signing secret |
| `STRIPE_STARTER_PRICE_ID` | Stripe Price ID for credit package |

## Config Files

| File | Purpose |
|------|---------|
| `config/audio.php` | Languages, voices, file limits, TTS chunk size |
| `config/gemini.php` | TTS model, timeout, rate limit |
| `config/services.php` | `google_cloud.project_id` |
| `config/stripe.php` | Credit packages, default cost per translation |
| `config/openai.php` | OpenAI API key |

# 07 – Queues & Jobs

## Queue Configuration

- **Driver**: `database` (default)
- **Table**: `jobs`
- **Worker**: `php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3`

## Job Classes

| Job | Timeout | Tries | Purpose |
|-----|---------|-------|---------|
| `ProcessAudioJob` | 600s | 3 | Full pipeline: transcribe → translate → TTS |
| `ProcessAudioTTSJob` | 600s | 3 | TTS only for audio file |
| `ProcessTextToAudioJob` | 600s | 3 | Text-to-audio |
| `ProcessCsvTranslationJob` | 7200s | 3 | CSV/XLSX translation (large files) |
| `ProcessAdditionalAudioTranslation` | 600s | 3 | Additional translations |
| `ProcessAudioTranslationJob` | 600s | 3 | Audio translation variant |

## Why 600s Timeout?

- TTS can be slow (180s HTTP timeout per request)
- Multiple retries possible
- Job must not be killed before HTTP request completes

## Running the Worker

**Local (with composer):**

```bash
composer run dev:full
```

**Production (supervisor/systemd):**

```bash
php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3
```

**Important**: Worker `--timeout` must be ≥ job timeout (600). Otherwise the worker kills the process before the job finishes.

## Failed Jobs

- Failed jobs are logged via `failed()` method on each job
- Check `storage/logs/laravel.log`
- Use `php artisan queue:failed` to list failed jobs
- Use `php artisan queue:retry all` to retry

# 09 – Troubleshooting

## Common Errors

### 1. `Vertex AI API has not been used in project ... before or it is disabled`

**Cause**: Vertex AI API not enabled in Google Cloud.

**Fix**: Google Cloud Console → APIs & Services → Library → Enable **Vertex AI API**.

---

### 2. `cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received`

**Cause**: TTS request timed out; sometimes slow or hanging response from Google.

**Fix**:
- Increase `GEMINI_TIMEOUT` to 180 or 240 in `.env`
- Ensure queue worker runs with `--timeout=600`
- Ensure `ProcessTextToAudioJob` and `ProcessAudioTTSJob` have `$timeout = 600`

---

### 3. `Google service account JSON file not found`

**Cause**: Service account file missing or wrong path.

**Fix**:
- Place JSON at `storage/app/google-service-account.json`, or
- Set `GOOGLE_SERVICE_ACCOUNT_JSON` (base64) in `.env`, or
- Set `GOOGLE_SERVICE_ACCOUNT_PATH` to correct path

---

### 4. `403 Permission denied` (Google Cloud)

**Cause**: Service account missing roles.

**Fix**: Add roles: Cloud Translation API User, Cloud Text-to-Speech User, Service Usage Consumer. Optionally Vertex AI User if needed.

---

### 5. `429 Rate limit exceeded` (Gemini)

**Cause**: TTS quota exceeded.

**Fix**: Wait for quota reset, or upgrade Google Cloud. App has rate limiting (60/min per user).

---

### 6. Job stuck / processing never completes

**Cause**: Queue worker not running or killed before job finishes.

**Fix**:
- Ensure `php artisan queue:work database --timeout=600` is running
- Check worker logs
- Check `storage/logs/laravel.log` for job errors

---

### 7. `504 Gateway Timeout` on web request

**Cause**: Web server timeout; job runs in background but user waits for redirect.

**Fix**: Jobs run async; user should be redirected to status page. If upload itself times out, increase PHP `max_execution_time` and web server timeout.

---

### 8. TTS works in Tinker but not in queue

**Cause**: Queue worker may have different environment or timeout.

**Fix**:
- Ensure worker `--timeout=600`
- Ensure job `$timeout = 600`
- Run `php artisan config:clear` and `php artisan cache:clear` after config changes

---

## Logs

```bash
# Windows
Get-Content storage/logs/laravel.log -Tail 50

# Linux/Mac
tail -f storage/logs/laravel.log
```

## Useful Commands

```bash
php artisan config:clear
php artisan cache:clear
php artisan queue:failed
php artisan queue:retry all
php artisan tinker   # Test services manually
```

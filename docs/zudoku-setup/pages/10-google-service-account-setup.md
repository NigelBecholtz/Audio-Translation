# 10 – Google Service Account Setup

Step-by-step guide to set up or change the Google Cloud service account for Translation and TTS.

---

## 1. Sign in with the correct account

1. Go to [Google Cloud Console](https://console.cloud.google.com).
2. Sign in with the Google account you want to use.
3. Select the project (or create a new one).

---

## 2. Billing

- **Billing** → **Link a billing account**.
- Translation API v3 requires billing (free tier is usually sufficient).

---

## 3. Enable APIs

Go to **APIs & Services** → **Library** and enable:

- **Cloud Translation API**
- **Cloud Text-to-Speech API**
- **Vertex AI API** (required for Gemini TTS)

---

## 4. Create service account

1. **IAM & Admin** → **Service Accounts**.
2. **Create Service Account**.
3. Name: e.g. `audio-translation` → **Create and continue**.

---

## 5. Assign roles

Under *Grant this service account access to project*, add:

- **Cloud Translation API User**
- **Cloud Text-to-Speech User**
- **Service Usage Consumer**

Click **Done**.

---

## 6. Create JSON key

1. Open the new service account.
2. **Keys** tab → **Add key** → **Create new key** → **JSON**.
3. Download the JSON file.

---

## 7. Note your Project ID

- **IAM & Admin** → **Project Settings**.
- Copy the **Project ID** (not the Project Number).

---

## 8. Configure in the app

### Option A: File (local / VPS)

1. Place the JSON file at: `storage/app/google-service-account.json`
2. In `.env`:

```env
GOOGLE_CLOUD_PROJECT_ID=your-project-id
```

Optional custom path:

```env
GOOGLE_SERVICE_ACCOUNT_PATH=/path/to/credentials.json
```

### Option B: Environment variable (no file on server)

1. Encode the JSON to base64:
   - **Windows (PowerShell):**
     ```powershell
     [Convert]::ToBase64String([IO.File]::ReadAllBytes("path\to\credentials.json"))
     ```
   - **Linux/Mac:**
     ```bash
     base64 -i credentials.json
     ```
2. Add to `.env`:

```env
GOOGLE_SERVICE_ACCOUNT_JSON=eyJ0eXBlIjoic2VydmljZV9hY2NvdW50Ii...
GOOGLE_CLOUD_PROJECT_ID=your-project-id
```

---

## 9. Clear cache and test

```bash
php artisan config:clear
php artisan cache:clear
```

Test a translation or TTS in the app. Check `storage/logs/laravel.log` if anything fails.

---

## Summary

| Step | Action |
|------|--------|
| 1 | Sign in with correct Google account in Cloud Console |
| 2 | Link billing |
| 3 | Enable Cloud Translation API, Cloud Text-to-Speech API, Vertex AI API |
| 4 | Create service account |
| 5 | Assign roles (Translation, TTS, Service Usage) |
| 6 | Create and download JSON key |
| 7 | Note Project ID |
| 8 | Add JSON file or base64 in `.env` |
| 9 | Run `config:clear` and `cache:clear`, then test |

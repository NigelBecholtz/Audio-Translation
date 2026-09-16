# Google Service Account opnieuw instellen

Korte handleiding om een **ander Google Cloud-account** te gebruiken voor vertaling en TTS.

---

## 1. Inloggen met het nieuwe account

1. Ga naar [Google Cloud Console](https://console.cloud.google.com).
2. Log in met het **Google-account** dat je wilt gebruiken.
3. Selecteer bovenaan het **project** (of maak een nieuw project: **Select a project** → **New Project**).

---

## 2. Billing (vereist voor Translation API)

- **Billing** → **Link a billing account**.
- Koppel een billing account (gratis tier is vaak voldoende).

---

## 3. APIs inschakelen

Ga naar **APIs & Services** → **Library** en zoek en **Enable**:

- **Cloud Translation API**
- **Cloud Text-to-Speech API**

---

## 4. Service account aanmaken

1. **IAM & Admin** → **Service Accounts**.
2. **Create Service Account**.
3. Naam: bijv. `audio-translation` → **Create and continue**.

---

## 5. Rollen toekennen

Bij *Grant this service account access to project* voeg toe:

- **Cloud Translation API User**
- **Cloud Text-to-Speech User**
- **Service Usage Consumer**

Klik **Done**.

---

## 6. JSON-sleutel maken

1. Open het nieuwe service account.
2. Tab **Keys** → **Add key** → **Create new key** → **JSON**.
3. Download het JSON-bestand (bewaar het veilig).

---

## 7. Project ID noteren

- **IAM & Admin** → **Project Settings**.
- Kopieer het **Project ID** (niet het Project Number).

---

## 8. In je app instellen

Kies **één** van de twee manieren.

### Optie A: Via bestand (lokaal / VPS)

1. Plaats het JSON-bestand op de server, bijvoorbeeld:
   ```
   storage/app/google-service-account.json
   ```
2. In `.env`:
   ```env
   GOOGLE_CLOUD_PROJECT_ID=jouw-project-id
   ```
   (Optioneel) ander pad:
   ```env
   GOOGLE_SERVICE_ACCOUNT_PATH=/pad/naar/credentials.json
   ```

### Optie B: Via environment (geen bestand op server)

1. Encodeer het JSON-bestand naar base64:
   - **Windows (PowerShell):**
     ```powershell
     [Convert]::ToBase64String([IO.File]::ReadAllBytes("pad\naar\credentials.json"))
     ```
   - **Linux/Mac:**
     ```bash
     base64 -i credentials.json
     ```
2. Plak de output in `.env`:
   ```env
   GOOGLE_SERVICE_ACCOUNT_JSON=eyJ0eXBlIjoic2VydmljZV9hY2NvdW50Ii...
   GOOGLE_CLOUD_PROJECT_ID=jouw-project-id
   ```

---

## 9. Cache legen en testen

```bash
php artisan config:clear
php artisan cache:clear
```

Test daarna een vertaling of TTS in de app. Bij fouten: kijk in `storage/logs/laravel.log`.

---

## Samenvatting

| Stap | Actie |
|------|--------|
| 1 | Inloggen met nieuw Google-account in Cloud Console |
| 2 | Billing koppelen |
| 3 | Cloud Translation API + Cloud Text-to-Speech API inschakelen |
| 4 | Service account aanmaken |
| 5 | Rollen toekennen (Translation, Text-to-Speech, Service Usage) |
| 6 | JSON key downloaden |
| 7 | Project ID noteren |
| 8 | JSON in app zetten (bestand **of** base64 in `.env`) |
| 9 | `config:clear` + `cache:clear` en testen |

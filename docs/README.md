# Audio Translation – Project Documentation

Complete handover documentation for the **Audio Translation** Laravel application. Written in English so another developer can take over the project.

---

## What This Project Does

**Audio Translation** is a Laravel web application that:

- **Translates audio files**: Upload audio (MP3, WAV, M4A, etc.) → transcription (OpenAI Whisper) → translation (Google Cloud Translation API) → spoken output (Google Gemini TTS).
- **Text-to-audio**: Enter text and generate speech in multiple languages using Gemini 2.5 Pro TTS.
- **CSV/XLSX translation**: Admin uploads spreadsheets; the app translates empty cells via background jobs.
- **Credits & payments**: Pay-per-use credits with Stripe checkout; admins manage user credits.

---

## Documentation Index

| Document | Description |
|----------|-------------|
| [01 – Overview](01-Overview.md) | Tech stack, features, and high-level architecture |
| [02 – Getting Started](02-Getting-Started.md) | Prerequisites, clone, install, and first run |
| [03 – Environment & Configuration](03-Environment-Configuration.md) | All `.env` variables and config files |
| [04 – Architecture](04-Architecture.md) | App structure, routes, controllers, services |
| [05 – External Services](05-External-Services.md) | Google Cloud, OpenAI, Stripe – APIs and usage |
| [06 – Database](06-Database.md) | Models, migrations, and relationships |
| [07 – Queues & Jobs](07-Queues-and-Jobs.md) | Background jobs, timeouts, and worker |
| [08 – Deployment](08-Deployment.md) | VPS setup, queue worker, logs |
| [09 – Troubleshooting](09-Troubleshooting.md) | Common errors and fixes |
| [10 – Google Service Account Setup](10-Google-Service-Account-Setup.md) | Step-by-step Google Cloud setup |

---

## Quick Start

1. Clone repository → `composer install` → `npm install`
2. Copy `.env.example` to `.env` → `php artisan key:generate`
3. Configure: `OPENAI_API_KEY`, Google service account, `GOOGLE_CLOUD_PROJECT_ID`, Stripe (optional)
4. Database: `touch database/database.sqlite` → `php artisan migrate`
5. Storage: `php artisan storage:link`
6. Run: `composer run dev:full` (server + queue + Vite)

---

## Copy to Audio-Translation-Docs

To use this documentation in the separate `Audio-Translation-Docs` folder:

```powershell
Copy-Item -Path "C:\Users\GAMING\Desktop\Stage\Audio-Translation\docs\*" -Destination "C:\Users\GAMING\Desktop\Stage\Audio-Translation-Docs\" -Recurse -Force
```

Or manually copy the `docs` folder contents to `C:\Users\GAMING\Desktop\Stage\Audio-Translation-Docs`.

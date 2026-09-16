# 04 – Architecture

## Directory Structure (Key Parts)

```
app/
├── Actions/           # CreateAudioTranslationAction
├── Constants/         # AudioConstants (TTS chunk size, etc.)
├── Http/
│   ├── Controllers/   # AudioController, TextToAudioController, PaymentController, Admin, etc.
│   ├── Middleware/    # Admin, AudioLimits, WebhookThrottle
│   └── Requests/      # StoreAudioRequest
├── Jobs/              # ProcessAudioJob, ProcessTextToAudioJob, ProcessCsvTranslationJob, etc.
├── Models/            # User, AudioFile, TextToAudio, Payment, etc.
├── Services/          # Core business logic
│   ├── AudioProcessingService.php   # Orchestrates transcription, translation, TTS
│   ├── GeminiTtsService.php         # Gemini TTS with chunking
│   ├── GoogleOAuthService.php       # Service account auth, token cache
│   ├── GoogleTranslationService.php # Translation API v3
│   ├── LanguageDetectionService.php # Detect language
│   ├── SimpleTtsService.php         # OpenAI TTS fallback (optional)
│   ├── CsvParserService.php         # CSV parsing
│   ├── ExcelParserService.php       # XLSX parsing
│   ├── MultiSheetService.php        # Multi-sheet XLSX
│   ├── CreditService.php            # Credit deduction
│   ├── FFmpegService.php            # Audio extraction (optional)
│   ├── RateLimiter.php              # TTS rate limiting
│   └── SanitizationService.php      # Cleanup
└── Traits/            # HasAudioFiles

config/
├── audio.php          # Languages, voices, limits
├── gemini.php         # TTS config
├── services.php       # Google Cloud
├── stripe.php         # Payments
└── openai.php         # OpenAI

routes/
└── web.php            # All routes
```

## Routes

| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | Welcome |
| `/login`, `/register`, `/logout` | GET/POST | Auth |
| `/admin/login`, `/admin/logout` | GET/POST | Admin auth |
| `/webhook/stripe` | POST | Stripe webhook (no auth) |
| `/audio` | GET/POST | Create, index, show |
| `/audio/{id}/status` | GET | Poll processing status |
| `/audio/{id}/download` | GET | Download translated audio |
| `/audio/{id}/approve-transcription`, `/approve-tts` | POST | Approval steps |
| `/text-to-audio` | GET/POST | Create, index, show |
| `/text-to-audio/{id}/status` | GET | Poll status |
| `/credits` | GET | Credits page |
| `/checkout` | POST | Stripe checkout |
| `/admin/dashboard` | GET | Admin dashboard |
| `/admin/csv-translations` | GET/POST | CSV translation upload, status, download |

## Service Flow (Audio Translation)

1. User uploads audio → `AudioController::store` → `CreateAudioTranslationAction`
2. `ProcessAudioJob` dispatched
3. Job: `AudioProcessingService::transcribeAudio` (Whisper)
4. Job: `AudioProcessingService::translateText` (Google Translation) – or use `GoogleTranslationService` for batch
5. Job: `AudioProcessingService::generateAudio` (Gemini TTS)
6. User polls `/audio/{id}/status` for progress
7. User downloads result

## Service Flow (Text-to-Audio)

1. User submits text → `TextToAudioController::store`
2. `ProcessTextToAudioJob` dispatched
3. Job: `AudioProcessingService::generateAudio` (Gemini TTS)
4. User polls `/text-to-audio/{id}/status`
5. User downloads result

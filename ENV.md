# Environment Variables (.env) Configuration

## 📋 Complete `.env` Setup Guide

Kopieer deze variabelen naar je `.env` bestand en vul je eigen waarden in.

---

## 🔧 **Application Basics**

```env
APP_NAME="Audio Translation"
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Voor VPS/Production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jouw-domein.com
```

---

## 💾 **Database**

**Lokaal (SQLite):**
```env
DB_CONNECTION=sqlite
```

**VPS (MySQL):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audio_translation
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

---

## ⚡ **Queue Configuration**

**Lokaal (geen queue worker nodig):**
```env
QUEUE_CONNECTION=sync
```

**VPS (met supervisor voor betere UX):**
```env
QUEUE_CONNECTION=database
```

---

## 🤖 **OpenAI API**

```env
OPENAI_API_KEY=sk-proj-your_openai_api_key_here
```

Voor Whisper (transcriptie) en GPT (vertaling)

---

## 🎙️ **Gemini TTS API**

```env
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_TIMEOUT=120
```

**Belangrijk:** Je hebt ook `storage/app/google-service-account.json` nodig!

---

## 🎵 **Audio Processing Settings**

### File Upload Limits
```env
AUDIO_MAX_FILE_SIZE=50
AUDIO_MAX_DURATION=600
AUDIO_ALLOWED_TYPES=mp3,wav,m4a,mp4,ogg,flac
```

- `AUDIO_MAX_FILE_SIZE`: Maximum bestandsgrootte in MB (default: 50)
- `AUDIO_MAX_DURATION`: Maximum audio lengte in seconden (default: 600 = 10 minuten)
- `AUDIO_ALLOWED_TYPES`: Toegestane audio formaten (komma-gescheiden)

### Storage & Cleanup
```env
AUDIO_CLEANUP_AFTER_DAYS=30
AUDIO_STORAGE_DISK=public
```

- `AUDIO_CLEANUP_AFTER_DAYS`: Na hoeveel dagen oude bestanden verwijderen (0 = nooit)
- `AUDIO_STORAGE_DISK`: Waar audio bestanden opslaan (public, s3, etc.)

### Processing Limits
```env
AUDIO_MAX_EXECUTION_TIME=600
AUDIO_MAX_INPUT_TIME=600
AUDIO_MEMORY_LIMIT=512M
```

- `AUDIO_MAX_EXECUTION_TIME`: Max tijd voor script uitvoering (seconden)
- `AUDIO_MAX_INPUT_TIME`: Max tijd voor input parsing (seconden)
- `AUDIO_MEMORY_LIMIT`: Max geheugen per request

### Text & TTS Limits
```env
AUDIO_MAX_TEXT_LENGTH=50000
AUDIO_MAX_STYLE_LENGTH=5000
```

- `AUDIO_MAX_TEXT_LENGTH`: Max karakters voor text-to-audio input
- `AUDIO_MAX_STYLE_LENGTH`: Max karakters voor style instructions

### TTS Chunking (Advanced)
```env
TTS_CHUNK_SIZE=900
TTS_CHUNK_DELAY=2
```

- `TTS_CHUNK_SIZE`: Bytes per chunk voor Gemini TTS (default: 900, max: 900)
- `TTS_CHUNK_DELAY`: Seconden tussen chunks om rate limits te voorkomen

---

## 💳 **Stripe Payment**

```env
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
```

**Voor Production:**
```env
STRIPE_KEY=pk_live_your_live_key_here
STRIPE_SECRET=sk_live_your_live_secret_key_here
```

---

## 📝 **Session & Cache**

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
```

**Voor Production (met Redis):**
```env
SESSION_DRIVER=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 📧 **Mail (Optional)**

**Lokaal (logs only):**
```env
MAIL_MAILER=log
```

**Production (SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@jouw-domein.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 📊 **Logging**

```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

**Voor Production:**
```env
LOG_LEVEL=error
```

---

## 🎯 **Complete Example - LOKAAL (Herd)**

```env
# Application
APP_NAME="Audio Translation"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite

# Queue
QUEUE_CONNECTION=sync

# APIs
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxx
GEMINI_TIMEOUT=120

# Audio Settings
AUDIO_MAX_FILE_SIZE=50
AUDIO_MAX_DURATION=600
AUDIO_ALLOWED_TYPES=mp3,wav,m4a,mp4,ogg,flac
AUDIO_CLEANUP_AFTER_DAYS=30
AUDIO_STORAGE_DISK=public

# Processing Limits
AUDIO_MAX_EXECUTION_TIME=600
AUDIO_MEMORY_LIMIT=512M
AUDIO_MAX_TEXT_LENGTH=50000
AUDIO_MAX_STYLE_LENGTH=5000

# Stripe
STRIPE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx

# Session
SESSION_DRIVER=database
CACHE_STORE=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

---

## 🚀 **Complete Example - VPS (Production)**

```env
# Application
APP_NAME="Audio Translation"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jouw-domein.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audio_translation
DB_USERNAME=your_db_user
DB_PASSWORD=strong_password_here

# Queue (voor betere UX)
QUEUE_CONNECTION=database

# APIs
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxx
GEMINI_TIMEOUT=120

# Audio Settings
AUDIO_MAX_FILE_SIZE=50
AUDIO_MAX_DURATION=600
AUDIO_ALLOWED_TYPES=mp3,wav,m4a,mp4,ogg,flac
AUDIO_CLEANUP_AFTER_DAYS=30
AUDIO_STORAGE_DISK=public

# Processing Limits
AUDIO_MAX_EXECUTION_TIME=600
AUDIO_MEMORY_LIMIT=512M
AUDIO_MAX_TEXT_LENGTH=50000
AUDIO_MAX_STYLE_LENGTH=5000

# Stripe (LIVE keys!)
STRIPE_KEY=pk_live_xxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxx

# Session & Cache (Redis aanbevolen)
SESSION_DRIVER=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Logging (minder verbose)
LOG_CHANNEL=stack
LOG_LEVEL=error

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.jouw-provider.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@jouw-domein.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## ✅ **Na `.env` aanpassen:**

```bash
# Clear config cache
php artisan config:clear
php artisan cache:clear

# Cache nieuwe config (alleen production)
php artisan config:cache
```

---

## 🔒 **Security Checklist:**

- ✅ `.env` staat in `.gitignore`
- ✅ `google-service-account.json` staat in `.gitignore`
- ✅ Gebruik sterke database passwords op VPS
- ✅ `APP_DEBUG=false` op production
- ✅ `APP_ENV=production` op VPS
- ✅ Gebruik LIVE Stripe keys op production

---

## 📞 **Hulp Nodig?**

Zie ook:
- `DEPLOYMENT.md` - VPS deployment guide
- `README.md` - Project documentatie
- `GEMINI_OAUTH2_SETUP.md` - Gemini TTS setup


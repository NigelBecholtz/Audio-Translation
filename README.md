# Audio Translation Application

Laravel-based applicatie voor AI-powered audio vertaling met Gemini 2.5 Pro TTS en OpenAI services.

## 🚀 Start de App (Met Herd)

**Super eenvoudig!** Herd draait al je Laravel server.

```bash
npm run dev
```

Dit start alleen Vite (voor CSS/JS). **Dat is alles!**

### **Access je app:**
**https://audio-translation.test**

**No queue worker needed - alles werkt automatisch!** 🎉

## 📋 Vereisten

- PHP 8.2+
- Composer
- Node.js & NPM
- OpenAI API key
- Google Cloud Service Account (voor Gemini TTS)

## ⚙️ Setup

1. **Clone repository**
```bash
git clone <repository-url>
cd Audio-Translation
```

2. **Installeer dependencies**
```bash
composer install
npm install
```

3. **Configureer environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configureer API keys in `.env`**
```env
OPENAI_API_KEY=your_openai_key
GEMINI_TIMEOUT=120

# Optioneel: Voor hogere quota
QUEUE_CONNECTION=database
```

5. **Setup database**
```bash
touch database/database.sqlite
php artisan migrate
```

6. **Storage link**
```bash
php artisan storage:link
```

7. **Start de app**
```bash
composer run dev
```

## 🎯 Features

- **Audio Upload & Vertaling**: MP3, WAV, M4A bestanden (max 50MB)
- **Text-to-Audio**: Direct tekst naar spraak conversie
- **Gemini 2.5 Pro TTS**: 30+ AI stemmen met accent support
- **OpenAI TTS Fallback**: Automatisch bij quota issues
- **Smart Chunking**: Automatisch voor lange teksten (met voice consistency)
- **Credit System**: Pay-per-use met Stripe integratie
- **Admin Panel**: Volledig beheer dashboard

## ⚠️ Belangrijke Opmerkingen

### **Voice Consistency**
- **Tekst < 900 karakters**: ✅ Perfecte stem consistency
- **Tekst > 900 karakters**: ⚠️ Mogelijke kleine stem variaties (API limitatie)

### **API Quota**
- **Gemini TTS**: Beperkte gratis quota (error 429 mogelijk)
- **OpenAI TTS**: Automatische fallback bij quota issues
- **Check quota**: https://console.cloud.google.com/apis/api/texttospeech.googleapis.com/quotas

### **FFmpeg (Optioneel)**
Voor betere audio concatenatie:
```bash
winget install ffmpeg
```
Werkt ook zonder FFmpeg (PHP fallback).

## 🧪 Testen

```bash
composer run test
```

## 📁 Belangrijke Bestanden

- `app/Services/GeminiTtsService.php` - Gemini TTS met chunking
- `app/Services/SimpleTtsService.php` - OpenAI TTS fallback
- `app/Jobs/` - Background job processing
- `config/gemini.php` - Gemini configuratie

## 🆘 Troubleshooting

### **Job blijft hangen?**
```bash
# Check of queue worker draait via:
composer run dev
```

### **Gemini Quota Exceeded (429)?**
- App gebruikt automatisch OpenAI TTS fallback
- Of wacht tot quota reset (dagelijks)
- Of upgrade Google Cloud account

### **504 Gateway Timeout?**
- Queue worker moet draaien (`composer run dev`)
- Check logs: `storage/logs/laravel.log`

## 📊 Logs

```bash
# Windows
Get-Content storage/logs/laravel.log -Tail 50

# Linux/Mac
tail -f storage/logs/laravel.log
```

## 🎯 Start Commands

| Command | Beschrijving |
|---------|-------------|
| `composer run dev` | Start alles (aanbevolen) |
| `php artisan serve` | Alleen server |
| `php artisan queue:work database --timeout=600` | Alleen queue worker |
| `npm run dev` | Alleen Vite |

## 📝 License

MIT License - Made by Nigel Becholtz

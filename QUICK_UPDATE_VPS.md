# 🚀 Quick VPS Update Guide

## Stappen om je VPS te updaten met de nieuwe verbeteringen:

### **1. SSH naar je VPS:**
```bash
ssh jouw-user@jouw-vps-ip
```

### **2. Navigeer naar je project:**
```bash
cd /var/www/chatgpt
```

### **3. Pull de nieuwe code:**
```bash
git pull origin main
```

### **4. Upload nieuw bestand:**
Via SFTP upload:
- `config/audio.php` (nieuw bestand!)

### **5. Update `.env` bestand:**
```bash
nano .env
```

Voeg deze regels toe (of update ze):
```env
# Gemini
GEMINI_TIMEOUT=120

# Audio Settings
AUDIO_MAX_FILE_SIZE=50
AUDIO_MAX_DURATION=600
AUDIO_ALLOWED_TYPES=mp3,wav,m4a,mp4,ogg,flac
AUDIO_CLEANUP_AFTER_DAYS=30
AUDIO_STORAGE_DISK=public

# Processing Limits
AUDIO_MAX_EXECUTION_TIME=600
AUDIO_MAX_INPUT_TIME=600
AUDIO_MEMORY_LIMIT=512M

# Text & Style Limits
AUDIO_MAX_TEXT_LENGTH=50000
AUDIO_MAX_STYLE_LENGTH=5000

# Queue (kies een van deze)
QUEUE_CONNECTION=sync  # Voor nu
# QUEUE_CONNECTION=database  # Later met supervisor
```

Sla op: `CTRL+X`, dan `Y`, dan `ENTER`

### **6. Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **7. Restart services:**
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

### **8. Permissions (indien nodig):**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## ✅ Test

Bezoek: `http://chatgpt.optimasit.com/text-to-audio/create`

Je app zou nu moeten werken zonder errors!

---

## 📋 Wat is er nieuw?

- ✅ **Betere configuratie** - alles via `.env` instelbaar
- ✅ **Rate limiting** - bescherming tegen abuse
- ✅ **Database transactions** - geen credit loss bij errors
- ✅ **CreditService** - cleaner code
- ✅ **Environment-based logging** - minder logs in productie
- ✅ **Chunking support** - teksten > 900 bytes werken nu
- ✅ **Config fallbacks** - werkt ook zonder nieuwe .env values

---

## 🆘 Probleem?

Als je nog steeds errors ziet:
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check queue (als je database queue gebruikt)
php artisan queue:work --once

# Herstart alles
sudo systemctl restart php8.2-fpm nginx
```


# Production Deployment Guide

## ✅ Verbeteringen Sinds OpenAI TTS Versie

### **Wat is Nieuw:**
1. ✅ **Gemini 2.5 Pro TTS** (30+ stemmen, style instructions)
2. ✅ **Smart chunking** (teksten > 900 bytes)
3. ✅ **CreditService** (geen code duplication meer)
4. ✅ **Rate limiting** (60 req/min general, 20 req/min voor TTS)
5. ✅ **Database transactions** (atomische credit operations)
6. ✅ **Environment-based logging** (minder logs in production)
7. ✅ **Config centralization** (config/audio.php)
8. ✅ **Security** (service account in .gitignore)

## 🚀 Deploy Naar VPS

### **1. Update Code op VPS:**

```bash
cd /var/www/chatgpt  # of jouw path
git pull origin main
```

### **2. Nieuwe Dependencies:**

```bash
# FFmpeg voor audio concatenatie
sudo apt install ffmpeg -y

# Composer dependencies (geen nieuwe)
composer install --no-dev
```

### **3. Run Nieuwe Migraties:**

```bash
php artisan migrate --force
```

Dit voegt toe:
- `voice` kolom
- `style_instruction` kolom  
- `text_to_audio` tabel

### **4. Upload Service Account:**

Via SFTP upload:
- `storage/app/google-service-account.json`

Permissions:
```bash
chmod 600 storage/app/google-service-account.json
chown www-data:www-data storage/app/google-service-account.json
```

### **5. Update .env:**

Voeg toe:
```env
GEMINI_TIMEOUT=120
QUEUE_CONNECTION=sync
```

Voor production met queue worker:
```env
QUEUE_CONNECTION=database
```

### **6. Nginx Timeouts (BELANGRIJK):**

Edit je nginx config:
```nginx
server {
    # Bestaande config...
    
    # Voeg toe:
    proxy_read_timeout 600;
    fastcgi_read_timeout 600;
}
```

Reload:
```bash
sudo systemctl reload nginx
```

### **7. PHP Timeouts:**

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Zet:
```ini
max_execution_time = 600
memory_limit = 512M
```

Restart:
```bash
sudo systemctl restart php8.2-fpm
```

### **8. Clear & Optimize:**

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **9. Permissions:**

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## ✅ Klaar!

Test op: https://jouw-domein.com

## 📊 Performance Tips

**Voor Sync Queue (huidige setup):**
- ✅ Eenvoudig
- ⚠️ Requests duren lang (30-60 sec)
- ✅ Geen extra configuratie

**Voor Database Queue + Supervisor:**
- ✅ Snelle responses
- ✅ Better UX
- ⚠️ Requires supervisor setup

Wil je supervisor setup? Vraag het!

# 🚀 Production Setup Guide

## ⚠️ CRITICAL: Queue Configuration

### **Voor VPS/Production:**

Edit je `.env` op de VPS:

```env
# VERPLICHT voor productie
QUEUE_CONNECTION=database
APP_ENV=production
APP_DEBUG=false
```

### **Setup Queue Worker (Supervisor):**

1. **Installeer Supervisor:**
```bash
sudo apt install supervisor -y
```

2. **Create config:**
```bash
sudo nano /etc/supervisor/conf.d/audio-translation-worker.conf
```

Plak dit:
```ini
[program:audio-translation-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chatgpt/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/chatgpt/storage/logs/worker.log
stopwaitsecs=3600
```

3. **Start worker:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start audio-translation-worker:*
```

4. **Check status:**
```bash
sudo supervisorctl status
```

### **Benefits:**
- ✅ Snelle page responses (< 1 sec)
- ✅ Background processing
- ✅ Auto-restart bij crashes
- ✅ Multiple workers voor throughput

---

## 🔒 Security Checklist

- ✅ `APP_DEBUG=false` in production
- ✅ `APP_ENV=production`
- ✅ `is_admin` niet in mass assignment
- ✅ Debug logging uit in productie
- ✅ HTTPS enabled
- ✅ `.env` niet in Git
- ✅ `google-service-account.json` niet in Git

---

## 📊 Monitoring

View worker logs:
```bash
tail -f /var/www/chatgpt/storage/logs/worker.log
```

View Laravel logs:
```bash
tail -f /var/www/chatgpt/storage/logs/laravel.log
```

Restart workers:
```bash
sudo supervisorctl restart audio-translation-worker:*
```


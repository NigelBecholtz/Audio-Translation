# VPS Deployment Stappen - Large CSV Queue System

## Stap 1: Pull de nieuwe code
```bash
cd /path/to/Audio-Translation
git pull origin main
```

## Stap 2: Composer dependencies (als je nieuwe packages hebt)
```bash
composer install --no-dev --optimize-autoloader
```

## Stap 3: Run database migratie
```bash
php artisan migrate --force
```

Dit maakt de `csv_translation_jobs` tabel aan.

## Stap 4: Cache clearen
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Stap 5: PHP Configuratie aanpassen

### Voor PHP-FPM (gebruikelijk bij Nginx)
Bewerk je PHP-FPM config (meestal `/etc/php/8.x/fpm/php.ini`):

```bash
sudo nano /etc/php/8.2/fpm/php.ini  # Pas versie aan naar jouw PHP versie
```

Zoek en wijzig deze waarden:
```ini
max_execution_time = 3600
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
```

Herstart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm  # Pas versie aan
```

## Stap 6: Nginx Configuratie aanpassen

Bewerk je site config (meestal in `/etc/nginx/sites-available/`):
```bash
sudo nano /etc/nginx/sites-available/your-site
```

Voeg toe of wijzig in het `server` block:
```nginx
server {
    # ... andere configuratie ...
    
    client_max_body_size 100M;
    client_body_timeout 300s;
    
    # ... rest van configuratie ...
}
```

Test en herlaad Nginx:
```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Stap 7: Queue Worker Setup (BELANGRIJK!)

Je hebt 3 opties. Kies de beste voor jouw situatie:

### Optie A: Supervisor (AANBEVOLEN voor productie)

1. **Installeer Supervisor**:
```bash
sudo apt-get update
sudo apt-get install supervisor
```

2. **Maak Supervisor configuratie**:
```bash
sudo nano /etc/supervisor/conf.d/laravel-queue.conf
```

3. **Voeg deze configuratie toe**:
```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /pad/naar/jouw/Audio-Translation/artisan queue:work --sleep=3 --tries=1 --max-time=3600 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/pad/naar/jouw/Audio-Translation/storage/logs/worker.log
stopwaitsecs=3600
```

**LET OP**: Verander `/pad/naar/jouw/Audio-Translation/` naar je echte pad!

4. **Start Supervisor**:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

5. **Check of het werkt**:
```bash
sudo supervisorctl status
```

Je zou moeten zien: `laravel-queue-worker:laravel-queue-worker_00   RUNNING`

### Optie B: Systemd Service (Alternatief)

1. **Maak service file**:
```bash
sudo nano /etc/systemd/system/laravel-queue.service
```

2. **Voeg toe**:
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /pad/naar/jouw/Audio-Translation/artisan queue:work --timeout=3600
StandardOutput=append:/pad/naar/jouw/Audio-Translation/storage/logs/worker.log
StandardError=append:/pad/naar/jouw/Audio-Translation/storage/logs/worker.log

[Install]
WantedBy=multi-user.target
```

3. **Start service**:
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
sudo systemctl status laravel-queue
```

### Optie C: Cron Job (Simpelste, maar minder betrouwbaar)

```bash
crontab -e
```

Voeg toe:
```bash
* * * * * cd /pad/naar/jouw/Audio-Translation && php artisan queue:work --stop-when-empty --timeout=3600 >> /dev/null 2>&1
```

### Optie D: Sync Mode (Geen queue, direct processing)

Als je géén achtergrond worker wilt:

1. **Bewerk .env**:
```bash
nano /pad/naar/jouw/Audio-Translation/.env
```

2. **Wijzig of voeg toe**:
```env
QUEUE_CONNECTION=sync
```

3. **Cache clearen**:
```bash
php artisan config:clear
```

**NADEEL**: De request duurt nu de hele processing tijd, maar gebruiker ziet wel de status pagina.

## Stap 8: Verificatie

### Check queue configuratie:
```bash
php artisan queue:work --help
```

### Test met kleine file eerst:
1. Upload een kleine CSV (100 regels)
2. Check of je naar status pagina wordt geleid
3. Check of progress bar werkt
4. Check of download werkt

### Monitor queue worker logs:
```bash
tail -f /pad/naar/jouw/Audio-Translation/storage/logs/worker.log
```

### Check Laravel logs:
```bash
tail -f /pad/naar/jouw/Audio-Translation/storage/logs/laravel.log
```

## Stap 9: Queue Worker Management (met Supervisor)

### Worker herstarten na code updates:
```bash
sudo supervisorctl restart laravel-queue-worker:*
```

### Status checken:
```bash
sudo supervisorctl status
```

### Logs bekijken:
```bash
sudo supervisorctl tail -f laravel-queue-worker stdout
```

### Worker stoppen:
```bash
sudo supervisorctl stop laravel-queue-worker:*
```

## Troubleshooting

### Queue werkt niet:
```bash
# Check Supervisor status
sudo supervisorctl status

# Herstart worker
sudo supervisorctl restart laravel-queue-worker:*

# Check logs
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
```

### Upload fails:
```bash
# Check PHP limits
php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit'

# Check Nginx limit
sudo nginx -T | grep client_max_body_size
```

### Database errors:
```bash
# Check migrations
php artisan migrate:status

# Run migrations again
php artisan migrate --force
```

### Permissions:
```bash
# Zorg dat storage writable is
sudo chown -R www-data:www-data /pad/naar/jouw/Audio-Translation/storage
sudo chmod -R 775 /pad/naar/jouw/Audio-Translation/storage
```

## Quick Reference - Handig om te kopiëren

### Volledige deployment in één keer (na git pull):
```bash
cd /pad/naar/jouw/Audio-Translation
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
sudo chown -R www-data:www-data storage
sudo supervisorctl restart laravel-queue-worker:*
```

### Daily check commands:
```bash
# Check queue worker
sudo supervisorctl status

# Check recent jobs in database
php artisan tinker
>>> \App\Models\CsvTranslationJob::latest()->take(5)->get(['id','status','original_filename','created_at'])

# Clear old completed jobs (optioneel)
>>> \App\Models\CsvTranslationJob::where('status', 'completed')->where('created_at', '<', now()->subDays(7))->delete()
```

## Aanbevolen Setup

Voor productie met grote files:
- ✅ Gebruik Supervisor (Optie A)
- ✅ PHP memory_limit: 512M
- ✅ Nginx client_max_body_size: 100M
- ✅ Queue worker timeout: 3600s
- ✅ Monitor worker logs dagelijks

Succes! 🚀


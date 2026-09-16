# 08 – Deployment

## VPS Checklist

### 1. Server Requirements

- PHP 8.2+
- Composer
- Node.js & NPM
- SQLite or MySQL/PostgreSQL
- Web server (Apache/Nginx)

### 2. Deploy Code

```bash
cd /var/www/your-app
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. Environment

- Copy `.env.example` to `.env` (if not exists)
- Set `APP_ENV=production`, `APP_DEBUG=false`
- Set `APP_URL` to your domain
- Configure all API keys

### 4. Google Credentials

- Place `google-service-account.json` in `storage/app/`
- Or set `GOOGLE_SERVICE_ACCOUNT_JSON` (base64)
- Set `GOOGLE_CLOUD_PROJECT_ID`

### 5. Database

```bash
php artisan migrate --force
```

### 6. Storage

```bash
php artisan storage:link
```

### 7. Cache & Config

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Queue Worker

Run via **supervisor** or **systemd**:

```bash
php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3
```

Example supervisor config:

```ini
[program:laravel-worker]
command=php /var/www/your-app/artisan queue:work database --timeout=600 --sleep=3 --tries=3
directory=/var/www/your-app
autostart=true
autorestart=true
user=www-data
```

### 9. Web Server

- Point document root to `public/`
- Ensure `storage` and `bootstrap/cache` are writable

### 10. Logs

- `storage/logs/laravel.log`
- Use `tail -f storage/logs/laravel.log` for debugging

### 11. Stripe Webhook

- Configure webhook URL: `https://yourdomain.com/webhook/stripe`
- Event: `checkout.session.completed`
- Set `STRIPE_WEBHOOK_SECRET` in `.env`

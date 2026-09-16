# 02 – Getting Started

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- OpenAI API key
- Google Cloud service account (Translation + Text-to-Speech)
- Stripe account (optional, for payments)

## Setup Steps

### 1. Clone and install

```bash
git clone <repository-url>
cd Audio-Translation
composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure `.env`

Minimum required:

```env
OPENAI_API_KEY=sk-...
GOOGLE_CLOUD_PROJECT_ID=your-project-id
```

For Google credentials, either:
- Place `google-service-account.json` in `storage/app/`, or
- Set `GOOGLE_SERVICE_ACCOUNT_JSON` (base64-encoded JSON)

For payments:

```env
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_STARTER_PRICE_ID=price_...
```

### 4. Database

```bash
touch database/database.sqlite
php artisan migrate
```

### 5. Storage link

```bash
php artisan storage:link
```

### 6. Run the application

**Option A – All-in-one (recommended for local dev):**

```bash
composer run dev:full
```

This starts: PHP server, queue worker, and Vite.

**Option B – Separate terminals:**

```bash
php artisan serve
php artisan queue:work database --timeout=600 --tries=3 --memory=512 --sleep=3
npm run dev
```

### 7. Access

- Local: `http://localhost:8000` or `https://audio-translation.test` (if using Herd)
- Admin: `/admin/login` (requires admin user – see seeders)

## First Admin User

```bash
php artisan db:seed --class=AdminUserSeeder
```

Check `database/seeders/AdminUserSeeder.php` for default credentials.

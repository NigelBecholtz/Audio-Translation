# Audio Translation Application

A Laravel-based web application that provides AI-powered audio translation services. Users can upload audio files, get them transcribed, translated, and converted back to audio in their target language using OpenAI's Whisper, GPT, and TTS APIs.

## 🚀 Features

### Core Functionality
- **Audio Upload**: Support for MP3, WAV, M4A, and MP4 audio files (up to 50MB)
- **Speech-to-Text**: Automatic transcription using OpenAI Whisper API
- **Text Translation**: AI-powered translation between 22 supported languages
- **Text-to-Speech**: Generate translated audio using OpenAI TTS API
- **Real-time Processing**: Live status updates during translation workflow

### Supported Languages
English, Spanish, French, German, Dutch, Italian, Portuguese, Russian, Japanese, Korean, Chinese, Arabic, Hindi, Swedish, Albanian, Bulgarian, Slovak, Latvian, Finnish, Greek, Romanian, Catalan

### User Management
- **User Registration & Authentication**: Secure user accounts with Laravel's built-in auth
- **Subscription Tiers**: Free tier (2 translations) and pay-per-use credit system
- **Credit System**: Purchase credits for additional translations (€0.50 per translation)
- **Admin Panel**: Complete admin dashboard for user and payment management

### Payment Integration
- **Stripe Integration**: Secure payment processing for credit purchases
- **Credit Packages**: Starter package (10 credits for €5.00)
- **Transaction History**: Complete audit trail of all payments and credit usage

## 🛠️ Technology Stack

### Backend
- **Laravel 12**: PHP framework
- **PHP 8.2+**: Server-side language
- **SQLite**: Database (easily configurable for MySQL/PostgreSQL)
- **OpenAI API**: AI services (Whisper, GPT-3.5-turbo, TTS)

### Frontend
- **Tailwind CSS 4**: Modern utility-first CSS framework
- **Vite**: Fast build tool and development server
- **Blade Templates**: Laravel's templating engine

### Payment & Services
- **Stripe**: Payment processing
- **OpenAI**: AI transcription, translation, and TTS services

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- OpenAI API key
- Stripe account (for payments)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd Audio-Translation
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Environment Variables
Edit `.env` file with your configuration:

```env
# Application
APP_NAME="Audio Translation"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_ORGANIZATION=your_organization_id_here

# Stripe Configuration
STRIPE_KEY=pk_test_your_stripe_publishable_key
STRIPE_SECRET=sk_test_your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
STRIPE_STARTER_PRICE_ID=price_your_starter_price_id

# Mail Configuration (optional)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### 6. Database Setup
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed admin user (optional)
php artisan db:seed --class=AdminUserSeeder
```

### 7. Storage Setup
```bash
# Create storage link
php artisan storage:link
```

### 8. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

## 🏃‍♂️ Running the Application

### Development Mode
```bash
# Start all services (Laravel server, queue worker, and Vite)
composer run dev
```

Or run services individually:
```bash
# Laravel development server
php artisan serve

# Queue worker (for background processing)
php artisan queue:work

# Vite development server
npm run dev
```

### Production Mode
```bash
# Build assets
npm run build

# Start production server
php artisan serve --host=0.0.0.0 --port=8000
```

## 📁 Project Structure

```
Audio-Translation/
├── app/
│   ├── Http/Controllers/     # Application controllers
│   │   ├── AudioController.php      # Main audio processing logic
│   │   ├── PaymentController.php    # Stripe payment handling
│   │   ├── AdminController.php      # Admin panel functionality
│   │   └── Auth/                   # Authentication controllers
│   ├── Models/               # Eloquent models
│   │   ├── AudioFile.php           # Audio file management
│   │   ├── User.php                # User model with subscription logic
│   │   ├── Payment.php             # Payment tracking
│   │   └── CreditTransaction.php   # Credit usage tracking
│   └── Exceptions/           # Custom exceptions
├── config/                   # Configuration files
│   ├── openai.php           # OpenAI API configuration
│   └── stripe.php           # Stripe payment configuration
├── database/
│   ├── migrations/          # Database schema migrations
│   └── seeders/            # Database seeders
├── resources/
│   ├── views/              # Blade templates
│   │   ├── audio/          # Audio-related views
│   │   ├── admin/          # Admin panel views
│   │   └── payment/        # Payment-related views
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── routes/
│   └── web.php            # Web routes
└── storage/
    └── app/public/        # Public file storage
```

## 🔧 Configuration

### OpenAI API Setup
1. Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Add the key to your `.env` file
3. Ensure you have sufficient credits for API usage

### Stripe Setup
1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Get your API keys from the Stripe dashboard
3. Create a product and price for the starter credit package
4. Add the price ID to your `.env` file
5. Configure webhooks for payment processing

### File Upload Limits
The application supports files up to 50MB. To modify limits, update:
- PHP configuration in `php.ini`
- Laravel validation rules in `AudioController.php`
- Nginx/Apache server configuration

## 🎯 Usage

### For Users
1. **Register/Login**: Create an account or sign in
2. **Upload Audio**: Select an audio file and choose source/target languages
3. **Monitor Progress**: Watch real-time status updates during processing
4. **Download Results**: Get the translated audio file when complete
5. **Purchase Credits**: Buy additional translations when free limit is reached

### For Administrators
1. **Admin Login**: Access admin panel at `/admin/login`
2. **User Management**: View and manage user accounts
3. **Payment Monitoring**: Track all payments and transactions
4. **Credit Management**: Add or remove credits for users
5. **System Overview**: Monitor application usage and performance

## 🔒 Security Features

- **Authentication**: Laravel's built-in authentication system
- **Authorization**: Role-based access control (admin middleware)
- **File Validation**: Strict file type and size validation
- **CSRF Protection**: Cross-site request forgery protection
- **Secure Payments**: Stripe's secure payment processing
- **Input Sanitization**: Proper validation and sanitization of all inputs

## 🧪 Testing

```bash
# Run all tests
composer run test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📊 Monitoring & Logging

- **Application Logs**: Check `storage/logs/laravel.log`
- **Queue Monitoring**: Monitor background job processing
- **Payment Logs**: Stripe webhook logs and transaction history
- **Error Tracking**: Comprehensive error logging and handling

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure production database (MySQL/PostgreSQL recommended)
- [ ] Set up SSL certificate
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up queue workers (Supervisor recommended)
- [ ] Configure file storage (S3 or similar for scalability)
- [ ] Set up monitoring and logging
- [ ] Configure backup strategy

### Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# OpenAI (Production keys)
OPENAI_API_KEY=your_production_openai_key

# Stripe (Production keys)
STRIPE_KEY=pk_live_your_live_stripe_key
STRIPE_SECRET=sk_live_your_live_stripe_secret
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Check the application logs in `storage/logs/laravel.log`
- Review the Laravel documentation
- Check OpenAI API documentation for service-specific issues
- Review Stripe documentation for payment-related issues

## 🔄 API Workflow

The application follows this workflow for audio translation:

1. **Upload**: User uploads audio file with language selection
2. **Transcription**: OpenAI Whisper converts audio to text
3. **Translation**: OpenAI GPT translates text to target language
4. **Audio Generation**: OpenAI TTS converts translated text to audio
5. **Completion**: User can download the translated audio file

Each step is tracked with status updates and error handling for a smooth user experience.

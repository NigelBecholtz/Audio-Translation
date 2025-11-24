<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\TextToAudioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

// Stripe Webhook (must be outside auth middleware and CSRF protection)
// Rate limited to prevent DoS attacks
Route::post('/webhook/stripe', [WebhookController::class, 'handleStripe'])
    ->middleware('webhook.throttle')
    ->name('webhook.stripe');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Admin Login Routes (Public)
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// Protected Routes with rate limiting
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    // Audio routes with processing limits middleware
    Route::middleware('audio.limits')->group(function () {
        Route::post('audio', [AudioController::class, 'store'])->name('audio.store');
    });
    
    Route::resource('audio', AudioController::class)->except(['store']);
    Route::get('audio/{id}/download', [AudioController::class, 'download'])->name('audio.download');
    Route::post('audio/{id}/approve-transcription', [AudioController::class, 'approveTranscription'])->name('audio.approve-transcription');
    Route::post('audio/{id}/retry', [AudioController::class, 'retry'])->name('audio.retry');
});

// Status endpoints WITHOUT rate limiting for real-time polling
Route::middleware(['auth'])->group(function () {
    Route::get('audio/{id}/status', [AudioController::class, 'status'])->name('audio.status');
    
    // Text to Audio Routes with stricter rate limiting and processing limits
    Route::middleware(['throttle:20,1', 'audio.limits'])->group(function () {
        Route::post('text-to-audio', [TextToAudioController::class, 'store'])->name('text-to-audio.store');
    });
    
    Route::resource('text-to-audio', TextToAudioController::class)->except(['store'])->middleware('throttle:20,1');
    Route::get('text-to-audio/{id}/download', [TextToAudioController::class, 'download'])->name('text-to-audio.download');
});

// Text-to-audio status endpoint WITHOUT rate limiting for real-time polling
Route::middleware(['auth'])->group(function () {
    Route::get('text-to-audio/{id}/status', [TextToAudioController::class, 'status'])->name('text-to-audio.status');
});

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    // Payment Routes
    Route::get('/credits', [PaymentController::class, 'showCredits'])->name('payment.credits');
    Route::post('/checkout', [PaymentController::class, 'createCheckoutSession'])->name('payment.checkout');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    
    // Style Preset Routes
    Route::resource('style-presets', \App\Http\Controllers\StylePresetController::class);
    Route::get('/api/style-presets', [\App\Http\Controllers\StylePresetController::class, 'getPresets'])->name('style-presets.api');
    
    // Admin Routes (Protected by admin middleware)
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/audio-files', [AdminController::class, 'audioFiles'])->name('audio-files');
        
        // Credit Management Routes
        Route::post('/users/{user}/add-credits', [AdminController::class, 'addCredits'])->name('users.add-credits');
        Route::post('/users/{user}/remove-credits', [AdminController::class, 'removeCredits'])->name('users.remove-credits');
        Route::get('/users/{user}/credit-history', [AdminController::class, 'creditHistory'])->name('users.credit-history');
        
        // CSV Translation Routes
        Route::get('/csv-translations', [\App\Http\Controllers\Admin\CsvTranslationController::class, 'index'])->name('csv-translations.index');
        Route::post('/csv-translations/process', [\App\Http\Controllers\Admin\CsvTranslationController::class, 'process'])->name('csv-translations.process');
        Route::get('/csv-translations/{job}/status', [\App\Http\Controllers\Admin\CsvTranslationController::class, 'status'])->name('csv-translations.status');
        Route::get('/csv-translations/{job}/status-api', [\App\Http\Controllers\Admin\CsvTranslationController::class, 'statusApi'])->name('csv-translations.status-api');
        Route::get('/csv-translations/{job}/download', [\App\Http\Controllers\Admin\CsvTranslationController::class, 'download'])->name('csv-translations.download');
    });
});

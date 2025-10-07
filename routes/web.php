<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\TextToAudioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLoginController;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

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

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::resource('audio', AudioController::class);
    Route::get('audio/{id}/download', [AudioController::class, 'download'])->name('audio.download');
    
    // Text to Audio Routes
    Route::resource('text-to-audio', TextToAudioController::class);
    Route::get('text-to-audio/{id}/download', [TextToAudioController::class, 'download'])->name('text-to-audio.download');
    
    // Payment Routes
    Route::get('/credits', [PaymentController::class, 'showCredits'])->name('payment.credits');
    Route::post('/checkout', [PaymentController::class, 'createCheckoutSession'])->name('payment.checkout');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    
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
    });
});

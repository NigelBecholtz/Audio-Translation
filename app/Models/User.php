<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'subscription_type',
        'translations_used',
        'translations_limit',
        'credits',
        'subscription_expires_at',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'credits' => 'decimal:2',
            'subscription_expires_at' => 'datetime',
        ];
    }

    // Relationships
    public function audioFiles()
    {
        return $this->hasMany(AudioFile::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function textToAudioFiles()
    {
        return $this->hasMany(TextToAudio::class);
    }

    // Subscription methods
    public function canMakeTranslation(): bool
    {
        // Check free translations first
        if ($this->translations_used < $this->translations_limit) {
            return true;
        }
        
        // Then check if user has credits
        return $this->credits >= 0.50; // €0.50 per translation
    }

    public function getRemainingTranslations(): int
    {
        // Free translations
        $freeRemaining = max(0, $this->translations_limit - $this->translations_used);
        
        // Credits translations
        $creditsTranslations = floor($this->credits / 0.50);
        
        return $freeRemaining + $creditsTranslations;
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_type === 'free') {
            return true;
        }
        
        if ($this->subscription_type === 'pay_per_use') {
            return $this->credits > 0;
        }
        
        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}

<?php

namespace App\Providers;

use App\Models\AudioFile;
use App\Models\TextToAudio;
use App\Policies\AudioFilePolicy;
use App\Policies\TextToAudioPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(AudioFile::class, AudioFilePolicy::class);
        Gate::policy(TextToAudio::class, TextToAudioPolicy::class);
    }
}

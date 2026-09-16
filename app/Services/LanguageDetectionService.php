<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LanguageDetectionService
{
    private const FALLBACK_LANGUAGE = 'en';

    public function __construct(private GoogleOAuthService $oauthService) {}

    /**
     * Detect the language of a text with Google Cloud Translation v3; falls back to English.
     */
    public function detectLanguage(string $text): string
    {
        if (trim($text) === '') {
            return self::FALLBACK_LANGUAGE;
        }

        $projectId = $this->oauthService->projectId();

        if (! $projectId) {
            Log::warning('Language detection skipped: Google Cloud project ID not configured');

            return self::FALLBACK_LANGUAGE;
        }

        try {
            $response = Http::withToken($this->oauthService->getAccessToken())
                ->acceptJson()
                ->timeout(30)
                ->post("https://translation.googleapis.com/v3/projects/{$projectId}:detectLanguage", [
                    'content' => $text,
                    'mimeType' => 'text/plain',
                ]);

            if ($response->failed()) {
                Log::error('Language detection failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return self::FALLBACK_LANGUAGE;
            }

            return $response->json('languages.0.languageCode') ?: self::FALLBACK_LANGUAGE;

        } catch (Throwable $e) {
            Log::error('Language detection error', ['error' => $e->getMessage()]);

            return self::FALLBACK_LANGUAGE;
        }
    }

    /**
     * Fixed target language order for CSV smart-fallback translation.
     */
    public function getPresetLanguages(): array
    {
        return config('audio.csv_preset_languages');
    }
}

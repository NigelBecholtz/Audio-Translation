<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleOAuthService;

class LanguageDetectionService
{
    private GoogleOAuthService $oauthService;
    private ?string $projectId = null;

    public function __construct(GoogleOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
        
        // Get project ID from service account JSON file
        $serviceAccountPath = storage_path('app/google-service-account.json');
        if (file_exists($serviceAccountPath)) {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            $this->projectId = $serviceAccount['project_id'] ?? null;
        }
        
        // Fallback to config if not found
        if (empty($this->projectId)) {
            $this->projectId = config('services.google_cloud.project_id');
        }
        
        if (empty($this->projectId)) {
            throw new \Exception('Google Cloud project ID not configured');
        }
    }

    /**
     * Detect language of text using Google Cloud Translation API
     *
     * @param string $text Text to detect language for
     * @return string Detected language code
     * @throws \Exception
     */
    public function detectLanguage(string $text): string
    {
        if (empty(trim($text))) {
            Log::info('Empty text provided, defaulting to English');
            return 'en'; // Default to English for empty text
        }

        try {
            Log::info('Starting language detection', [
                'text_length' => strlen($text),
                'text_sample' => substr($text, 0, 100)
            ]);
            
            // Get OAuth2 access token
            Log::info('Getting OAuth2 access token for language detection');
            $accessToken = $this->oauthService->getAccessToken();
            
            // Google Cloud Translation API v3 detect language endpoint
            $endpoint = "https://translation.googleapis.com/v3/projects/{$this->projectId}:detectLanguage";
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'content' => $text,
                ]);

            if (!$response->successful()) {
                Log::error('Language detection failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 'en'; // Fallback to English
            }

            $data = $response->json();
            $detectedLanguage = $data['languageCode'] ?? 'en';
            
            Log::info('Language detected', [
                'text' => substr($text, 0, 100) . '...',
                'detected_language' => $detectedLanguage
            ]);

            return $detectedLanguage;

        } catch (\Exception $e) {
            Log::error('Language detection error', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100) . '...'
            ]);
            return 'en'; // Fallback to English
        }
    }

    /**
     * Get most popular TTS languages for translation
     *
     * @return array Array of popular language codes
     */
    public function getPopularLanguages(): array
    {
        return [
            'en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh', 
            'ar', 'hi', 'nl', 'pl', 'tr', 'sv', 'da', 'no', 'fi', 'cs', 
            'hu', 'ro', 'bg', 'hr', 'sl', 'el', 'uk', 'lv', 'lt', 'et', 'ca'
        ];
    }

    /**
     * Get preset language configuration
     * Order: EN | ES | DE | FR | IT | NL | RO | EL | SQ | SK | LV | BG | FI | CA
     *
     * @return array Array of language codes in specific order
     */
    public function getPresetLanguages(): array
    {
        return [
            'en',    // English
            'es',    // Spanish
            'es_AR', // Spanish (Argentina)
            'de',    // German
            'fr',    // French
            'it',    // Italian
            'nl',    // Dutch
            'ro',    // Romanian
            'el',    // Greek
            'gr',    // Greek (alternative)
            'sq',    // Albanian
            'al',    // Albanian (alternative)
            'sk',    // Slovak
            'lv',    // Latvian
            'bg',    // Bulgarian
            'fi',    // Finnish
            'ca'     // Catalan
        ];
    }

    /**
     * Check if detected language is already in the popular languages list
     *
     * @param string $detectedLanguage
     * @return bool
     */
    public function isPopularLanguage(string $detectedLanguage): bool
    {
        return in_array($detectedLanguage, $this->getPopularLanguages());
    }
}

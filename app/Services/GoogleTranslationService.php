<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleOAuthService;

class GoogleTranslationService
{
    private $oauthService;
    private $projectId;
    
    // Language code mapping voor Google Translate API
    private array $languageMapping = [
        'es_AR' => 'es', // Spanish (Argentina) -> Spanish
        'gr' => 'el',    // Greek code mapping
        'al' => 'sq',    // Albanian code mapping
        'ca' => 'ca',    // Catalan
        'fr' => 'fr',
        'de' => 'de',
        'it' => 'it',
        'nl' => 'nl',
        'ro' => 'ro',
        'sk' => 'sk',
        'lv' => 'lv',
        'bg' => 'bg',
        'fi' => 'fi',
    ];

    public function __construct(GoogleOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
        // Extract project ID from service account email
        $email = config('gemini.service_account.client_email');
        if ($email) {
            // Format: xxx@project-id.iam.gserviceaccount.com
            preg_match('/@(.+?)\.iam\.gserviceaccount\.com/', $email, $matches);
            $this->projectId = $matches[1] ?? 'audio-translation';
        } else {
            $this->projectId = config('services.google_cloud.project_id', 'audio-translation');
        }
    }

    /**
     * Translate a single text to target language
     *
     * @param string $text Text to translate
     * @param string $targetLanguage Target language code (from CSV header)
     * @return string Translated text
     * @throws \Exception
     */
    public function translateText(string $text, string $targetLanguage): string
    {
        if (empty($text)) {
            return '';
        }

        $result = $this->translateBatch([$text], $targetLanguage);
        return $result[0] ?? $text;
    }

    /**
     * Translate multiple texts to target language (batch processing)
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @return array Array of translated texts
     * @throws \Exception
     */
    public function translateBatch(array $texts, string $targetLanguage): array
    {
        if (empty($texts)) {
            return [];
        }

        // Filter out empty texts but keep track of positions
        $nonEmptyTexts = [];
        $positions = [];
        foreach ($texts as $index => $text) {
            if (!empty($text)) {
                $nonEmptyTexts[] = $text;
                $positions[] = $index;
            }
        }

        if (empty($nonEmptyTexts)) {
            return array_fill(0, count($texts), '');
        }

        try {
            // Map language code
            $googleLangCode = $this->languageMapping[$targetLanguage] ?? $targetLanguage;
            
            // Get OAuth2 access token
            $accessToken = $this->oauthService->getAccessToken();
            
            // Google Cloud Translation API v3 endpoint
            $endpoint = "https://translation.googleapis.com/v3/projects/{$this->projectId}:translateText";
            
            // Split into chunks if needed (max 128 texts per request)
            $chunks = array_chunk($nonEmptyTexts, 100);
            $allTranslations = [];
            
            foreach ($chunks as $chunk) {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpoint, [
                        'contents' => $chunk,
                        'targetLanguageCode' => $googleLangCode,
                        'sourceLanguageCode' => 'en',
                    ]);

                if (!$response->successful()) {
                    Log::error('Google Translation API failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'target_language' => $googleLangCode
                    ]);
                    throw new \Exception('Translation API failed: ' . $response->body());
                }

                $data = $response->json();
                
                if (!isset($data['translations'])) {
                    throw new \Exception('No translations returned from API');
                }

                foreach ($data['translations'] as $translation) {
                    $allTranslations[] = $translation['translatedText'] ?? '';
                }
                
                // Rate limiting: small delay between chunks
                if (count($chunks) > 1) {
                    usleep(500000); // 0.5 second
                }
            }

            // Reconstruct full array with empty strings in original positions
            $result = array_fill(0, count($texts), '');
            foreach ($positions as $idx => $originalPosition) {
                $result[$originalPosition] = $allTranslations[$idx] ?? '';
            }

            Log::info('Batch translation completed', [
                'count' => count($nonEmptyTexts),
                'target_language' => $googleLangCode
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Translation batch failed', [
                'target_language' => $targetLanguage,
                'texts_count' => count($texts),
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get supported language codes from CSV headers
     *
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return array_keys($this->languageMapping);
    }

    /**
     * Check if translation service is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->oauthService->isConfigured();
    }
}


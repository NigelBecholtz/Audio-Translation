<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleOAuthService;

class GoogleTranslationService
{
    private $oauthService;
    private $projectId;
    
    // Language code mapping voor Google Translate API (TTS compatible)
    private array $languageMapping = [
        'en' => 'en',    // English
        'es' => 'es',    // Spanish
        'es_AR' => 'es-AR', // Spanish (Argentina)
        'sq' => 'sq',    // Albanian (AL)
        'al' => 'sq',    // Albanian (AL) - alternative code
        'bg' => 'bg',    // Bulgarian
        'sk' => 'sk',    // Slovak
        'lv' => 'lv',    // Latvian
        'fi' => 'fi',    // Finnish
        'el' => 'el',    // Greek (GR)
        'gr' => 'el',    // Greek (GR) - alternative code
        'nl' => 'nl',    // Dutch
        'fr' => 'fr',    // French
        'it' => 'it',    // Italian
        'ro' => 'ro',    // Romanian
        'ca' => 'ca',    // Catalan
        // Additional languages
        'de' => 'de',    // German
        'pt' => 'pt',    // Portuguese
        'ru' => 'ru',    // Russian
        'ja' => 'ja',    // Japanese
        'ko' => 'ko',    // Korean
        'zh' => 'zh',    // Chinese
        'ar' => 'ar',    // Arabic
        'hi' => 'hi',    // Hindi
        'pl' => 'pl',    // Polish
        'tr' => 'tr',    // Turkish
        'sv' => 'sv',    // Swedish
        'da' => 'da',    // Danish
        'no' => 'no',    // Norwegian
        'cs' => 'cs',    // Czech
        'hu' => 'hu',    // Hungarian
        'hr' => 'hr',    // Croatian
        'sl' => 'sl',    // Slovenian
        'uk' => 'uk',    // Ukrainian
        'lt' => 'lt',    // Lithuanian
        'et' => 'et',    // Estonian
    ];

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
            throw new \Exception('Google Cloud project ID not configured. Please set GOOGLE_CLOUD_PROJECT_ID in .env or ensure google-service-account.json contains project_id.');
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
            
            // Split into chunks if needed (max 1000 texts per request for better performance)
            $chunks = array_chunk($nonEmptyTexts, 1000);
            $allTranslations = [];
            
            foreach ($chunks as $chunk) {
                $response = Http::timeout(600)
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
                    $translatedText = $translation['translatedText'] ?? '';
                    
                    // Decode HTML entities to proper UTF-8 characters
                    $translatedText = html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    $allTranslations[] = $translatedText;
                }
                
                // Rate limiting: minimal delay for faster processing
                if (count($chunks) > 1) {
                    usleep(100000); // 0.1 second for faster processing
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


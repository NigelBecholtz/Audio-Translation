<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleOAuthService;

class GeminiTtsService
{
    private $oauthService;
    private $baseUrl;
    private $timeout;

    public function __construct()
    {
        $this->oauthService = new GoogleOAuthService();
        $this->baseUrl = config('gemini.base_url');
        $this->timeout = config('gemini.timeout', 120);
    }

    /**
     * Generate audio from text using Gemini 2.5 Pro TTS
     *
     * @param string $text
     * @param string $language
     * @param string $voice
     * @param string|null $styleInstruction
     * @return string Path to the generated audio file
     * @throws \Exception
     */
    public function generateAudio(string $text, string $language, ?string $voice = null, ?string $styleInstruction = null): string
    {
        try {
            Log::info('=== GEMINI TTS GENERATION DEBUG ===');
            Log::info('Language: ' . $language);
            Log::info('Text length: ' . strlen($text));

            // Get voice for language if not specified
            if (!$voice) {
                $voice = $this->getVoiceForLanguage($language);
            }
            
            Log::info('Selected voice: ' . $voice);

            // Get OAuth2 access token
            $accessToken = $this->oauthService->getAccessToken();
            
            // Prepare TTS payload
            $ttsPayload = [
                'input' => [
                    'text' => $text
                ],
                'voice' => [
                    'languageCode' => $this->getLanguageCode($language),
                    'name' => $voice,
                    'model_name' => 'gemini-2.5-pro-tts'
                ],
                'audioConfig' => [
                    'audioEncoding' => 'MP3',
                    'sampleRateHertz' => 24000
                ]
            ];

            // Add style instruction as prompt if provided
            if (!empty($styleInstruction)) {
                $ttsPayload['input']['prompt'] = $styleInstruction;
            }

            Log::info('Calling Gemini TTS API...');
            
            $response = Http::timeout($this->timeout)
                ->retry(2, 1000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ])
                ->post('https://texttospeech.googleapis.com/v1/text:synthesize', $ttsPayload);

            if (!$response->successful()) {
                throw new \Exception('Gemini TTS API request failed: ' . $response->body());
            }

            $responseData = $response->json();
            
            // Extract audio content from response
            if (!isset($responseData['audioContent'])) {
                throw new \Exception('No audio content received from Gemini TTS API');
            }

            $audioContent = base64_decode($responseData['audioContent']);

            // Save the audio file
            $filename = 'gemini_tts_' . time() . '.mp3';
            $path = 'audio/' . $filename;
            
            Storage::disk('public')->put($path, $audioContent);
            
            Log::info('Gemini TTS audio generated successfully', [
                'language' => $language,
                'voice' => $voice,
                'file_path' => $path
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('Gemini TTS generation failed', [
                'language' => $language,
                'voice' => $voice,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Gemini TTS generation failed: ' . $e->getMessage());
        }
    }

    private function getVoiceForLanguage(string $language): string
    {
        $voiceMapping = config('gemini.tts.voice_mapping', []);
        return $voiceMapping[$language] ?? config('gemini.tts.default_voice', 'Kore');
    }

    private function getLanguageCode(string $language): string
    {
        $languageMap = [
            'en' => 'en-US', 'es' => 'es-ES', 'fr' => 'fr-FR', 'de' => 'de-DE',
            'it' => 'it-IT', 'pt' => 'pt-PT', 'ru' => 'ru-RU', 'ja' => 'ja-JP',
            'ko' => 'ko-KR', 'zh' => 'zh-CN', 'ar' => 'ar-SA', 'hi' => 'hi-IN',
            'nl' => 'nl-NL', 'sv' => 'sv-SE', 'da' => 'da-DK', 'no' => 'nb-NO',
            'fi' => 'fi-FI', 'pl' => 'pl-PL', 'cs' => 'cs-CZ', 'sk' => 'sk-SK',
            'hu' => 'hu-HU', 'ro' => 'ro-RO', 'bg' => 'bg-BG', 'hr' => 'hr-HR',
            'sl' => 'sl-SI', 'el' => 'el-GR', 'tr' => 'tr-TR', 'uk' => 'uk-UA',
            'lv' => 'lv-LV', 'lt' => 'lt-LT', 'et' => 'et-EE', 'ca' => 'ca-ES',
        ];

        return $languageMap[$language] ?? 'en-US';
    }

    public function isConfigured(): bool
    {
        return $this->oauthService->isConfigured();
    }
}

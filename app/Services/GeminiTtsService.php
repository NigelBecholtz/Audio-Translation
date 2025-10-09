<?php

namespace App\Services;

use App\Constants\AudioConstants;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\GoogleOAuthService;
use App\Services\SimpleTtsService;
use App\Services\RateLimiter;

class GeminiTtsService
{
    private $oauthService;
    private $baseUrl;
    private $timeout;
    private $fallbackService;
    private $rateLimiter;

    public function __construct()
    {
        $this->oauthService = new GoogleOAuthService();
        $this->baseUrl = config('gemini.base_url');
        $this->timeout = config('gemini.timeout', 120);
        $this->fallbackService = new SimpleTtsService();
        $this->rateLimiter = new RateLimiter();
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
            if (app()->environment('local')) {
                Log::info('Gemini TTS: Generating audio', [
                    'language' => $language,
                    'text_length' => strlen($text)
                ]);
            }

            // Get voice for language if not specified
            if (!$voice) {
                $voice = $this->getVoiceForLanguage($language);
            }

            // Check if text needs chunking
            $chunkSize = AudioConstants::TTS_CHUNK_SIZE_BYTES;
            
            if (strlen($text) <= $chunkSize && strlen($styleInstruction ?? '') <= $chunkSize) {
                // Single chunk - direct API call
                return $this->generateSingleChunk($text, $language, $voice, $styleInstruction);
            } else {
                // Multiple chunks needed - split and concatenate
                Log::warning('Text exceeds chunk size - using chunking (voice consistency may vary)', [
                    'chunk_size' => $chunkSize
                ]);
                return $this->generateWithChunking($text, $language, $voice, $styleInstruction);
            }

        } catch (\Exception $e) {
            Log::error('Gemini TTS generation failed, trying fallback', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);
            
            // Try OpenAI TTS fallback
            try {
                return $this->fallbackService->generateAudio($text, $language, $voice, $styleInstruction);
            } catch (\Exception $fallbackError) {
                Log::error('Both Gemini and OpenAI TTS failed', [
                    'gemini_error' => $e->getMessage(),
                    'openai_error' => $fallbackError->getMessage()
                ]);
                throw new \Exception('Audio generation failed: ' . $e->getMessage() . ' (Fallback also failed)');
            }
        }
    }

    private function generateSingleChunk(string $text, string $language, string $voice, ?string $styleInstruction): string
    {
        $maxChunkSize = AudioConstants::TTS_CHUNK_SIZE_BYTES;
        
        // Truncate if needed
        if (strlen($text) > $maxChunkSize) {
            $text = substr($text, 0, $maxChunkSize);
        }
        if (!empty($styleInstruction) && strlen($styleInstruction) > $maxChunkSize) {
            $styleInstruction = substr($styleInstruction, 0, $maxChunkSize);
        }

        // Rate limiting: Max 60 requests per minute for Gemini TTS
        $rateLimitKey = 'gemini_tts_' . auth()->id();
        $maxAttempts = config('gemini.rate_limit.max_attempts', 60);
        $decayMinutes = config('gemini.rate_limit.decay_minutes', 1);
        
        try {
            return $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $decayMinutes, function() use ($text, $language, $voice, $styleInstruction) {
                return $this->executeTtsRequest($text, $language, $voice, $styleInstruction);
            });
        } catch (\Exception $e) {
            Log::warning('Gemini TTS rate limit hit, using fallback', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            // Use fallback if rate limited
            return $this->fallbackService->generateAudio($text, $language, $voice, $styleInstruction);
        }
    }
    
    private function executeTtsRequest(string $text, string $language, string $voice, ?string $styleInstruction): string
    {
        $accessToken = $this->oauthService->getAccessToken();
        
        // Capitalize first letter of voice name (Gemini expects proper case)
        $voiceName = ucfirst(strtolower($voice));
        
        $ttsPayload = [
            'input' => ['text' => $text],
            'voice' => [
                'languageCode' => $this->getLanguageCode($language),
                'name' => $voiceName,
                'model_name' => 'gemini-2.5-pro-tts'
            ],
            'audioConfig' => [
                'audioEncoding' => 'MP3',
                'sampleRateHertz' => AudioConstants::TTS_SAMPLE_RATE
            ]
        ];

        if (!empty($styleInstruction)) {
            $ttsPayload['input']['prompt'] = $styleInstruction;
        }

        $response = Http::timeout($this->timeout)
            ->retry(2, 1000)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->post('https://texttospeech.googleapis.com/v1/text:synthesize', $ttsPayload);

        if (!$response->successful()) {
            throw new \Exception('Gemini TTS API failed: ' . $response->body());
        }

        $responseData = $response->json();
        
        if (!isset($responseData['audioContent'])) {
            throw new \Exception('No audio content received');
        }

        $audioContent = base64_decode($responseData['audioContent']);
        $filename = 'gemini_tts_' . time() . '_' . rand(1000, 9999) . '.mp3';
        $path = 'audio/' . $filename;
        
        Storage::disk('public')->put($path, $audioContent);

        return $path;
    }

    private function generateWithChunking(string $text, string $language, string $voice, ?string $styleInstruction): string
    {
        // Simple sentence-based chunking
        $chunks = $this->chunkText($text, AudioConstants::TTS_CHUNK_SIZE_BYTES);
        $audioPaths = [];

        foreach ($chunks as $index => $chunk) {
            $audioPaths[] = $this->generateSingleChunk($chunk, $language, $voice, $styleInstruction);
            
            // Delay between chunks
            if ($index < count($chunks) - 1) {
                sleep(AudioConstants::TTS_CHUNK_DELAY_SECONDS);
            }
        }

        // Concatenate chunks
        return $this->concatenateAudio($audioPaths);
    }

    private function chunkText(string $text, int $maxBytes): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk) + strlen($sentence) <= $maxBytes) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            } else {
                if ($currentChunk) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $sentence;
            }
        }

        if ($currentChunk) {
            $chunks[] = $currentChunk;
        }

        return $chunks ?: [$text];
    }

    private function concatenateAudio(array $audioPaths): string
    {
        if (count($audioPaths) === 1) {
            return $audioPaths[0];
        }

        // Simple binary concatenation (works for MP3)
        $concatenated = '';
        foreach ($audioPaths as $path) {
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                $concatenated .= file_get_contents($fullPath);
                unlink($fullPath); // Cleanup chunk
            }
        }

        $filename = 'gemini_tts_concat_' . time() . '.mp3';
        $path = 'audio/' . $filename;
        Storage::disk('public')->put($path, $concatenated);

        return $path;
    }

    private function getVoiceForLanguage(string $language): string
    {
        $voiceMapping = config('gemini.tts.voice_mapping', []);
        return $voiceMapping[$language] ?? config('gemini.tts.default_voice', 'Kore');
    }

    private function getLanguageCode(string $language): string
    {
        // Normalize to lowercase for matching
        $languageLower = strtolower($language);
        
        $languageMap = [
            // English variants (lowercase keys for matching)
            'en-us' => 'en-US',
            'en-gb' => 'en-GB',
            'en-au' => 'en-AU',
            'en-ca' => 'en-CA',
            'en-in' => 'en-IN',
            'en' => 'en-US', // Default to US English
            // Other languages
            'es' => 'es-ES', 'fr' => 'fr-FR', 'de' => 'de-DE',
            'it' => 'it-IT', 'pt' => 'pt-PT', 'ru' => 'ru-RU', 'ja' => 'ja-JP',
            'ko' => 'ko-KR', 'zh' => 'zh-CN', 'ar' => 'ar-SA', 'hi' => 'hi-IN',
            'nl' => 'nl-NL', 'sv' => 'sv-SE', 'da' => 'da-DK', 'no' => 'nb-NO',
            'fi' => 'fi-FI', 'pl' => 'pl-PL', 'cs' => 'cs-CZ', 'sk' => 'sk-SK',
            'hu' => 'hu-HU', 'ro' => 'ro-RO', 'bg' => 'bg-BG', 'hr' => 'hr-HR',
            'sl' => 'sl-SI', 'el' => 'el-GR', 'tr' => 'tr-TR', 'uk' => 'uk-UA',
            'lv' => 'lv-LV', 'lt' => 'lt-LT', 'et' => 'et-EE', 'ca' => 'ca-ES',
        ];

        return $languageMap[$languageLower] ?? 'en-US';
    }

    public function isConfigured(): bool
    {
        return $this->oauthService->isConfigured();
    }
}

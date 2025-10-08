<?php

namespace App\Services;

use App\Services\GeminiTtsService;
use App\Services\FFmpegService;
use Illuminate\Support\Facades\Log;

class AudioProcessingService
{
    protected $geminiTts;
    protected $creditService;
    protected $ffmpegService;

    public function __construct()
    {
        $this->geminiTts = new GeminiTtsService();
        $this->creditService = new CreditService();
        $this->ffmpegService = new FFmpegService();
    }

    /**
     * Generate audio from text using Gemini TTS
     *
     * @param string $text
     * @param string $language
     * @param string|null $voice
     * @param string|null $styleInstruction
     * @return string Path to generated audio file
     * @throws \Exception
     */
    public function generateAudio(string $text, string $language, ?string $voice = null, ?string $styleInstruction = null): string
    {
        try {
            if (app()->environment('local')) {
                Log::info('Generating audio with Gemini TTS', [
                    'language' => $language,
                    'voice' => $voice,
                    'text_length' => strlen($text),
                    'has_style_instruction' => !empty($styleInstruction)
                ]);
            }
            
            $path = $this->geminiTts->generateAudio($text, $language, $voice, $styleInstruction);
            
            if (app()->environment('local')) {
                Log::info('Audio generated successfully', [
                    'file_path' => $path
                ]);
            }
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Audio generation failed', [
                'language' => $language,
                'voice' => $voice,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Audio generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Transcribe audio file using OpenAI Whisper
     *
     * @param \App\Models\AudioFile $audioFile
     * @return string Transcribed text
     * @throws \Exception
     */
    public function transcribeAudio($audioFile): string
    {
        try {
            // Update progress: Starting transcription
            $audioFile->update([
                'processing_stage' => 'transcribing',
                'processing_progress' => 10,
                'processing_message' => 'Preparing audio file...'
            ]);

            if (app()->environment('local')) {
                Log::info('Starting transcription', [
                    'file' => $audioFile->original_filename,
                    'language' => $audioFile->source_language
                ]);
            }

            // Process audio/video file with FFmpeg if available
            $processedPath = $audioFile->file_path;
            
            if ($this->ffmpegService->isInstalled()) {
                // Step 1: Extract audio from video files (MP4, etc)
                if ($this->ffmpegService->isVideoFile($audioFile->file_path)) {
                    $audioFile->update([
                        'processing_progress' => 15,
                        'processing_message' => 'Extracting audio from video...'
                    ]);

                    Log::info('Video file detected, extracting audio', [
                        'file' => $audioFile->original_filename
                    ]);
                    
                    $processedPath = $this->ffmpegService->extractAudioFromVideo($audioFile->file_path);
                    $audioFile->update(['file_path' => $processedPath]);
                }
                
                // Step 2: Compress audio if needed (>25MB)
                $originalSize = $this->ffmpegService->getFileSizeMB($processedPath);
                
                if ($originalSize > 25) {
                    $audioFile->update([
                        'processing_progress' => 20,
                        'processing_message' => 'Compressing large audio file...'
                    ]);

                    Log::info('File exceeds 25MB, compressing with FFmpeg', [
                        'original_size' => $originalSize . 'MB'
                    ]);
                    
                    $processedPath = $this->ffmpegService->compressIfNeeded($processedPath, 25);
                    
                    // Update database with compressed file path
                    $audioFile->update(['file_path' => $processedPath]);
                }
            }

            $audioFile->update([
                'processing_progress' => 30,
                'processing_message' => 'Transcribing audio with AI...'
            ]);

            $filePath = storage_path('app/public/' . $processedPath);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Audio file not found: ' . $filePath);
            }

            $response = \OpenAI\Laravel\Facades\OpenAI::audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($filePath, 'r'),
                'language' => $audioFile->source_language,
                'response_format' => 'text',
            ]);

            $transcription = is_string($response) ? $response : $response->text;
            
            if (empty($transcription)) {
                throw new \Exception('Transcription returned empty result');
            }

            $audioFile->update([
                'processing_progress' => 40,
                'processing_message' => 'Transcription completed!'
            ]);

            if (app()->environment('local')) {
                Log::info('Transcription completed', [
                    'length' => strlen($transcription)
                ]);
            }

            return $transcription;

        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'file' => $audioFile->original_filename,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Transcription failed: ' . $e->getMessage());
        }
    }

    /**
     * Translate text using OpenAI GPT
     *
     * @param string $text
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return string Translated text
     * @throws \Exception
     */
    public function translateText(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        try {
            if (app()->environment('local')) {
                Log::info('Starting translation', [
                    'from' => $sourceLanguage,
                    'to' => $targetLanguage,
                    'text_length' => strlen($text)
                ]);
            }

            $languageNames = [
                'en' => 'English', 'es' => 'Spanish', 'fr' => 'French',
                'de' => 'German', 'it' => 'Italian', 'pt' => 'Portuguese',
                'ru' => 'Russian', 'ja' => 'Japanese', 'ko' => 'Korean',
                'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi',
                'nl' => 'Dutch', 'sv' => 'Swedish', 'da' => 'Danish',
                'no' => 'Norwegian', 'fi' => 'Finnish', 'pl' => 'Polish',
                'cs' => 'Czech', 'sk' => 'Slovak', 'hu' => 'Hungarian',
                'ro' => 'Romanian', 'bg' => 'Bulgarian', 'hr' => 'Croatian',
                'sl' => 'Slovenian', 'el' => 'Greek', 'tr' => 'Turkish',
                'uk' => 'Ukrainian', 'lv' => 'Latvian', 'lt' => 'Lithuanian',
                'et' => 'Estonian', 'ca' => 'Catalan', 'eu' => 'Basque',
                'th' => 'Thai', 'vi' => 'Vietnamese', 'id' => 'Indonesian',
                'ms' => 'Malay', 'tl' => 'Tagalog', 'bn' => 'Bengali',
                'ta' => 'Tamil', 'te' => 'Telugu', 'ml' => 'Malayalam',
                'kn' => 'Kannada', 'gu' => 'Gujarati', 'pa' => 'Punjabi',
                'ur' => 'Urdu', 'si' => 'Sinhala', 'my' => 'Burmese',
                'km' => 'Khmer', 'lo' => 'Lao', 'mn' => 'Mongolian',
                'af' => 'Afrikaans', 'sw' => 'Swahili', 'am' => 'Amharic',
                'sq' => 'Albanian', 'hy' => 'Armenian', 'az' => 'Azerbaijani',
                'ka' => 'Georgian', 'he' => 'Hebrew', 'fa' => 'Persian',
                'ps' => 'Pashto', 'ne' => 'Nepali'
            ];

            $sourceLangName = $languageNames[$sourceLanguage] ?? $sourceLanguage;
            $targetLangName = $languageNames[$targetLanguage] ?? $targetLanguage;

            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a professional translator. Translate the following text from {$sourceLangName} to {$targetLangName}. Maintain the original tone, style, and formatting. Only return the translated text without any explanations or notes."
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 4000,
            ]);

            $translatedText = $response->choices[0]->message->content ?? '';
            
            if (empty($translatedText)) {
                throw new \Exception('Translation returned empty result');
            }

            if (app()->environment('local')) {
                Log::info('Translation completed', [
                    'translated_length' => strlen($translatedText)
                ]);
            }

            return $translatedText;

        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'from' => $sourceLanguage,
                'to' => $targetLanguage,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * Deduct credits from user account
     *
     * @param \App\Models\User $user
     * @param float $amount
     * @param string $description
     * @return void
     */
    public function deductCredits($user, float $amount = 0.5, string $description = 'Credits used for audio processing'): void
    {
        $this->creditService->deductCredit($user, $description, $amount);
    }
}


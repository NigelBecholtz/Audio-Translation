<?php

namespace App\Services;

use App\Constants\AudioConstants;
use App\Support\LanguageCode;
use Illuminate\Support\Facades\Log;

class AudioProcessingService
{
    protected $geminiTts;

    protected $creditService;

    protected $ffmpegService;

    public function __construct()
    {
        $this->geminiTts = new GeminiTtsService;
        $this->creditService = new CreditService;
        $this->ffmpegService = new FFmpegService;
    }

    /**
     * Generate audio from text using Gemini TTS
     *
     * @return string Path to generated audio file
     *
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
                    'has_style_instruction' => ! empty($styleInstruction),
                ]);
            }

            $path = $this->geminiTts->generateAudio($text, $language, $voice, $styleInstruction);

            if (app()->environment('local')) {
                Log::info('Audio generated successfully', [
                    'file_path' => $path,
                ]);
            }

            return $path;

        } catch (\Exception $e) {
            Log::error('Audio generation failed', [
                'language' => $language,
                'voice' => $voice,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Audio generation failed: '.$e->getMessage());
        }
    }

    /**
     * Transcribe audio file using OpenAI Whisper
     *
     * @param  \App\Models\AudioFile  $audioFile
     * @return string Transcribed text
     *
     * @throws \Exception
     */
    public function transcribeAudio($audioFile): string
    {
        try {
            // Update progress: Starting transcription
            $audioFile->update([
                'processing_stage' => 'transcribing',
                'processing_progress' => 10,
                'processing_message' => 'Preparing audio file...',
            ]);

            if (app()->environment('local')) {
                Log::info('Starting transcription', [
                    'file' => $audioFile->original_filename,
                    'language' => $audioFile->source_language,
                ]);
            }

            // Process audio/video file with FFmpeg if available
            $processedPath = $audioFile->file_path;

            if ($this->ffmpegService->isInstalled()) {
                // Step 1: Extract audio from video files (MP4, etc)
                if ($this->ffmpegService->isVideoFile($audioFile->file_path)) {
                    $audioFile->update([
                        'processing_progress' => 15,
                        'processing_message' => 'Extracting audio from video...',
                    ]);

                    Log::info('Video file detected, extracting audio', [
                        'file' => $audioFile->original_filename,
                    ]);

                    $processedPath = $this->ffmpegService->extractAudioFromVideo($audioFile->file_path);
                    $audioFile->update(['file_path' => $processedPath]);
                }

                // Step 2: Compress audio if needed
                $originalSize = $this->ffmpegService->getFileSizeMB($processedPath);

                if ($originalSize > AudioConstants::WHISPER_MAX_FILE_SIZE_MB) {
                    $audioFile->update([
                        'processing_progress' => 20,
                        'processing_message' => 'Compressing large audio file...',
                    ]);

                    Log::info('File exceeds 25MB, compressing with FFmpeg', [
                        'original_size' => $originalSize.'MB',
                    ]);

                    $processedPath = $this->ffmpegService->compressIfNeeded($processedPath, AudioConstants::WHISPER_MAX_FILE_SIZE_MB);

                    // Update database with compressed file path
                    $audioFile->update(['file_path' => $processedPath]);
                }
            }

            $audioFile->update([
                'processing_progress' => 30,
                'processing_message' => 'Transcribing audio with AI...',
            ]);

            $filePath = storage_path('app/public/'.$processedPath);

            if (! file_exists($filePath)) {
                throw new \Exception('Audio file not found: '.$filePath);
            }

            // Whisper only accepts ISO-639-1 codes (en), not regional codes (en-gb)
            $whisperLanguage = LanguageCode::base($audioFile->source_language);

            // Build transcription parameters
            $transcriptionParams = [
                'model' => 'whisper-1',
                'file' => fopen($filePath, 'r'),
                'language' => $whisperLanguage,
                'response_format' => 'text',
            ];

            // Add prompt hint for English variants to help with accent detection
            if ($whisperLanguage === 'en' && $audioFile->source_language !== 'en') {
                $accentHint = $this->getAccentHint($audioFile->source_language);
                if ($accentHint) {
                    $transcriptionParams['prompt'] = $accentHint;
                }
            }

            $response = \OpenAI\Laravel\Facades\OpenAI::audio()->transcribe($transcriptionParams);

            $transcription = is_string($response) ? $response : $response->text;

            if (empty($transcription)) {
                throw new \Exception('Transcription returned empty result');
            }

            $audioFile->update([
                'processing_progress' => 40,
                'processing_message' => 'Transcription completed!',
            ]);

            if (app()->environment('local')) {
                Log::info('Transcription completed', [
                    'length' => strlen($transcription),
                ]);
            }

            return $transcription;

        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'file' => $audioFile->original_filename,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Transcription failed: '.$e->getMessage());
        }
    }

    /**
     * Deduct credits from user account
     *
     * @param  \App\Models\User  $user
     */
    public function deductCredits($user, float $amount = 0.5, string $description = 'Credits used for audio processing'): void
    {
        $this->creditService->deductCredit($user, $description, $amount);
    }

    /**
     * Get accent hint prompt for Whisper API to help with English variant detection
     * This helps Whisper use the correct spelling and terminology
     *
     * @param  string  $languageCode  Extended language code (e.g., 'en-gb', 'en-us')
     * @return string|null Prompt hint or null if not applicable
     */
    private function getAccentHint(string $languageCode): ?string
    {
        $hints = [
            'en-gb' => 'This is British English. Use British spelling: colour, realise, centre, organise, etc.',
            'en-us' => 'This is American English. Use American spelling: color, realize, center, organize, etc.',
            'en-au' => 'This is Australian English. Use Australian/British spelling: colour, realise, centre, etc.',
            'en-ca' => 'This is Canadian English. Use Canadian spelling (mix of British and American).',
            'en-in' => 'This is Indian English. Use British spelling with Indian English terminology.',
        ];

        $code = strtolower(trim($languageCode));

        return $hints[$code] ?? null;
    }
}

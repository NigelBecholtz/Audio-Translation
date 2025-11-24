<?php

namespace App\Jobs;

use App\Models\AudioFile;
use App\Services\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAudioTranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AudioFile $audioFile
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        try {
            // Step 1: Translate text (skip if same language for accent improvement)
            $sourceBase = $this->getBaseLanguageCode($this->audioFile->source_language);
            $targetBase = $this->getBaseLanguageCode($this->audioFile->target_language);
            $isSameLanguage = ($sourceBase === $targetBase);
            
            if ($isSameLanguage) {
                // Same language - skip translation, use transcription directly for accent improvement
                $this->audioFile->update([
                    'status' => 'generating_audio',
                    'processing_stage' => 'generating_audio',
                    'processing_progress' => 60,
                    'processing_message' => 'Skipping translation (accent improvement mode)...'
                ]);
                
                $translatedText = $this->audioFile->transcription; // Use transcription as-is
            } else {
                // Different languages - translate
                $this->audioFile->update([
                    'status' => 'translating',
                    'processing_stage' => 'translating',
                    'processing_progress' => 60,
                    'processing_message' => 'Translating text with AI...'
                ]);

                $translatedText = $processingService->translateText(
                    $this->audioFile->transcription,
                    $this->audioFile->source_language,
                    $this->audioFile->target_language
                );
            }

            $this->audioFile->update([
                'translated_text' => $translatedText,
                'processing_progress' => 70,
                'processing_message' => $isSameLanguage ? 'Ready for audio generation!' : 'Translation completed!'
            ]);

            // Step 2: Generate audio with TTS
            $this->audioFile->update([
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 80,
                'processing_message' => $isSameLanguage ? 'Generating audio with AI voice (accent improvement)...' : 'Generating translated audio...'
            ]);

            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $this->audioFile->target_language,
                $this->audioFile->voice,
                $this->audioFile->style_instruction
            );

            $this->audioFile->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed',
                'processing_stage' => 'completed',
                'processing_progress' => 100,
                'processing_message' => 'Processing complete!'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $this->audioFile->user,
                config('stripe.default_cost_per_translation'),
                'Credits used for audio translation'
            );

        } catch (\Exception $e) {
            Log::error('Audio translation processing failed', [
                'audio_file_id' => $this->audioFile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->audioFile->update([
                'status' => 'failed',
                'processing_stage' => 'failed',
                'processing_progress' => 0,
                'processing_message' => 'Processing failed',
                'error_message' => $e->getMessage()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Audio translation job failed permanently', [
            'audio_file_id' => $this->audioFile->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->audioFile->update([
            'status' => 'failed',
            'processing_stage' => 'failed',
            'processing_progress' => 0,
            'processing_message' => 'Processing failed',
            'error_message' => 'Processing failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
        ]);
    }

    /**
     * Get base language code (e.g., 'en-gb' -> 'en', 'es' -> 'es')
     *
     * @param string $languageCode
     * @return string
     */
    private function getBaseLanguageCode(string $languageCode): string
    {
        $code = strtolower(trim($languageCode));
        
        // Extract base language code if it's in format 'xx-XX' or 'xx_XX'
        if (preg_match('/^([a-z]{2})(?:[-_][a-z]{2,})?$/i', $code, $matches)) {
            return strtolower($matches[1]);
        }
        
        return $code;
    }
}


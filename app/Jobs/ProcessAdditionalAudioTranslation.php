<?php

namespace App\Jobs;

use App\Models\AudioTranslation;
use App\Services\AudioProcessingService;
use App\Services\GoogleTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessAdditionalAudioTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

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
    public $timeout = 600; // 10 minutes

    /**
     * The ID of the audio translation to process.
     */
    public int $audioTranslationId;

    /**
     * Create a new job instance.
     */
    public function __construct(AudioTranslation $audioTranslation)
    {
        $this->audioTranslationId = $audioTranslation->id;
    }

    /**
     * Execute the job.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        try {
            // Load the audio translation fresh from database
            $audioTranslation = AudioTranslation::find($this->audioTranslationId);
            
            // Check if the translation still exists
            if (!$audioTranslation) {
                Log::warning('Audio translation no longer exists, skipping job', [
                    'audio_translation_id' => $this->audioTranslationId,
                ]);
                return;
            }

            Log::info('Starting additional audio translation processing', [
                'audio_translation_id' => $audioTranslation->id,
                'target_language' => $audioTranslation->target_language,
            ]);

            // Step 1: Translate the text to the target language
            $audioTranslation->update([
                'status' => 'translating',
                'processing_stage' => 'translating',
                'processing_progress' => 25,
                'processing_message' => 'Translating text to ' . strtoupper($audioTranslation->target_language) . '...'
            ]);

            $translationService = app(GoogleTranslationService::class);
            
            Log::info('Calling translation service', [
                'audio_translation_id' => $audioTranslation->id,
                'source_language' => $audioTranslation->audioFile->source_language,
                'target_language' => $audioTranslation->target_language,
            ]);

            $translatedText = $translationService->translate(
                $audioTranslation->audioFile->transcription,
                $audioTranslation->audioFile->source_language,
                $audioTranslation->target_language
            );

            Log::info('Translation completed', [
                'audio_translation_id' => $audioTranslation->id,
                'translated_text_length' => strlen($translatedText),
            ]);

            // Step 2: Generate audio with TTS
            $audioTranslation->update([
                'translated_text' => $translatedText,
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 60,
                'processing_message' => 'Translation completed! Generating audio with AI voice...'
            ]);

            $styleInstruction = $audioTranslation->style_instruction
                ?: $audioTranslation->audioFile->style_instruction;

            Log::info('Starting audio generation', [
                'audio_translation_id' => $audioTranslation->id,
                'voice' => $audioTranslation->voice,
            ]);

            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $audioTranslation->target_language,
                $audioTranslation->voice,
                $styleInstruction
            );

            Log::info('Audio generation completed', [
                'audio_translation_id' => $audioTranslation->id,
                'audio_path' => $translatedAudioPath,
            ]);

            // Step 3: Complete the translation
            $audioTranslation->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed',
                'processing_stage' => 'completed',
                'processing_progress' => 100,
                'processing_message' => 'Translation completed successfully!'
            ]);

            Log::info('Additional audio translation completed successfully', [
                'audio_translation_id' => $audioTranslation->id,
            ]);

            // Deduct credits
            $processingService->deductCredits(
                $audioTranslation->audioFile->user,
                config('stripe.default_cost_per_translation'),
                'Credits used for additional audio translation to ' . strtoupper($audioTranslation->target_language)
            );

        } catch (\Exception $e) {
            // Try to update the translation if it still exists
            $audioTranslation = AudioTranslation::find($this->audioTranslationId);
            if ($audioTranslation) {
                Log::error('Additional audio translation processing failed', [
                    'audio_translation_id' => $audioTranslation->id,
                    'error' => $e->getMessage(),
                ]);

                $audioTranslation->update([
                    'status' => 'failed',
                    'processing_stage' => 'failed',
                    'processing_progress' => 0,
                    'processing_message' => 'Processing failed: ' . $e->getMessage(),
                    'error_message' => $e->getMessage(),
                ]);
            } else {
                Log::error('Additional audio translation processing failed and model no longer exists', [
                    'audio_translation_id' => $this->audioTranslationId,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        try {
            $audioTranslation = AudioTranslation::find($this->audioTranslationId);
            
            if ($audioTranslation) {
                Log::error('Additional audio translation job failed permanently', [
                    'audio_translation_id' => $audioTranslation->id,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'attempts' => $this->attempts()
                ]);

                $audioTranslation->update([
                    'status' => 'failed',
                    'processing_stage' => 'failed',
                    'processing_progress' => 0,
                    'processing_message' => 'Processing failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
                    'error_message' => $exception->getMessage(),
                ]);
            } else {
                Log::warning('Additional audio translation job failed but model no longer exists', [
                    'audio_translation_id' => $this->audioTranslationId,
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts()
                ]);
            }
        } catch (\Exception $e) {
            // Model doesn't exist or can't be updated, just log it
            Log::error('Additional audio translation job failed and could not update model', [
                'audio_translation_id' => $this->audioTranslationId,
                'error' => $exception->getMessage(),
                'update_error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);
        }
    }
}

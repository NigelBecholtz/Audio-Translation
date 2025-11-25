<?php

namespace App\Jobs;

use App\Models\AudioTranslation;
use App\Services\AudioProcessingService;
use App\Services\GoogleTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAdditionalAudioTranslation implements ShouldQueue
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
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AudioTranslation $audioTranslation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        try {
            Log::info('Starting additional audio translation processing', [
                'audio_translation_id' => $this->audioTranslation->id,
                'target_language' => $this->audioTranslation->target_language,
            ]);

            // Step 1: Translate the text to the target language
            $this->audioTranslation->update([
                'status' => 'translating',
                'processing_stage' => 'translating',
                'processing_progress' => 25,
                'processing_message' => 'Translating text to ' . strtoupper($this->audioTranslation->target_language) . '...'
            ]);

            $translationService = app(GoogleTranslationService::class);
            
            Log::info('Calling translation service', [
                'audio_translation_id' => $this->audioTranslation->id,
                'source_language' => $this->audioTranslation->audioFile->source_language,
                'target_language' => $this->audioTranslation->target_language,
            ]);

            $translatedText = $translationService->translate(
                $this->audioTranslation->audioFile->transcription,
                $this->audioTranslation->audioFile->source_language,
                $this->audioTranslation->target_language
            );

            Log::info('Translation completed', [
                'audio_translation_id' => $this->audioTranslation->id,
                'translated_text_length' => strlen($translatedText),
            ]);

            // Step 2: Generate audio with TTS
            $this->audioTranslation->update([
                'translated_text' => $translatedText,
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 60,
                'processing_message' => 'Translation completed! Generating audio with AI voice...'
            ]);

            $styleInstruction = $this->audioTranslation->style_instruction
                ?: $this->audioTranslation->audioFile->style_instruction;

            Log::info('Starting audio generation', [
                'audio_translation_id' => $this->audioTranslation->id,
                'voice' => $this->audioTranslation->voice,
            ]);

            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $this->audioTranslation->target_language,
                $this->audioTranslation->voice,
                $styleInstruction
            );

            Log::info('Audio generation completed', [
                'audio_translation_id' => $this->audioTranslation->id,
                'audio_path' => $translatedAudioPath,
            ]);

            // Step 3: Complete the translation
            $this->audioTranslation->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed',
                'processing_stage' => 'completed',
                'processing_progress' => 100,
                'processing_message' => 'Translation completed successfully!'
            ]);

            Log::info('Additional audio translation completed successfully', [
                'audio_translation_id' => $this->audioTranslation->id,
            ]);

            // Deduct credits
            $processingService->deductCredits(
                $this->audioTranslation->audioFile->user,
                config('stripe.default_cost_per_translation'),
                'Credits used for additional audio translation to ' . strtoupper($this->audioTranslation->target_language)
            );

        } catch (\Exception $e) {
            Log::error('Additional audio translation processing failed', [
                'audio_translation_id' => $this->audioTranslation->id,
                'error' => $e->getMessage(),
            ]);

            $this->audioTranslation->update([
                'status' => 'failed',
                'processing_stage' => 'failed',
                'processing_progress' => 0,
                'processing_message' => 'Processing failed: ' . $e->getMessage(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Additional audio translation job failed permanently', [
            'audio_translation_id' => $this->audioTranslation->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);

        $this->audioTranslation->update([
            'status' => 'failed',
            'processing_stage' => 'failed',
            'processing_progress' => 0,
            'processing_message' => 'Processing failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}

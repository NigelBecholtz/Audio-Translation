<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAdditionalAudioTranslation implements ShouldQueue
{
    use Queueable;

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
            // Step 1: Translate the text to the target language
            $this->audioTranslation->update([
                'status' => 'translating',
            ]);

            $translationService = app(GoogleTranslationService::class);
            $translatedText = $translationService->translate(
                $this->audioTranslation->audioFile->transcription,
                $this->audioTranslation->audioFile->source_language,
                $this->audioTranslation->target_language
            );

            // Step 2: Generate audio with TTS
            $this->audioTranslation->update([
                'translated_text' => $translatedText,
                'status' => 'generating_audio',
            ]);

            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $this->audioTranslation->voice,
                $this->audioTranslation->target_language
            );

            // Step 3: Complete the translation
            $this->audioTranslation->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed',
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
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

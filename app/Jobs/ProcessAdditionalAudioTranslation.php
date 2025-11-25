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

            $styleInstruction = $this->audioTranslation->style_instruction
                ?: $this->audioTranslation->audioFile->style_instruction;

            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $this->audioTranslation->target_language,
                $this->audioTranslation->voice,
                $styleInstruction
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

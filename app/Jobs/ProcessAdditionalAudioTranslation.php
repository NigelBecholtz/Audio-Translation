<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesProcessingFailure;
use App\Models\AudioTranslation;
use App\Services\AudioProcessingService;
use App\Services\GoogleTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessAdditionalAudioTranslation implements ShouldQueue
{
    use Dispatchable, HandlesProcessingFailure, InteractsWithQueue, Queueable;

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
     * The ID of the audio translation to process (the record may be deleted while queued).
     */
    public int $audioTranslationId;

    public function __construct(AudioTranslation $audioTranslation)
    {
        $this->audioTranslationId = $audioTranslation->id;
    }

    /**
     * Translate the transcription into an extra language and generate its audio.
     */
    public function handle(AudioProcessingService $processingService, GoogleTranslationService $translationService): void
    {
        $audioTranslation = $this->processingRecord();

        if (! $audioTranslation) {
            Log::warning('Audio translation no longer exists, skipping job', [
                'audio_translation_id' => $this->audioTranslationId,
            ]);

            return;
        }

        $audioFile = $audioTranslation->audioFile;

        $audioTranslation->update([
            'status' => 'translating',
            'processing_stage' => 'translating',
            'processing_progress' => 25,
            'processing_message' => 'Translating text to '.strtoupper($audioTranslation->target_language).'...',
        ]);

        // The transcription is written in the audio's source language
        $translatedText = $translationService->translateText(
            $audioFile->transcription,
            $audioTranslation->target_language,
            $audioFile->source_language
        );

        $audioTranslation->update([
            'translated_text' => $translatedText,
            'status' => 'generating_audio',
            'processing_stage' => 'generating_audio',
            'processing_progress' => 60,
            'processing_message' => 'Translation completed! Generating audio with AI voice...',
        ]);

        $translatedAudioPath = $processingService->generateAudio(
            $translatedText,
            $audioTranslation->target_language,
            $audioTranslation->voice,
            $audioTranslation->style_instruction ?: $audioFile->style_instruction
        );

        $audioTranslation->update([
            'translated_audio_path' => $translatedAudioPath,
            'status' => 'completed',
            'processing_stage' => 'completed',
            'processing_progress' => 100,
            'processing_message' => 'Translation completed successfully!',
        ]);

        $processingService->deductCredits(
            $audioFile->user,
            config('stripe.default_cost_per_translation'),
            'Credits used for additional audio translation to '.strtoupper($audioTranslation->target_language)
        );
    }

    protected function processingRecord(): ?Model
    {
        return AudioTranslation::find($this->audioTranslationId);
    }
}

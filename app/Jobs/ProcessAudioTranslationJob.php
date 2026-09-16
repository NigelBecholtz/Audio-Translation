<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesProcessingFailure;
use App\Models\AudioFile;
use App\Services\GoogleTranslationService;
use App\Support\LanguageCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAudioTranslationJob implements ShouldQueue
{
    use Dispatchable, HandlesProcessingFailure, InteractsWithQueue, Queueable, SerializesModels;

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

    public function __construct(
        public AudioFile $audioFile
    ) {}

    /**
     * Translate the approved transcription, then wait for the user to approve TTS generation.
     */
    public function handle(GoogleTranslationService $translator): void
    {
        // Same base language (e.g. en-gb → en-us) is an accent improvement: keep the transcription as-is
        $isSameLanguage = LanguageCode::isSameLanguage(
            $this->audioFile->source_language,
            $this->audioFile->target_language
        );

        if ($isSameLanguage) {
            $this->audioFile->update([
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 60,
                'processing_message' => 'Skipping translation (accent improvement mode)...',
            ]);

            $translatedText = $this->audioFile->transcription;
        } else {
            $this->audioFile->update([
                'status' => 'translating',
                'processing_stage' => 'translating',
                'processing_progress' => 60,
                'processing_message' => 'Translating text with Google Translate...',
            ]);

            $translatedText = $translator->translateText(
                $this->audioFile->transcription,
                $this->audioFile->target_language,
                $this->audioFile->source_language
            );
        }

        $this->audioFile->update([
            'translated_text' => $translatedText,
            'status' => 'pending_tts_approval',
            'processing_stage' => 'pending_tts_approval',
            'processing_progress' => 100,
            'processing_message' => $isSameLanguage
                ? 'Translation completed! Ready to generate audio (accent improvement).'
                : 'Translation completed! Please review and approve to generate audio.',
        ]);
    }

    protected function processingRecord(): ?Model
    {
        return $this->audioFile;
    }
}

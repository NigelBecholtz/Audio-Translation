<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesProcessingFailure;
use App\Models\AudioFile;
use App\Services\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAudioTTSJob implements ShouldQueue
{
    use Dispatchable, HandlesProcessingFailure, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    public function __construct(
        public AudioFile $audioFile
    ) {}

    /**
     * Generate the translated audio after the user approved the translation.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        $this->audioFile->update([
            'status' => 'generating_audio',
            'processing_stage' => 'generating_audio',
            'processing_progress' => 80,
            'processing_message' => 'Generating translated audio...',
        ]);

        $translatedAudioPath = $processingService->generateAudio(
            $this->audioFile->translated_text,
            $this->audioFile->target_language,
            $this->audioFile->voice,
            $this->audioFile->style_instruction
        );

        $this->audioFile->update([
            'translated_audio_path' => $translatedAudioPath,
            'status' => 'completed',
            'processing_stage' => 'completed',
            'processing_progress' => 100,
            'processing_message' => 'Processing complete!',
        ]);

        $processingService->deductCredits(
            $this->audioFile->user,
            config('stripe.default_cost_per_translation'),
            'Credits used for audio translation'
        );
    }

    protected function processingRecord(): ?Model
    {
        return $this->audioFile;
    }
}

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

class ProcessAudioJob implements ShouldQueue
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
     * Transcribe the audio with Whisper, then wait for the user to approve the transcription.
     * Translation continues in ProcessAudioTranslationJob.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        $this->audioFile->update([
            'status' => 'transcribing',
            'processing_stage' => 'transcribing',
            'processing_progress' => 10,
            'processing_message' => 'Starting transcription...',
        ]);

        $transcription = $processingService->transcribeAudio($this->audioFile);

        $this->audioFile->update([
            'transcription' => $transcription,
            'status' => 'pending_approval',
            'processing_stage' => 'pending_approval',
            'processing_progress' => 50,
            'processing_message' => 'Transcription completed! Please review and approve to continue.',
        ]);
    }

    protected function processingRecord(): ?Model
    {
        return $this->audioFile;
    }
}

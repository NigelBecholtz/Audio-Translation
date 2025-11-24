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

class ProcessAudioTTSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    public function __construct(
        public AudioFile $audioFile
    ) {}

    public function handle(AudioProcessingService $processingService): void
    {
        try {
            // Step: Generate audio with TTS
            $this->audioFile->update([
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 80,
                'processing_message' => 'Generating translated audio...'
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
                'processing_message' => 'Processing complete!'
            ]);

            // Deduct credits
            $processingService->deductCredits(
                $this->audioFile->user,
                config('stripe.default_cost_per_translation'),
                'Credits used for audio translation'
            );

        } catch (\Exception $e) {
            Log::error('Audio TTS processing failed', [
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

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Audio TTS job failed permanently', [
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
}

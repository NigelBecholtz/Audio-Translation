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

class ProcessAudioJob implements ShouldQueue
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
            // Step 1: Transcribe with Whisper
            $this->audioFile->update([
                'status' => 'transcribing',
                'processing_stage' => 'transcribing',
                'processing_progress' => 10,
                'processing_message' => 'Starting transcription...'
            ]);

            $transcription = $processingService->transcribeAudio($this->audioFile);
            $this->audioFile->update([
                'transcription' => $transcription,
                'processing_progress' => 40,
                'processing_message' => 'Transcription completed!'
            ]);

            // Step 2: Translate text
            $this->audioFile->update([
                'status' => 'translating',
                'processing_stage' => 'translating',
                'processing_progress' => 50,
                'processing_message' => 'Translating text with AI...'
            ]);

            $translatedText = $processingService->translateText(
                $transcription,
                $this->audioFile->source_language,
                $this->audioFile->target_language
            );

            $this->audioFile->update([
                'translated_text' => $translatedText,
                'processing_progress' => 60,
                'processing_message' => 'Translation completed!'
            ]);

            // Step 3: Generate audio with TTS
            $this->audioFile->update([
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 70,
                'processing_message' => 'Generating translated audio...'
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
            Log::error('Audio processing failed', [
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
        Log::error('Audio processing job failed permanently', [
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
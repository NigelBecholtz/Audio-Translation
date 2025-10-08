<?php

namespace App\Jobs;

use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTextToAudioJob implements ShouldQueue
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
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public TextToAudio $textToAudio
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AudioProcessingService $processingService): void
    {
        try {
            // Generate audio with TTS
            $audioPath = $processingService->generateAudio(
                $this->textToAudio->text_content,
                $this->textToAudio->language,
                $this->textToAudio->voice,
                $this->textToAudio->style_instruction
            );
            
            $this->textToAudio->update([
                'audio_path' => $audioPath,
                'status' => 'completed'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $this->textToAudio->user,
                config('stripe.default_cost_per_translation', 0.5),
                'Credits used for text to audio conversion'
            );

        } catch (\Exception $e) {
            Log::error('Text to audio processing failed', [
                'text_to_audio_id' => $this->textToAudio->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->textToAudio->update([
                'status' => 'failed',
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
        Log::error('Text to audio job failed permanently', [
            'text_to_audio_id' => $this->textToAudio->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->textToAudio->update([
            'status' => 'failed',
            'error_message' => 'Processing failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
        ]);
    }
}
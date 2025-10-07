<?php

namespace App\Jobs;

use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTextToAudioJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 3; // Retry 3 times

    protected $textToAudioId;

    /**
     * Create a new job instance.
     */
    public function __construct($textToAudioId)
    {
        $this->textToAudioId = $textToAudioId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $textToAudioFile = TextToAudio::findOrFail($this->textToAudioId);
        $processingService = new AudioProcessingService();
        
        try {
            // Generate audio with TTS
            $audioPath = $processingService->generateAudio(
                $textToAudioFile->text_content,
                $textToAudioFile->language,
                $textToAudioFile->voice,
                $textToAudioFile->style_instruction
            );
            
            $textToAudioFile->update([
                'audio_path' => $audioPath,
                'status' => 'completed'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $textToAudioFile->user,
                0.5,
                'Credits used for text to audio conversion'
            );

        } catch (\Exception $e) {
            Log::error('Text to audio job failed', [
                'text_to_audio_id' => $this->textToAudioId,
                'error' => $e->getMessage()
            ]);
            $textToAudioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}

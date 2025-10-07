<?php

namespace App\Jobs;

use App\Models\AudioFile;
use App\Services\AudioProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAudioJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 3; // Retry 3 times

    protected $audioFileId;

    /**
     * Create a new job instance.
     */
    public function __construct($audioFileId)
    {
        $this->audioFileId = $audioFileId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audioFile = AudioFile::findOrFail($this->audioFileId);
        $processingService = new AudioProcessingService();
        
        try {
            // Step 1: Transcribe with Whisper
            $audioFile->update(['status' => 'transcribing']);
            $transcription = $processingService->transcribeAudio($audioFile);
            $audioFile->update(['transcription' => $transcription]);

            // Step 2: Translate text
            $audioFile->update(['status' => 'translating']);
            $translatedText = $processingService->translateText(
                $transcription,
                $audioFile->source_language,
                $audioFile->target_language
            );
            $audioFile->update(['translated_text' => $translatedText]);

            // Step 3: Generate audio with TTS
            $audioFile->update(['status' => 'generating_audio']);
            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $audioFile->target_language,
                $audioFile->voice,
                $audioFile->style_instruction
            );
            $audioFile->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $audioFile->user,
                0.5,
                'Credits used for audio translation'
            );

        } catch (\Exception $e) {
            Log::error('Audio processing job failed', [
                'audio_file_id' => $this->audioFileId,
                'error' => $e->getMessage()
            ]);
            $audioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
}

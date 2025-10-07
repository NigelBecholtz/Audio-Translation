<?php

namespace App\Jobs;

use App\Models\TextToAudio;
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
        Log::info('=== TEXT TO AUDIO PROCESSING JOB START ===');
        Log::info('Processing text to audio ID: ' . $this->textToAudioId);
        
        $textToAudioFile = TextToAudio::findOrFail($this->textToAudioId);
        Log::info('Text to audio file found');
        
        try {
            // Generate audio with TTS
            Log::info('Starting TTS generation...');
            $audioPath = $this->generateAudio($textToAudioFile->text_content, $textToAudioFile->language, $textToAudioFile->voice, $textToAudioFile->style_instruction);
            $textToAudioFile->update([
                'audio_path' => $audioPath,
                'status' => 'completed'
            ]);
            Log::info('TTS generation completed, path: ' . $audioPath);
            
            // Update user usage
            $this->updateUserUsage($textToAudioFile->user);
            
            Log::info('=== TEXT TO AUDIO PROCESSING JOB END - SUCCESS ===');

        } catch (\Exception $e) {
            Log::error('Text to audio processing job failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            $textToAudioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            Log::info('=== TEXT TO AUDIO PROCESSING JOB END - FAILED ===');
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    private function generateAudio($text, $language, $voice, $styleInstruction = null)
    {
        try {
            Log::info('=== GEMINI TTS GENERATION DEBUG ===');
            Log::info('Language: ' . $language);
            Log::info('Voice: ' . $voice);
            Log::info('Text length: ' . strlen($text));
            Log::info('Text preview: ' . substr($text, 0, 100) . '...');
            
            // Initialize Gemini TTS service
            $geminiTts = new \App\Services\GeminiTtsService();
            
            // Use Gemini TTS
            Log::info('Using Gemini 2.5 Pro TTS');
            $path = $geminiTts->generateAudio($text, $language, $voice, $styleInstruction);
            
            Log::info('Gemini TTS audio generated successfully', [
                'language' => $language,
                'voice' => $voice,
                'file_path' => $path
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Gemini TTS generation failed', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Gemini TTS generation failed: ' . $e->getMessage());
        }
    }

    private function updateUserUsage($user)
    {
        // Use free translations first
        if ($user->translations_used < $user->translations_limit) {
            $user->increment('translations_used');
        } else {
            // Then use credits
            $user->decrement('credits', 0.50); // €0.50 per translation
            $newBalance = $user->fresh()->credits;
            
            // Create credit transaction record for usage
            \App\Models\CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => null, // System transaction
                'amount' => -0.50, // Negative amount for usage
                'type' => 'usage', // Add required type field
                'description' => 'Credits used for text to audio conversion',
                'balance_after' => $newBalance,
            ]);
        }
    }
}

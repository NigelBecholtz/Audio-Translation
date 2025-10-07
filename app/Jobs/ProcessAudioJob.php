<?php

namespace App\Jobs;

use App\Models\AudioFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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
        Log::info('=== AUDIO PROCESSING JOB START ===');
        Log::info('Processing audio file ID: ' . $this->audioFileId);
        
        $audioFile = AudioFile::findOrFail($this->audioFileId);
        Log::info('Audio file found: ' . $audioFile->original_filename);
        
        try {
            // Step 1: Transcribe with Whisper
            Log::info('Step 1: Starting transcription...');
            $audioFile->update(['status' => 'transcribing']);
            $transcription = $this->transcribeWithWhisper($audioFile);
            $audioFile->update(['transcription' => $transcription]);
            Log::info('Step 1: Transcription completed, length: ' . strlen($transcription));

            // Step 2: Translate text
            Log::info('Step 2: Starting translation...');
            $audioFile->update(['status' => 'translating']);
            $translatedText = $this->translateText($transcription, $audioFile->source_language, $audioFile->target_language);
            $audioFile->update(['translated_text' => $translatedText]);
            Log::info('Step 2: Translation completed, length: ' . strlen($translatedText));

            // Step 3: Generate audio with TTS
            Log::info('Step 3: Starting TTS generation...');
            $audioFile->update(['status' => 'generating_audio']);
            $translatedAudioPath = $this->generateAudio($translatedText, $audioFile->target_language, $audioFile->voice, $audioFile->style_instruction);
            $audioFile->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed'
            ]);
            Log::info('Step 3: TTS generation completed, path: ' . $translatedAudioPath);
            
            // Update user usage
            $this->updateUserUsage($audioFile->user);
            
            Log::info('=== AUDIO PROCESSING JOB END - SUCCESS ===');

        } catch (\Exception $e) {
            Log::error('Audio processing job failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            $audioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            Log::info('=== AUDIO PROCESSING JOB END - FAILED ===');
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    private function transcribeWithWhisper(AudioFile $audioFile)
    {
        Log::info('=== WHISPER TRANSCRIPTION DEBUG ===');
        Log::info('Audio file ID: ' . $audioFile->id);
        Log::info('Source language: ' . $audioFile->source_language);
        
        try {
            $audioPath = \Illuminate\Support\Facades\Storage::disk('public')->path($audioFile->file_path);
            Log::info('Audio path: ' . $audioPath);
            Log::info('File exists: ' . (file_exists($audioPath) ? 'YES' : 'NO'));
            
            if (!file_exists($audioPath)) {
                throw new \Exception('Audio file not found: ' . $audioPath);
            }
            
            Log::info('Calling OpenAI Whisper API...');
            $response = OpenAI::audio()->transcribe([
                'file' => fopen($audioPath, 'r'),
                'model' => 'whisper-1',
                'language' => $audioFile->source_language,
                'response_format' => 'json'
            ]);

            Log::info('Whisper API response received');
            Log::info('Transcription length: ' . strlen($response->text));
            Log::info('Transcription preview: ' . substr($response->text, 0, 100) . '...');

            return $response->text;
            
        } catch (\Exception $e) {
            Log::error('Whisper transcription failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            throw new \Exception('Transcription failed: ' . $e->getMessage());
        }
    }

    private function translateText($text, $sourceLanguage, $targetLanguage)
    {
        try {
            // Skip translation if source and target are the same
            if ($sourceLanguage === $targetLanguage) {
                return $text;
            }
            
            $languageNames = [
                'en' => 'English',
                'es' => 'Spanish', 
                'fr' => 'French',
                'de' => 'German',
                'nl' => 'Dutch',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'zh' => 'Chinese',
                'ar' => 'Arabic',
                'hi' => 'Hindi',
                'sv' => 'Swedish',
                'sq' => 'Albanian',
                'bg' => 'Bulgarian',
                'sk' => 'Slovak',
                'lv' => 'Latvian',
                'fi' => 'Finnish',
                'el' => 'Greek',
                'ro' => 'Romanian',
                'ca' => 'Catalan'
            ];
            
            $sourceLangName = $languageNames[$sourceLanguage] ?? $sourceLanguage;
            $targetLangName = $languageNames[$targetLanguage] ?? $targetLanguage;
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a professional translator. Translate the following text from {$sourceLangName} to {$targetLangName}. Only return the translated text, nothing else."
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ]);
            
            $translatedText = trim($response->choices[0]->message->content);
            
            Log::info('Translation completed', [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'text_length' => strlen($text),
                'translated_length' => strlen($translatedText)
            ]);
            
            return $translatedText;
            
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Translation failed: ' . $e->getMessage());
        }
    }

    private function generateAudio($text, $language, $voice = null, $styleInstruction = null)
    {
        try {
            Log::info('=== GEMINI TTS GENERATION DEBUG ===');
            Log::info('Language: ' . $language);
            Log::info('Text length: ' . strlen($text));
            Log::info('Text preview: ' . substr($text, 0, 100) . '...');
            
            // Initialize Gemini TTS service
            $geminiTts = new \App\Services\GeminiTtsService();
            
            // Use Gemini TTS
            Log::info('Using Gemini 2.5 Pro TTS');
            $path = $geminiTts->generateAudio($text, $language, $voice, $styleInstruction ?? null);
            
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
                'description' => 'Credits used for audio translation',
                'balance_after' => $newBalance,
            ]);
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class SimpleTtsService
{
    /**
     * Generate audio using OpenAI TTS as fallback
     * 
     * @param string $text
     * @param string $language
     * @param string|null $voice
     * @param string|null $styleInstruction
     * @return string Path to generated audio file
     * @throws \Exception
     */
    public function generateAudio(string $text, string $language, ?string $voice = null, ?string $styleInstruction = null): string
    {
        try {
            Log::info('Using OpenAI TTS fallback', [
                'language' => $language,
                'text_length' => strlen($text),
                'voice' => $voice
            ]);
            
            // Map Gemini voices to OpenAI voices (best effort)
            $openAiVoice = $this->mapVoice($voice);
            
            // OpenAI TTS has a 4096 character limit
            $maxLength = 4096;
            if (strlen($text) > $maxLength) {
                Log::warning('Text exceeds OpenAI TTS limit, truncating', [
                    'original_length' => strlen($text),
                    'truncated_to' => $maxLength
                ]);
                $text = substr($text, 0, $maxLength);
            }
            
            // Generate audio with OpenAI TTS
            $response = OpenAI::audio()->speech([
                'model' => 'tts-1',
                'voice' => $openAiVoice,
                'input' => $text,
            ]);
            
            // Save audio file
            $filename = 'openai_tts_' . time() . '_' . rand(1000, 9999) . '.mp3';
            $path = 'audio/' . $filename;
            
            Storage::disk('public')->put($path, $response);
            
            Log::info('OpenAI TTS fallback successful', [
                'file_path' => $path,
                'voice' => $openAiVoice
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('OpenAI TTS fallback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('OpenAI TTS fallback failed: ' . $e->getMessage());
        }
    }

    /**
     * Map Gemini voice names to OpenAI voice names
     * 
     * @param string|null $geminiVoice
     * @return string
     */
    private function mapVoice(?string $geminiVoice): string
    {
        // OpenAI voices: alloy, echo, fable, onyx, nova, shimmer
        
        // Female Gemini voices -> Female OpenAI voices
        $femaleVoices = ['achernar', 'aoede', 'autonoe', 'callirrhoe', 'despina', 'erinome', 'gacrux', 'kore', 'laomedeia', 'leda', 'pulcherrima', 'sulafat', 'vindemiatrix', 'zephyr'];
        
        // Male Gemini voices -> Male OpenAI voices  
        $maleVoices = ['achird', 'algenib', 'algieba', 'alnilam', 'charon', 'enceladus', 'fenrir', 'lapetus', 'orus', 'puck', 'rasalgethi', 'sadachbia', 'sadaltager', 'schedar', 'umbriel', 'zubenelgenubi'];
        
        $voiceLower = strtolower($geminiVoice ?? '');
        
        if (in_array($voiceLower, $femaleVoices)) {
            // Rotate through female voices for variety
            $femaleOpenAiVoices = ['nova', 'shimmer', 'alloy'];
            return $femaleOpenAiVoices[array_rand($femaleOpenAiVoices)];
        }
        
        if (in_array($voiceLower, $maleVoices)) {
            // Rotate through male voices for variety
            $maleOpenAiVoices = ['onyx', 'echo', 'fable'];
            return $maleOpenAiVoices[array_rand($maleOpenAiVoices)];
        }
        
        // Default to alloy (neutral)
        return 'alloy';
    }

    /**
     * Check if OpenAI API is configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty(config('openai.api_key'));
    }
}

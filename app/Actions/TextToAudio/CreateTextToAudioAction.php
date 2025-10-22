<?php

namespace App\Actions\TextToAudio;

use App\Models\TextToAudio;
use App\Models\User;
use App\Services\SanitizationService;
use Illuminate\Support\Facades\Log;

class CreateTextToAudioAction
{
    private SanitizationService $sanitizer;
    
    public function __construct(SanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Create a new text-to-audio record
     *
     * @param User $user
     * @param array $data
     * @return TextToAudio
     */
    public function execute(User $user, array $data): TextToAudio
    {
        try {
            // Sanitize inputs
            $textContent = $this->sanitizer->sanitizeText($data['text_content']);
            $language = $this->sanitizer->sanitizeLanguageCode($data['language']);
            $voice = $this->sanitizer->sanitizeVoiceName($data['voice']);
            $styleInstruction = $this->sanitizer->sanitizeStyleInstruction($data['style_instruction'] ?? null);

            // Create database record
            $textToAudioRecord = TextToAudio::create([
                'user_id' => $user->id,
                'text_content' => $textContent,
                'language' => $language,
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'processing'
            ]);

            Log::info('Text-to-audio created', [
                'user_id' => $user->id,
                'text_to_audio_id' => $textToAudioRecord->id
            ]);

            return $textToAudioRecord;

        } catch (\Exception $e) {
            Log::error('Failed to create text-to-audio', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

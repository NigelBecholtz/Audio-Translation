<?php

namespace App\Actions\Audio;

use App\Models\AudioFile;
use App\Models\User;
use App\Services\SanitizationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CreateAudioTranslationAction
{
    private SanitizationService $sanitizer;
    
    public function __construct(SanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Create a new audio translation record
     *
     * @param User $user
     * @param UploadedFile $audioFile
     * @param array $data
     * @return AudioFile
     */
    public function execute(User $user, UploadedFile $audioFile, array $data): AudioFile
    {
        try {
            // Sanitize inputs
            $sourceLanguage = $this->sanitizer->sanitizeLanguageCode($data['source_language']);
            $targetLanguage = $this->sanitizer->sanitizeLanguageCode($data['target_language']);
            $voice = $this->sanitizer->sanitizeVoiceName($data['voice']);
            $styleInstruction = $this->sanitizer->sanitizeStyleInstruction($data['style_instruction'] ?? null);
            
            // Upload audio file
            $originalFilename = $this->sanitizer->sanitizeFilename($audioFile->getClientOriginalName());
            $filename = time() . '_' . $originalFilename;
            $path = $audioFile->storeAs('audio', $filename, 'public');

            // Create database record
            $audioRecord = AudioFile::create([
                'user_id' => $user->id,
                'original_filename' => $originalFilename,
                'file_path' => $path,
                'file_size' => $audioFile->getSize(),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'uploaded',
                'processing_stage' => 'uploaded',
                'processing_progress' => 5,
                'processing_message' => 'Upload complete! Starting processing...'
            ]);

            Log::info('Audio translation created', [
                'user_id' => $user->id,
                'audio_file_id' => $audioRecord->id,
                'filename' => $originalFilename
            ]);

            return $audioRecord;

        } catch (\Exception $e) {
            Log::error('Failed to create audio translation', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

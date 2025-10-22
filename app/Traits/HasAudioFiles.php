<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait HasAudioFiles
{
    /**
     * Delete audio file from storage
     *
     * @param string|null $filePath
     * @return bool
     */
    protected function deleteAudioFile(?string $filePath): bool
    {
        if (!$filePath) {
            return true;
        }

        try {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                Log::info('Audio file deleted', ['path' => $filePath]);
                return true;
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete audio file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user can access resource
     *
     * @param int $userId
     * @return bool
     */
    protected function canAccess(int $userId): bool
    {
        return auth()->id() === $userId || auth()->user()->isAdmin();
    }
}

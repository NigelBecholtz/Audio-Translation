<?php

namespace App\Policies;

use App\Models\AudioFile;
use App\Models\User;

class AudioFilePolicy
{
    /**
     * Determine if the user can view the audio file.
     */
    public function view(User $user, AudioFile $audioFile): bool
    {
        return $user->id === $audioFile->user_id;
    }

    /**
     * Determine if the user can update the audio file.
     */
    public function update(User $user, AudioFile $audioFile): bool
    {
        return $user->id === $audioFile->user_id;
    }

    /**
     * Determine if the user can delete the audio file.
     */
    public function delete(User $user, AudioFile $audioFile): bool
    {
        return $user->id === $audioFile->user_id;
    }

    /**
     * Determine if the user can download the audio file.
     */
    public function download(User $user, AudioFile $audioFile): bool
    {
        return $user->id === $audioFile->user_id && $audioFile->status === 'completed';
    }
}

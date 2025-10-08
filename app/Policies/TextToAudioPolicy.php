<?php

namespace App\Policies;

use App\Models\TextToAudio;
use App\Models\User;

class TextToAudioPolicy
{
    /**
     * Determine if the user can view the text-to-audio file.
     */
    public function view(User $user, TextToAudio $textToAudio): bool
    {
        return $user->id === $textToAudio->user_id;
    }

    /**
     * Determine if the user can update the text-to-audio file.
     */
    public function update(User $user, TextToAudio $textToAudio): bool
    {
        return $user->id === $textToAudio->user_id;
    }

    /**
     * Determine if the user can delete the text-to-audio file.
     */
    public function delete(User $user, TextToAudio $textToAudio): bool
    {
        return $user->id === $textToAudio->user_id;
    }

    /**
     * Determine if the user can download the text-to-audio file.
     */
    public function download(User $user, TextToAudio $textToAudio): bool
    {
        return $user->id === $textToAudio->user_id && $textToAudio->status === 'completed';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioTranslation extends Model
{
    protected $fillable = [
        'audio_file_id',
        'target_language',
        'translated_text',
        'translated_audio_path',
        'voice',
        'status',
        'error_message',
        'cost'
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function audioFile(): BelongsTo
    {
        return $this->belongsTo(AudioFile::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['translating', 'generating_audio']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'file_size',
        'duration',
        'source_language',
        'target_language',
        'voice',
        'style_instruction',
        'transcription',
        'translated_text',
        'translated_audio_path',
        'status',
        'processing_stage',
        'processing_progress',
        'processing_message',
        'error_message'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
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
        return in_array($this->status, ['uploaded', 'transcribing', 'translating', 'generating_audio']);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isPendingTTSApproval(): bool
    {
        return $this->status === 'pending_tts_approval';
    }
}

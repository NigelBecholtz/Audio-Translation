<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvTranslationJob extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'output_path',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'target_languages',
        'error_message',
        'use_smart_fallback',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'target_languages' => 'array',
        'use_smart_fallback' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return round(($this->processed_items / $this->total_items) * 100, 2);
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
        return in_array($this->status, ['pending', 'processing']);
    }
}

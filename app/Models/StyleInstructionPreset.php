<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StyleInstructionPreset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'instruction',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only user's presets (including default ones)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_default', true);
        });
    }

    /**
     * Scope to get only default presets
     */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }
}

<?php

namespace App\Jobs\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Marks the job's record as failed once Laravel gives up on the job (after its last attempt).
 */
trait HandlesProcessingFailure
{
    /**
     * The record whose status this job drives, or null when it no longer exists.
     */
    abstract protected function processingRecord(): ?Model;

    public function failed(Throwable $exception): void
    {
        $record = $this->processingRecord();

        Log::error(class_basename(static::class).' failed', [
            'record' => $record ? $record::class.'#'.$record->getKey() : null,
            'error' => $exception->getMessage(),
        ]);

        if (! $record) {
            return;
        }

        $attributes = [
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ];

        if ($record->isFillable('processing_stage')) {
            $attributes += [
                'processing_stage' => 'failed',
                'processing_progress' => 0,
                'processing_message' => 'Processing failed',
            ];
        }

        $record->update($attributes);
    }
}

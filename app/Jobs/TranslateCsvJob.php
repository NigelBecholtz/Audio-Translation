<?php

namespace App\Jobs;

use App\Services\CsvTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranslateCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 1800; // 30 minutes for large CSV files

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $inputPath,
        public string $outputPath,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CsvTranslationService $translationService): void
    {
        try {
            Log::info('Starting CSV translation job', [
                'user_id' => $this->userId,
                'input_path' => $this->inputPath
            ]);

            // Parse CSV
            $fullInputPath = Storage::disk('public')->path($this->inputPath);
            $csvData = $translationService->parseCsv($fullInputPath);

            // Translate
            $translatedData = $translationService->translateCsv($csvData);

            // Export
            $fullOutputPath = Storage::disk('public')->path($this->outputPath);
            $translationService->exportToCsv($translatedData, $fullOutputPath);

            // Create notification file to indicate completion
            $statusPath = str_replace('.csv', '_status.json', $this->outputPath);
            Storage::disk('public')->put($statusPath, json_encode([
                'status' => 'completed',
                'completed_at' => now()->toIso8601String(),
                'output_file' => $this->outputPath
            ]));

            Log::info('CSV translation completed', [
                'user_id' => $this->userId,
                'output_path' => $this->outputPath
            ]);

        } catch (\Exception $e) {
            Log::error('CSV translation failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Create error status file
            $statusPath = str_replace('.csv', '_status.json', $this->outputPath);
            Storage::disk('public')->put($statusPath, json_encode([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String()
            ]));

            throw $e;
        }
    }
}


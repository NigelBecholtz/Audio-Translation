<?php

namespace App\Jobs;

use App\Models\CsvTranslationJob;
use App\Services\GoogleTranslationService;
use App\Services\CsvParserService;
use App\Services\LanguageDetectionService;
use App\Services\MultiSheetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvTranslationJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry failed jobs automatically

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CsvTranslationJob $translationJob
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        GoogleTranslationService $translationService,
        CsvParserService $csvParser,
        LanguageDetectionService $languageDetection,
        MultiSheetService $multiSheetService
    ): void {
        Log::info('Starting CSV translation job', [
            'job_id' => $this->translationJob->id,
            'file' => $this->translationJob->original_filename
        ]);

        try {
            // Update status to processing
            $this->translationJob->update([
                'status' => 'processing',
                'started_at' => now()
            ]);

            $fullPath = storage_path('app/public/' . $this->translationJob->file_path);

            // Check if we should use smart fallback
            if ($this->translationJob->use_smart_fallback) {
                $this->processSmartFallback(
                    $fullPath,
                    $csvParser,
                    $translationService,
                    $languageDetection,
                    $multiSheetService
                );
            } else {
                $this->processStandardTranslation($fullPath, $csvParser, $translationService);
            }

            // Mark as completed
            $this->translationJob->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            Log::info('CSV translation job completed', [
                'job_id' => $this->translationJob->id,
                'processed_items' => $this->translationJob->processed_items
            ]);

        } catch (\Exception $e) {
            Log::error('CSV translation job failed', [
                'job_id' => $this->translationJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->translationJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);

            throw $e;
        }
    }

    /**
     * Process standard CSV translation
     */
    private function processStandardTranslation(
        string $fullPath,
        CsvParserService $csvParser,
        GoogleTranslationService $translationService
    ): void {
        // Parse file
        $parsed = $csvParser->parse($fullPath);
        $headers = $parsed['headers'];
        $data = $parsed['data'];

        // Handle en_US columns - copy from en column
        $this->handleEnUsColumns($data, $headers);

        // Get target languages
        $targetLanguages = $this->translationJob->target_languages ?? array_diff($headers, ['en', 'en_US']);
        
        Log::info('Standard translation target languages', [
            'job_id' => $this->translationJob->id,
            'selected_languages' => $this->translationJob->target_languages,
            'headers' => $headers,
            'final_target_languages' => $targetLanguages
        ]);

        // Count total items to translate
        $totalItems = 0;
        foreach ($targetLanguages as $targetLang) {
            foreach ($data as $row) {
                if (empty($row[$targetLang]) && !empty($row['en'])) {
                    $totalItems++;
                }
            }
        }

        $this->translationJob->update(['total_items' => $totalItems]);

        $processedItems = 0;

        // Process each target language
        foreach ($targetLanguages as $targetLang) {
            // Collect texts that need translation for this language
            $textsToTranslate = [];
            $rowIndices = [];

            foreach ($data as $index => $row) {
                // Only translate if cell is empty and source text exists
                if (empty($row[$targetLang]) && !empty($row['en'])) {
                    $textsToTranslate[] = $row['en'];
                    $rowIndices[] = $index;
                }
            }

            // Batch translate all texts for this language
            if (!empty($textsToTranslate)) {
                try {
                    $translations = $translationService->translateBatch($textsToTranslate, $targetLang);
                    
                    // Update data with translations
                    foreach ($rowIndices as $idx => $rowIndex) {
                        if (isset($translations[$idx])) {
                            $data[$rowIndex][$targetLang] = $translations[$idx];
                            $processedItems++;

                            // Update progress every 100 items
                            if ($processedItems % 100 === 0) {
                                $this->translationJob->update([
                                    'processed_items' => $processedItems
                                ]);
                            }
                        }
                    }

                    Log::info("Translated {$targetLang}", [
                        'job_id' => $this->translationJob->id,
                        'count' => count($textsToTranslate),
                        'language' => $targetLang
                    ]);

                } catch (\Exception $e) {
                    Log::warning("Failed to translate {$targetLang}: " . $e->getMessage());
                    // Continue with other languages even if one fails
                }
            }
        }

        // Final progress update
        $this->translationJob->update(['processed_items' => $processedItems]);

        // Export to new file - always use CSV for better compatibility
        $originalName = pathinfo($this->translationJob->original_filename, PATHINFO_FILENAME);
        $outputFilename = 'translated_' . $originalName . '_' . date('Y-m-d_H-i-s') . '.csv';
        $outputPath = 'temp/' . $outputFilename;
        $fullOutputPath = storage_path('app/public/' . $outputPath);
        
        // Ensure temp directory exists
        $tempDir = storage_path('app/public/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $csvParser->exportToCsv($headers, $data, $fullOutputPath);

        $this->translationJob->update(['output_path' => $outputPath]);

        // Cleanup original upload
        Storage::disk('public')->delete($this->translationJob->file_path);
    }

    /**
     * Process file with smart fallback
     */
    private function processSmartFallback(
        string $fullPath,
        CsvParserService $csvParser,
        GoogleTranslationService $translationService,
        LanguageDetectionService $languageDetection,
        MultiSheetService $multiSheetService
    ): void {
        // Parse file to get raw data
        $parsed = $csvParser->parse($fullPath);
        $data = $parsed['data'];
        $headers = $parsed['headers'] ?? [];
        
        // Handle en_US columns - copy from en column
        $this->handleEnUsColumns($data, $headers);
        
        // Extract all text content from the file
        $sourceTexts = [];
        foreach ($data as $row) {
            foreach ($row as $value) {
                $text = trim($value);
                if (!empty($text)) {
                    $sourceTexts[] = $text;
                }
            }
        }
        
        if (empty($sourceTexts)) {
            throw new \Exception('No text content found in file');
        }
        
        // Detect source language from first few texts
        $sampleText = implode(' ', array_slice($sourceTexts, 0, 3));
        
        try {
            $detectedLanguage = $languageDetection->detectLanguage($sampleText);
        } catch (\Exception $e) {
            Log::error('Language detection failed', ['error' => $e->getMessage()]);
            $detectedLanguage = 'en'; // Fallback to English
        }
        
        // Get preset languages for translation
        $presetLanguages = $languageDetection->getPresetLanguages();
        
        Log::info('Preset languages retrieved', [
            'job_id' => $this->translationJob->id,
            'preset_languages' => $presetLanguages,
            'detected_language' => $detectedLanguage
        ]);
        
        // Remove detected language from target languages
        $targetLanguages = array_filter($presetLanguages, function($lang) use ($detectedLanguage) {
            return $lang !== $detectedLanguage;
        });
        
        Log::info('Target languages after filtering', [
            'job_id' => $this->translationJob->id,
            'target_languages' => array_values($targetLanguages)
        ]);
        
        $totalItems = count($sourceTexts) * count($targetLanguages);
        $this->translationJob->update(['total_items' => $totalItems]);
        
        $translations = [];
        $processedItems = 0;
        
        // Translate to each target language
        foreach ($targetLanguages as $targetLang) {
            try {
                $translatedTexts = $translationService->translateBatch($sourceTexts, $targetLang);
                $translations[$targetLang] = $translatedTexts;
                $processedItems += count($translatedTexts);
                
                $this->translationJob->update(['processed_items' => $processedItems]);
                
                Log::info("Smart fallback translated {$targetLang}", [
                    'job_id' => $this->translationJob->id,
                    'count' => count($translatedTexts),
                    'language' => $targetLang
                ]);
                
            } catch (\Exception $e) {
                Log::error("Smart fallback failed for {$targetLang}", [
                    'job_id' => $this->translationJob->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with other languages
            }
        }
        
        if (empty($translations)) {
            throw new \Exception('Failed to translate to any target languages');
        }
        
        // Create CSV output for better compatibility
        $originalName = pathinfo($this->translationJob->original_filename, PATHINFO_FILENAME);
        $outputFilename = 'smart_translations_' . $originalName . '_' . date('Y-m-d_H-i-s') . '.csv';
        $outputPath = 'temp/' . $outputFilename;
        $fullOutputPath = storage_path('app/public/' . $outputPath);
        
        // Ensure temp directory exists
        $tempDir = storage_path('app/public/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Create CSV with all translations in one sheet
        $csvParser = new CsvParserService();
        $csvParser->exportToCsv($headers, $data, $fullOutputPath);
        
        $this->translationJob->update(['output_path' => $outputPath]);
        
        // Cleanup original upload
        Storage::disk('public')->delete($this->translationJob->file_path);
    }

    /**
     * Handle en_US columns by copying values from en column
     */
    private function handleEnUsColumns(array &$data, array $headers): void
    {
        foreach ($headers as $header) {
            if ($header === 'en_US' || strtolower($header) === 'en_us') {
                Log::info('Found en_US column, copying values from en column');
                
                foreach ($data as $index => $row) {
                    if (!empty($row['en'])) {
                        $data[$index][$header] = $row['en'];
                    }
                }
                
                Log::info('Copied values from en to en_US column', [
                    'rows_processed' => count($data)
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CSV translation job failed completely', [
            'job_id' => $this->translationJob->id,
            'error' => $exception->getMessage()
        ]);

        $this->translationJob->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now()
        ]);
    }
}

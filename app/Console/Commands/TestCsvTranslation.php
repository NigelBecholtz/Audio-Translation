<?php

namespace App\Console\Commands;

use App\Services\CsvTranslationService;
use Illuminate\Console\Command;

class TestCsvTranslation extends Command
{
    protected $signature = 'csv:test-translation {input} {output}';
    protected $description = 'Test CSV translation with a sample file';

    public function handle(CsvTranslationService $translationService)
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');

        if (!file_exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return 1;
        }

        $this->info("Starting CSV translation test...");
        $this->info("Input: {$inputPath}");
        $this->info("Output: {$outputPath}");
        $this->newLine();

        try {
            // Parse CSV
            $this->info("📖 Parsing CSV...");
            $csvData = $translationService->parseCsv($inputPath);
            $this->info("✓ Found " . (count($csvData) - 1) . " rows to translate");
            $this->newLine();

            // Show header
            $this->info("Header columns: " . implode(', ', $csvData[0]));
            $this->newLine();

            // Translate (only first 3 rows for testing to save API costs)
            $this->info("🌍 Translating (processing first 3 rows only for test)...");
            
            $testData = array_slice($csvData, 0, 4); // Header + 3 rows
            
            $this->newLine();
            $translatedData = $translationService->translateCsv($testData);
            $this->newLine();

            // Export in 3 formats
            $this->info("💾 Exporting files...");
            
            // 1. Standard CSV (for Excel/Sheets)
            $translationService->exportToCsv($translatedData, $outputPath);
            $this->info("✓ Standard CSV: {$outputPath}");
            
            // 2. Pretty CSV (readable in text editor)
            $prettyPath = str_replace('.csv', '_pretty.csv', $outputPath);
            $translationService->exportToPrettyCsv($translatedData, $prettyPath);
            $this->info("✓ Pretty CSV: {$prettyPath}");
            
            // 3. Markdown table (for documentation)
            $markdownPath = str_replace('.csv', '.md', $outputPath);
            $translationService->exportToMarkdown($translatedData, $markdownPath);
            $this->info("✓ Markdown: {$markdownPath}");
            
            $this->newLine();

            // Show sample results
            $this->info("📊 Sample translations:");
            $header = $translatedData[0];
            $this->table($header, array_slice($translatedData, 1, 3));

            $this->newLine();
            $this->info("✅ Test completed successfully!");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Translation failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}


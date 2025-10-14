<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MultiSheetService
{
    /**
     * Create a multi-sheet XLSX file with separate sheets for each language
     *
     * @param array $sourceData Array of source text data
     * @param array $translations Array of translations [language => [texts]]
     * @param string $sourceLanguage Source language code
     * @param string $outputPath Output file path
     * @return void
     * @throws \Exception
     */
    public function createMultiSheetXlsx(array $sourceData, array $translations, string $sourceLanguage, string $outputPath): void
    {
        // For now, create a CSV with all languages in separate columns
        // This is a simplified approach - in production you might want to use PhpSpreadsheet
        $this->createMultiLanguageCsv($sourceData, $translations, $sourceLanguage, $outputPath);
    }

    /**
     * Create a CSV with all languages in separate columns
     */
    private function createMultiLanguageCsv(array $sourceData, array $translations, string $sourceLanguage, string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            throw new \Exception('Could not create output file');
        }

        try {
            // Write UTF-8 BOM for proper encoding
            fwrite($handle, "\xEF\xBB\xBF");

            // Create headers: source language + all target languages
            $languages = array_keys($translations);
            $headers = array_merge([$sourceLanguage], $languages);
            fputcsv($handle, $headers, ';');

            // Write data rows
            foreach ($sourceData as $index => $sourceText) {
                $row = [$sourceText];
                
                foreach ($languages as $language) {
                    $translatedText = $translations[$language][$index] ?? '';
                    
                    // Decode HTML entities and ensure proper UTF-8 encoding
                    $translatedText = html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    // Ensure the string is properly UTF-8 encoded
                    if (!mb_check_encoding($translatedText, 'UTF-8')) {
                        $translatedText = mb_convert_encoding($translatedText, 'UTF-8', 'auto');
                    }
                    
                    $row[] = $translatedText;
                }
                
                fputcsv($handle, $row, ';');
            }

            fclose($handle);

            Log::info('Multi-language CSV created', [
                'file' => basename($outputPath),
                'source_language' => $sourceLanguage,
                'target_languages' => $languages,
                'rows' => count($sourceData)
            ]);

        } catch (\Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Create separate CSV files for each language
     *
     * @param array $sourceData Array of source text data
     * @param array $translations Array of translations [language => [texts]]
     * @param string $sourceLanguage Source language code
     * @param string $basePath Base path for output files
     * @return array Array of created file paths
     * @throws \Exception
     */
    public function createSeparateLanguageFiles(array $sourceData, array $translations, string $sourceLanguage, string $basePath): array
    {
        $createdFiles = [];
        $baseName = pathinfo($basePath, PATHINFO_FILENAME);
        $directory = dirname($basePath);

        foreach ($translations as $language => $languageTranslations) {
            $fileName = $baseName . '_' . $language . '.csv';
            $filePath = $directory . '/' . $fileName;
            
            $handle = fopen($filePath, 'w');
            if ($handle === false) {
                throw new \Exception("Could not create file for language: {$language}");
            }

            try {
                // Write UTF-8 BOM for proper encoding
                fwrite($handle, "\xEF\xBB\xBF");

                // Write headers
                fputcsv($handle, [$sourceLanguage, $language], ';');

                // Write data rows
                foreach ($sourceData as $index => $sourceText) {
                    $translatedText = $languageTranslations[$index] ?? '';
                    
                    // Decode HTML entities and ensure proper UTF-8 encoding
                    $translatedText = html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    // Ensure the string is properly UTF-8 encoded
                    if (!mb_check_encoding($translatedText, 'UTF-8')) {
                        $translatedText = mb_convert_encoding($translatedText, 'UTF-8', 'auto');
                    }
                    
                    fputcsv($handle, [$sourceText, $translatedText], ';');
                }

                fclose($handle);
                $createdFiles[] = $filePath;

                Log::info('Language file created', [
                    'language' => $language,
                    'file' => $fileName,
                    'rows' => count($sourceData)
                ]);

            } catch (\Exception $e) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
                throw $e;
            }
        }

        return $createdFiles;
    }

    /**
     * Create a ZIP file containing separate CSV files for each language
     *
     * @param array $sourceData Array of source text data
     * @param array $translations Array of translations [language => [texts]]
     * @param string $sourceLanguage Source language code
     * @param string $outputPath Output ZIP file path
     * @return void
     * @throws \Exception
     */
    public function createLanguageZip(array $sourceData, array $translations, string $sourceLanguage, string $outputPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Could not create ZIP file');
        }

        try {
            foreach ($translations as $language => $languageTranslations) {
                $csvContent = $this->generateCsvContent($sourceData, $languageTranslations, $sourceLanguage, $language);
                $zip->addFromString("translations_{$language}.csv", $csvContent);
            }

            $zip->close();

            Log::info('Language ZIP created', [
                'file' => basename($outputPath),
                'languages' => array_keys($translations),
                'rows' => count($sourceData)
            ]);

        } catch (\Exception $e) {
            $zip->close();
            throw $e;
        }
    }

    /**
     * Generate CSV content for a specific language
     */
    private function generateCsvContent(array $sourceData, array $translations, string $sourceLanguage, string $targetLanguage): string
    {
        $content = "\xEF\xBB\xBF"; // UTF-8 BOM
        
        // Headers
        $content .= $sourceLanguage . ';' . $targetLanguage . "\n";
        
        // Data rows
        foreach ($sourceData as $index => $sourceText) {
            $translatedText = $translations[$index] ?? '';
            
            // Decode HTML entities and ensure proper UTF-8 encoding
            $translatedText = html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Ensure the string is properly UTF-8 encoded
            if (!mb_check_encoding($translatedText, 'UTF-8')) {
                $translatedText = mb_convert_encoding($translatedText, 'UTF-8', 'auto');
            }
            
            $content .= $sourceText . ';' . $translatedText . "\n";
        }
        
        return $content;
    }
}

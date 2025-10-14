<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        try {
            Log::info('Creating multi-sheet XLSX', [
                'source_language' => $sourceLanguage,
                'target_languages' => array_keys($translations),
                'source_data_count' => count($sourceData),
                'output_path' => $outputPath
            ]);
            
            $spreadsheet = new Spreadsheet();
            
            // Remove default sheet
            $spreadsheet->removeSheetByIndex(0);
            
            // Create overview sheet with all languages
            $this->createOverviewSheet($spreadsheet, $sourceData, $translations, $sourceLanguage);
            
            // Create individual sheets for each language
            foreach ($translations as $language => $languageTranslations) {
                $this->createLanguageSheet($spreadsheet, $sourceData, $languageTranslations, $sourceLanguage, $language);
            }
            
            // Set first sheet as active
            $spreadsheet->setActiveSheetIndex(0);
            
            // Save the file
            $writer = new Xlsx($spreadsheet);
            $writer->save($outputPath);
            
            Log::info('Multi-sheet XLSX created', [
                'file' => basename($outputPath),
                'source_language' => $sourceLanguage,
                'sheets' => count($translations) + 1, // +1 for overview sheet
                'rows' => count($sourceData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create multi-sheet XLSX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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

    /**
     * Create overview sheet with all languages
     */
    private function createOverviewSheet(Spreadsheet $spreadsheet, array $sourceData, array $translations, string $sourceLanguage): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Overview');
        
        // Create headers
        $headers = [$sourceLanguage];
        foreach (array_keys($translations) as $language) {
            $headers[] = $language;
        }
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Style headers
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        
        // Add data
        $row = 2;
        foreach ($sourceData as $index => $sourceText) {
            $colIndex = 0;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row, $sourceText);
            $colIndex++;
            
            foreach ($translations as $language => $languageTranslations) {
                $translatedText = $languageTranslations[$index] ?? '';
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row, $translatedText);
                $colIndex++;
            }
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Create individual sheet for a specific language
     */
    private function createLanguageSheet(Spreadsheet $spreadsheet, array $sourceData, array $languageTranslations, string $sourceLanguage, string $targetLanguage): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($targetLanguage);
        
        // Create headers
        $sheet->setCellValue('A1', $sourceLanguage);
        $sheet->setCellValue('B1', $targetLanguage);
        
        // Style headers
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E8']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
        
        // Add data
        $row = 2;
        foreach ($sourceData as $index => $sourceText) {
            $translatedText = $languageTranslations[$index] ?? '';
            
            $sheet->setCellValue('A' . $row, $sourceText);
            $sheet->setCellValue('B' . $row, $translatedText);
            $row++;
        }
        
        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        
        // Add borders to data
        $dataRange = 'A1:B' . (count($sourceData) + 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);
    }
}

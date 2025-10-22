<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\ExcelParserService;

class CsvParserService
{
    private ExcelParserService $excelParser;

    public function __construct(ExcelParserService $excelParser)
    {
        $this->excelParser = $excelParser;
    }

    /**
     * Parse CSV or XLSX file to associative array
     *
     * @param string $filePath Path to CSV or XLSX file
     * @return array Array of rows with column headers as keys
     * @throws \Exception
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found: ' . $filePath);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            return $this->excelParser->parse($filePath);
        }
        
        // Default to CSV parsing
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception('Could not open CSV file');
        }

        $data = [];
        $headers = null;
        $rowNumber = 0;

        try {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                
                // First row contains headers
                if ($headers === null) {
                    $headers = array_map('trim', $row);
                    
                    // Validate required columns (case-insensitive)
                    $lowerHeaders = array_map('strtolower', $headers);
                    if (!in_array('en', $lowerHeaders)) {
                        throw new \Exception('File must contain "en" column');
                    }
                    
                    continue;
                }

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Combine headers with row data
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = $row[$index] ?? '';
                }

                $data[] = $rowData;
            }

            fclose($handle);

            Log::info('CSV parsed successfully', [
                'file' => basename($filePath),
                'rows' => count($data),
                'columns' => count($headers)
            ]);

            return [
                'headers' => $headers,
                'data' => $data
            ];

        } catch (\Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Export array data to CSV or XLSX file
     *
     * @param array $headers Column headers
     * @param array $data Array of row data
     * @param string $filePath Output file path
     * @return void
     * @throws \Exception
     */
    public function exportToCsv(array $headers, array $data, string $filePath): void
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            $this->excelParser->exportToXlsx($headers, $data, $filePath);
            return;
        }
        
        // Default to CSV export
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \Exception('Could not create CSV file');
        }

        try {
            // Write UTF-8 BOM for proper encoding
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, $headers, ';');

            // Write data rows
            foreach ($data as $row) {
                $rowData = [];
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    
                    // Decode HTML entities and ensure proper UTF-8 encoding
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    // Ensure the string is properly UTF-8 encoded
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                    
                    $rowData[] = $value;
                }
                fputcsv($handle, $rowData, ';');
            }

            fclose($handle);

            Log::info('CSV exported successfully', [
                'file' => basename($filePath),
                'rows' => count($data)
            ]);

        } catch (\Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Validate CSV or XLSX structure
     *
     * @param string $filePath
     * @return array Validation result with 'valid' boolean and 'message' string
     */
    public function validate(string $filePath): array
    {
        try {
            $parsed = $this->parse($filePath);
            
            $headers = $parsed['headers'];
            $data = $parsed['data'];

            // Check required columns (case-insensitive)
            $lowerHeaders = array_map('strtolower', $headers);
            
            if (!in_array('en', $lowerHeaders)) {
                return ['valid' => false, 'message' => 'Missing required column: en (English source)'];
            }

            // Check for at least one target language column
            $targetLanguages = array_diff($headers, ['key', 'en']);
            if (empty($targetLanguages)) {
                return ['valid' => false, 'message' => 'No target language columns found'];
            }

            // Check if we have data
            if (empty($data)) {
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $fileType = $extension === 'xlsx' ? 'XLSX' : 'CSV';
                return ['valid' => false, 'message' => $fileType . ' file is empty (no data rows)'];
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileType = $extension === 'xlsx' ? 'XLSX' : 'CSV';
            
            return [
                'valid' => true,
                'message' => 'Valid ' . $fileType . ' file',
                'rows' => count($data),
                'languages' => array_values($targetLanguages)
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }
}


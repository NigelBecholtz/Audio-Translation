<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CsvParserService
{
    /**
     * Parse CSV file to associative array
     *
     * @param string $filePath Path to CSV file
     * @return array Array of rows with column headers as keys
     * @throws \Exception
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('CSV file not found: ' . $filePath);
        }

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
                    
                    // Validate required columns
                    if (!in_array('key', $headers) || !in_array('en', $headers)) {
                        throw new \Exception('CSV must contain "key" and "en" columns');
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
     * Export array data to CSV file
     *
     * @param array $headers Column headers
     * @param array $data Array of row data
     * @param string $filePath Output file path
     * @return void
     * @throws \Exception
     */
    public function exportToCsv(array $headers, array $data, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \Exception('Could not create CSV file');
        }

        try {
            // Write headers
            fputcsv($handle, $headers, ';');

            // Write data rows
            foreach ($data as $row) {
                $rowData = [];
                foreach ($headers as $header) {
                    $rowData[] = $row[$header] ?? '';
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
     * Validate CSV structure
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

            // Check required columns
            if (!in_array('key', $headers)) {
                return ['valid' => false, 'message' => 'Missing required column: key'];
            }

            if (!in_array('en', $headers)) {
                return ['valid' => false, 'message' => 'Missing required column: en (English source)'];
            }

            // Check for at least one target language column
            $targetLanguages = array_diff($headers, ['key', 'en']);
            if (empty($targetLanguages)) {
                return ['valid' => false, 'message' => 'No target language columns found'];
            }

            // Check if we have data
            if (empty($data)) {
                return ['valid' => false, 'message' => 'CSV file is empty (no data rows)'];
            }

            return [
                'valid' => true,
                'message' => 'Valid CSV file',
                'rows' => count($data),
                'languages' => array_values($targetLanguages)
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }
}


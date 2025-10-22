<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ExcelParserService
{
    /**
     * Parse XLSX file to associative array
     *
     * @param string $filePath Path to XLSX file
     * @return array Array of rows with column headers as keys
     * @throws \Exception
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('XLSX file not found: ' . $filePath);
        }

        // XLSX is a ZIP file containing XML
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            throw new \Exception('Could not open XLSX file');
        }

        try {
            // Read the shared strings (for text values)
            $sharedStrings = $this->parseSharedStrings($zip);
            
            // Read the worksheet data
            $worksheetData = $this->parseWorksheet($zip, $sharedStrings);
            
            $zip->close();

            Log::info('XLSX parsed successfully', [
                'file' => basename($filePath),
                'rows' => count($worksheetData['data']),
                'columns' => count($worksheetData['headers'])
            ]);

            return $worksheetData;

        } catch (\Exception $e) {
            $zip->close();
            throw $e;
        }
    }

    /**
     * Parse shared strings from XLSX
     */
    private function parseSharedStrings(ZipArchive $zip): array
    {
        $sharedStrings = [];
        
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml !== false) {
                $xml = simplexml_load_string($sharedStringsXml);
                if ($xml !== false) {
                    foreach ($xml->si as $si) {
                        $text = '';
                        if (isset($si->t)) {
                            $text = (string)$si->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }
        
        return $sharedStrings;
    }

    /**
     * Parse worksheet data from XLSX
     */
    private function parseWorksheet(ZipArchive $zip, array $sharedStrings): array
    {
        $data = [];
        $headers = null;
        
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($worksheetXml !== false) {
                $xml = simplexml_load_string($worksheetXml);
                if ($xml !== false) {
                    $rowData = [];
                    $currentRow = 0;
                    
                    foreach ($xml->sheetData->row as $row) {
                        $rowNumber = (int)$row['r'];
                        
                        // Initialize row data
                        if (!isset($rowData[$rowNumber])) {
                            $rowData[$rowNumber] = [];
                        }
                        
                        foreach ($row->c as $cell) {
                            $cellRef = (string)$cell['r'];
                            $column = $this->getColumnFromRef($cellRef);
                            $value = $this->getCellValue($cell, $sharedStrings);
                            
                            $rowData[$rowNumber][$column] = $value;
                        }
                    }
                    
                    // Convert to array format
                    $maxRow = max(array_keys($rowData));
                    $maxCol = 0;
                    
                    for ($row = 1; $row <= $maxRow; $row++) {
                        if (isset($rowData[$row])) {
                            $maxCol = max($maxCol, max(array_keys($rowData[$row])));
                        }
                    }
                    
                    // Extract headers and data
                    for ($row = 1; $row <= $maxRow; $row++) {
                        $rowValues = [];
                        for ($col = 0; $col <= $maxCol; $col++) {
                            $rowValues[] = $rowData[$row][$col] ?? '';
                        }
                        
                        if ($headers === null) {
                            $headers = array_map('trim', $rowValues);
                            continue;
                        }
                        
                        // Skip empty rows
                        if (empty(array_filter($rowValues))) {
                            continue;
                        }
                        
                        // Combine headers with row data
                        $rowDataAssoc = [];
                        foreach ($headers as $index => $header) {
                            $rowDataAssoc[$header] = $rowValues[$index] ?? '';
                        }
                        
                        $data[] = $rowDataAssoc;
                    }
                }
            }
        }
        
        return [
            'headers' => $headers ?? [],
            'data' => $data
        ];
    }

    /**
     * Get column index from cell reference (A=0, B=1, etc.)
     */
    private function getColumnFromRef(string $cellRef): int
    {
        preg_match('/([A-Z]+)/', $cellRef, $matches);
        $column = $matches[1] ?? '';
        
        $result = 0;
        for ($i = 0; $i < strlen($column); $i++) {
            $result = $result * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $result - 1;
    }

    /**
     * Get cell value from XML cell element
     */
    private function getCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cellType = (string)$cell['t'];
        $value = '';
        
        if (isset($cell->v)) {
            $cellValue = (string)$cell->v;
            
            if ($cellType === 's') {
                // Shared string
                $index = (int)$cellValue;
                $value = $sharedStrings[$index] ?? '';
            } else {
                // Direct value
                $value = $cellValue;
            }
        }
        
        return $value;
    }

    /**
     * Export array data to XLSX file
     *
     * @param array $headers Column headers
     * @param array $data Array of row data
     * @param string $filePath Output file path
     * @return void
     * @throws \Exception
     */
    public function exportToXlsx(array $headers, array $data, string $filePath): void
    {
        // Create a proper XLSX file using PhpSpreadsheet
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $col = 1;
            foreach ($headers as $header) {
                $worksheet->setCellValueByColumnAndRow($col, 1, $header);
                $col++;
            }
            
            // Set data
            $row = 2;
            foreach ($data as $rowData) {
                $col = 1;
                foreach ($headers as $header) {
                    $value = $rowData[$header] ?? '';
                    
                    // Decode HTML entities and ensure proper UTF-8 encoding
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    
                    // Ensure the string is properly UTF-8 encoded
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                    
                    $worksheet->setCellValueByColumnAndRow($col, $row, $value);
                    $col++;
                }
                $row++;
            }
            
            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            // Save as XLSX
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
            
            Log::info('XLSX exported successfully', [
                'file' => basename($filePath),
                'rows' => count($data)
            ]);
            
        } catch (\Exception $e) {
            Log::error('XLSX export failed', [
                'error' => $e->getMessage(),
                'file' => basename($filePath)
            ]);
            
            // Fallback to CSV if XLSX export fails
            $this->exportToCsv($headers, $data, $filePath);
        }
    }

    /**
     * Export as CSV (fallback for XLSX export)
     */
    private function exportToCsv(array $headers, array $data, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \Exception('Could not create file');
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

            Log::info('File exported successfully', [
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
     * Validate XLSX structure
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
                return ['valid' => false, 'message' => 'XLSX file is empty (no data rows)'];
            }

            return [
                'valid' => true,
                'message' => 'Valid XLSX file',
                'rows' => count($data),
                'languages' => array_values($targetLanguages)
            ];

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }
}

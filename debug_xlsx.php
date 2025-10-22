<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "=== Testing XLSX Creation ===\n";

try {
    $spreadsheet = new Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();
    
    echo "Spreadsheet created successfully\n";
    
    // Test data
    $headers = ['key', 'en', 'es_AR', 'fr'];
    $data = [
        ['key' => 'test1', 'en' => 'Hello', 'es_AR' => 'Hola', 'fr' => 'Bonjour'],
        ['key' => 'test2', 'en' => 'World', 'es_AR' => 'Mundo', 'fr' => 'Monde']
    ];
    
    echo "Setting headers...\n";
    // Set headers
    $col = 'A';
    foreach ($headers as $header) {
        $worksheet->setCellValue($col . '1', $header);
        echo "Set header at $col" . "1: $header\n";
        $col++;
    }
    
    echo "Setting data...\n";
    // Set data
    $row = 2;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($headers as $header) {
            $value = $rowData[$header] ?? '';
            $worksheet->setCellValue($col . $row, $value);
            echo "Set data at $col$row: $value\n";
            $col++;
        }
        $row++;
    }
    
    echo "Creating writer...\n";
    $writer = new Xlsx($spreadsheet);
    
    echo "Saving file...\n";
    $writer->save('debug_test.xlsx');
    
    echo "XLSX created successfully: debug_test.xlsx\n";
    echo "File size: " . filesize('debug_test.xlsx') . " bytes\n";
    
    // Test if file is readable
    if (file_exists('debug_test.xlsx')) {
        echo "File exists and is readable\n";
        
        // Try to read it back
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $testSpreadsheet = $reader->load('debug_test.xlsx');
        echo "File can be read back successfully\n";
    } else {
        echo "ERROR: File was not created\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

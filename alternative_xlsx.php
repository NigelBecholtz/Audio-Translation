<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== Creating Alternative XLSX ===\n";

try {
    // Create a completely new spreadsheet
    $spreadsheet = new Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Set worksheet title
    $worksheet->setTitle('Translations');
    
    // Test data
    $headers = ['key', 'en', 'es_AR', 'fr', 'de', 'it', 'nl', 'ro', 'gr', 'sk', 'lv', 'bg', 'fi', 'al', 'ca'];
    $data = [
        ['key' => 'Location_en', 'en' => 'English', 'es_AR' => 'Inglés', 'fr' => 'Anglais', 'de' => 'Englisch', 'it' => 'Inglese', 'nl' => 'Engels', 'ro' => 'Engleză', 'gr' => 'Αγγλικά', 'sk' => 'Angličtina', 'lv' => 'Angļu', 'bg' => 'Английски', 'fi' => 'Englanti', 'al' => 'Anglisht', 'ca' => 'Anglès'],
        ['key' => 'Location_es', 'en' => 'Spanish', 'es_AR' => 'Español', 'fr' => 'Espagnol', 'de' => 'Spanisch', 'it' => 'Spagnolo', 'nl' => 'Spaans', 'ro' => 'Spaniolă', 'gr' => 'Ισπανικά', 'sk' => 'Španielčina', 'lv' => 'Spāņu', 'bg' => 'Испански', 'fi' => 'Espanja', 'al' => 'Spanjisht', 'ca' => 'Espanyol']
    ];
    
    // Use fromArray method for better compatibility
    $allData = array_merge([$headers], $data);
    $worksheet->fromArray($allData, null, 'A1');
    
    // Style the header row
    $worksheet->getStyle('A1:' . chr(ord('A') + count($headers) - 1) . '1')->getFont()->setBold(true);
    
    // Auto-size columns
    foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) {
        $worksheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create writer
    $writer = new Xlsx($spreadsheet);
    
    // Save with different options
    $writer->save('alternative_test.xlsx');
    
    echo "Alternative XLSX created: alternative_test.xlsx\n";
    echo "File size: " . filesize('alternative_test.xlsx') . " bytes\n";
    
    // Test if we can read it back
    $reader = IOFactory::createReader('Xlsx');
    $testSpreadsheet = $reader->load('alternative_test.xlsx');
    echo "File can be read back successfully\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

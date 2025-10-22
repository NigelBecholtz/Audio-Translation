<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "=== Simple XLSX Test ===\n";

try {
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
    
    // Prepare data array with headers first
    $allData = array_merge([$headers], $data);
    
    // Use fromArray method for better compatibility
    $worksheet->fromArray($allData, null, 'A1');
    
    // Style the header row
    $worksheet->getStyle('A1:' . chr(ord('A') + count($headers) - 1) . '1')->getFont()->setBold(true);
    
    // Auto-size columns
    foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) {
        $worksheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create writer and save
    $writer = new Xlsx($spreadsheet);
    $writer->save('final_test.xlsx');
    
    echo "XLSX created successfully: final_test.xlsx\n";
    echo "File size: " . filesize('final_test.xlsx') . " bytes\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

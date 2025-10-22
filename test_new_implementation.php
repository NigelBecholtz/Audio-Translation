<?php

require 'vendor/autoload.php';

use App\Services\ExcelParserService;

echo "=== Testing New Implementation ===\n";

try {
    $parser = new ExcelParserService();
    
    $headers = ['key', 'en', 'es_AR', 'fr', 'de', 'it', 'nl', 'ro', 'gr', 'sk', 'lv', 'bg', 'fi', 'al', 'ca'];
    $data = [
        ['key' => 'Location_en', 'en' => 'English', 'es_AR' => 'Inglés', 'fr' => 'Anglais', 'de' => 'Englisch', 'it' => 'Inglese', 'nl' => 'Engels', 'ro' => 'Engleză', 'gr' => 'Αγγλικά', 'sk' => 'Angličtina', 'lv' => 'Angļu', 'bg' => 'Английски', 'fi' => 'Englanti', 'al' => 'Anglisht', 'ca' => 'Anglès'],
        ['key' => 'Location_es', 'en' => 'Spanish', 'es_AR' => 'Español', 'fr' => 'Espagnol', 'de' => 'Spanisch', 'it' => 'Spagnolo', 'nl' => 'Spaans', 'ro' => 'Spaniolă', 'gr' => 'Ισπανικά', 'sk' => 'Španielčina', 'lv' => 'Spāņu', 'bg' => 'Испански', 'fi' => 'Espanja', 'al' => 'Spanjisht', 'ca' => 'Espanyol']
    ];
    
    echo "Testing XLSX export...\n";
    $parser->exportToXlsx($headers, $data, 'new_implementation_test.xlsx');
    echo "XLSX export completed: new_implementation_test.xlsx\n";
    echo "File size: " . filesize('new_implementation_test.xlsx') . " bytes\n";
    
    echo "Testing CSV export...\n";
    $parser->exportToCsv($headers, $data, 'new_implementation_test.csv');
    echo "CSV export completed: new_implementation_test.csv\n";
    echo "File size: " . filesize('new_implementation_test.csv') . " bytes\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

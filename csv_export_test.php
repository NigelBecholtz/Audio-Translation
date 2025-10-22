<?php

echo "=== Creating CSV Export ===\n";

$headers = ['key', 'en', 'es_AR', 'fr', 'de', 'it', 'nl', 'ro', 'gr', 'sk', 'lv', 'bg', 'fi', 'al', 'ca'];
$data = [
    ['key' => 'Location_en', 'en' => 'English', 'es_AR' => 'Inglés', 'fr' => 'Anglais', 'de' => 'Englisch', 'it' => 'Inglese', 'nl' => 'Engels', 'ro' => 'Engleză', 'gr' => 'Αγγλικά', 'sk' => 'Angličtina', 'lv' => 'Angļu', 'bg' => 'Английски', 'fi' => 'Englanti', 'al' => 'Anglisht', 'ca' => 'Anglès'],
    ['key' => 'Location_es', 'en' => 'Spanish', 'es_AR' => 'Español', 'fr' => 'Espagnol', 'de' => 'Spanisch', 'it' => 'Spagnolo', 'nl' => 'Spaans', 'ro' => 'Spaniolă', 'gr' => 'Ισπανικά', 'sk' => 'Španielčina', 'lv' => 'Spāņu', 'bg' => 'Испански', 'fi' => 'Espanja', 'al' => 'Spanjisht', 'ca' => 'Espanyol']
];

$handle = fopen('csv_export_test.csv', 'w');
if ($handle !== FALSE) {
    // Write UTF-8 BOM for proper encoding
    fwrite($handle, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($handle, $headers, ',', '"', '\\');
    
    // Write data
    foreach ($data as $row) {
        $rowData = [];
        foreach ($headers as $header) {
            $value = $row[$header] ?? '';
            $rowData[] = $value;
        }
        fputcsv($handle, $rowData, ',', '"', '\\');
    }
    
    fclose($handle);
    echo "CSV export created: csv_export_test.csv\n";
    echo "File size: " . filesize('csv_export_test.csv') . " bytes\n";
} else {
    echo "ERROR: Could not create CSV file\n";
}

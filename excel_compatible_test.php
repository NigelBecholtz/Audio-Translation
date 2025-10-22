<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

echo "=== Creating Excel-Compatible XLSX ===\n";

try {
    $spreadsheet = new Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Test data
    $headers = ['key', 'en', 'es_AR', 'fr', 'de', 'it', 'nl', 'ro', 'gr', 'sk', 'lv', 'bg', 'fi', 'al', 'ca'];
    $data = [
        ['key' => 'Location_en', 'en' => 'English', 'es_AR' => 'Inglés', 'fr' => 'Anglais', 'de' => 'Englisch', 'it' => 'Inglese', 'nl' => 'Engels', 'ro' => 'Engleză', 'gr' => 'Αγγλικά', 'sk' => 'Angličtina', 'lv' => 'Angļu', 'bg' => 'Английски', 'fi' => 'Englanti', 'al' => 'Anglisht', 'ca' => 'Anglès'],
        ['key' => 'Location_es', 'en' => 'Spanish', 'es_AR' => 'Español', 'fr' => 'Espagnol', 'de' => 'Spanisch', 'it' => 'Spagnolo', 'nl' => 'Spaans', 'ro' => 'Spaniolă', 'gr' => 'Ισπανικά', 'sk' => 'Španielčina', 'lv' => 'Spāņu', 'bg' => 'Испански', 'fi' => 'Espanja', 'al' => 'Spanjisht', 'ca' => 'Espanyol']
    ];
    
    // Set headers with styling
    $col = 'A';
    foreach ($headers as $header) {
        $worksheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style headers
    $worksheet->getStyle('A1:' . chr(ord('A') + count($headers) - 1) . '1')->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '366092']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    
    // Set data
    $row = 2;
    foreach ($data as $rowData) {
        $col = 'A';
        foreach ($headers as $header) {
            $value = $rowData[$header] ?? '';
            $worksheet->setCellValue($col . $row, $value);
            $col++;
        }
        $row++;
    }
    
    // Style data rows
    $worksheet->getStyle('A2:' . chr(ord('A') + count($headers) - 1) . count($data) + 1)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    
    // Auto-size columns
    foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) {
        $worksheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set row height for headers
    $worksheet->getRowDimension(1)->setRowHeight(20);
    
    // Create writer with options
    $writer = new Xlsx($spreadsheet);
    
    // Set some writer options for better compatibility
    $writer->setOffice2003Compatibility(true);
    
    $writer->save('excel_compatible_test.xlsx');
    
    echo "Excel-compatible XLSX created: excel_compatible_test.xlsx\n";
    echo "File size: " . filesize('excel_compatible_test.xlsx') . " bytes\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

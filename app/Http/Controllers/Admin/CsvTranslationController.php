<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleTranslationService;
use App\Services\CsvParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvTranslationController extends Controller
{
    private $translationService;
    private $csvParser;

    public function __construct(
        GoogleTranslationService $translationService,
        CsvParserService $csvParser
    ) {
        $this->translationService = $translationService;
        $this->csvParser = $csvParser;
    }

    /**
     * Show CSV upload form
     */
    public function index()
    {
        return view('admin.csv-translations.index');
    }

    /**
     * Process uploaded CSV file and return translated version
     */
    public function process(Request $request)
    {
        // Validate upload
        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240', // 10MB max
            ]
        ], [
            'csv_file.required' => 'Please upload a CSV file',
            'csv_file.mimes' => 'File must be a CSV file',
            'csv_file.max' => 'File size must not exceed 10MB',
        ]);

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            // Use Laravel's public disk temp directory instead
            $tempFilename = 'csv_upload_' . time() . '_' . uniqid() . '.csv';
            $tempPath = $file->storeAs('temp', $tempFilename, 'public');
            $fullPath = storage_path('app/public/' . $tempPath);
            
            // Verify file was uploaded successfully
            if (!file_exists($fullPath)) {
                Log::error('CSV upload failed', [
                    'expected_path' => $fullPath,
                    'temp_path' => $tempPath,
                    'storage_path' => storage_path('app/public'),
                ]);
                throw new \Exception('Failed to upload file. Please contact administrator.');
            }

            // Validate CSV structure
            $validation = $this->csvParser->validate($fullPath);
            if (!$validation['valid']) {
                Storage::disk('public')->delete($tempPath);
                return back()->with('error', 'Invalid CSV: ' . $validation['message']);
            }

            Log::info('CSV translation started', [
                'file' => $originalName,
                'rows' => $validation['rows'],
                'languages' => $validation['languages']
            ]);

            // Parse CSV
            $parsed = $this->csvParser->parse($fullPath);
            $headers = $parsed['headers'];
            $data = $parsed['data'];

            // Get target language columns (exclude 'key' and 'en')
            $targetLanguages = array_diff($headers, ['key', 'en']);

            $translationsNeeded = 0;
            $translationsCompleted = 0;

            // Process each target language
            foreach ($targetLanguages as $targetLang) {
                // Collect texts that need translation for this language
                $textsToTranslate = [];
                $rowIndices = [];

                foreach ($data as $index => $row) {
                    // Only translate if cell is empty and source text exists
                    if (empty($row[$targetLang]) && !empty($row['en'])) {
                        $textsToTranslate[] = $row['en'];
                        $rowIndices[] = $index;
                        $translationsNeeded++;
                    }
                }

                // Batch translate all texts for this language
                if (!empty($textsToTranslate)) {
                    try {
                        $translations = $this->translationService->translateBatch($textsToTranslate, $targetLang);
                        
                        // Update data with translations
                        foreach ($rowIndices as $idx => $rowIndex) {
                            if (isset($translations[$idx])) {
                                $data[$rowIndex][$targetLang] = $translations[$idx];
                                $translationsCompleted++;
                            }
                        }

                        Log::info("Translated {$targetLang}", [
                            'count' => count($textsToTranslate),
                            'language' => $targetLang
                        ]);

                    } catch (\Exception $e) {
                        Log::warning("Failed to translate {$targetLang}: " . $e->getMessage());
                        // Continue with other languages even if one fails
                    }
                }
            }

            // Export to new CSV (use public disk temp directory)
            $outputFilename = 'translated_' . time() . '_' . uniqid() . '.csv';
            $outputPath = storage_path('app/public/temp/' . $outputFilename);
            
            // Ensure temp directory exists in public storage
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $this->csvParser->exportToCsv($headers, $data, $outputPath);

            // Cleanup original upload
            Storage::disk('public')->delete($tempPath);

            Log::info('CSV translation completed', [
                'file' => $originalName,
                'translations_needed' => $translationsNeeded,
                'translations_completed' => $translationsCompleted
            ]);

            // Return file download and cleanup after download
            return response()->download($outputPath, 'translated_' . $originalName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('CSV translation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Cleanup temp files
            if (isset($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            if (isset($outputPath) && file_exists($outputPath)) {
                unlink($outputPath);
            }

            return back()->with('error', 'Translation failed: ' . $e->getMessage());
        }
    }
}


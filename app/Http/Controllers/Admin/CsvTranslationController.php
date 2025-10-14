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
            
            // Store uploaded file temporarily
            $tempPath = $file->storeAs('temp', 'upload_' . time() . '.csv', 'local');
            $fullPath = storage_path('app/' . $tempPath);

            // Validate CSV structure
            $validation = $this->csvParser->validate($fullPath);
            if (!$validation['valid']) {
                Storage::disk('local')->delete($tempPath);
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

            // Export to new CSV
            $outputFilename = 'translated_' . time() . '.csv';
            $outputPath = storage_path('app/temp/' . $outputFilename);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $this->csvParser->exportToCsv($headers, $data, $outputPath);

            // Cleanup original upload
            Storage::disk('local')->delete($tempPath);

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
                Storage::disk('local')->delete($tempPath);
            }
            if (isset($outputPath) && file_exists($outputPath)) {
                unlink($outputPath);
            }

            return back()->with('error', 'Translation failed: ' . $e->getMessage());
        }
    }
}


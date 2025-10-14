<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleTranslationService;
use App\Services\CsvParserService;
use App\Services\LanguageDetectionService;
use App\Services\MultiSheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvTranslationController extends Controller
{
    private $translationService;
    private $csvParser;
    private $languageDetection;
    private $multiSheetService;

    public function __construct(
        GoogleTranslationService $translationService,
        CsvParserService $csvParser,
        LanguageDetectionService $languageDetection,
        MultiSheetService $multiSheetService
    ) {
        $this->translationService = $translationService;
        $this->csvParser = $csvParser;
        $this->languageDetection = $languageDetection;
        $this->multiSheetService = $multiSheetService;
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
        Log::info('CSV Translation process started', [
            'request_data' => $request->all(),
            'file_uploaded' => $request->hasFile('csv_file')
        ]);

        // Validate upload
        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'max:10240', // 10MB max
            ]
        ], [
            'csv_file.required' => 'Please upload a CSV or XLSX file',
            'csv_file.mimes' => 'File must be a CSV or XLSX file',
            'csv_file.max' => 'File size must not exceed 10MB',
        ]);

        Log::info('File validation passed');

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            Log::info('File details', [
                'original_name' => $originalName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            // Use Laravel's public disk temp directory instead
            $extension = $file->getClientOriginalExtension();
            $tempFilename = 'file_upload_' . time() . '_' . uniqid() . '.' . $extension;
            $tempPath = $file->storeAs('temp', $tempFilename, 'public');
            $fullPath = storage_path('app/public/' . $tempPath);
            
            Log::info('File stored', [
                'temp_path' => $tempPath,
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath)
            ]);
            
            // Verify file was uploaded successfully
            if (!file_exists($fullPath)) {
                Log::error('CSV upload failed', [
                    'expected_path' => $fullPath,
                    'temp_path' => $tempPath,
                    'storage_path' => storage_path('app/public'),
                ]);
                throw new \Exception('Failed to upload file. Please contact administrator.');
            }

            // Validate file structure
            Log::info('Starting file validation', ['file_path' => $fullPath]);
            $validation = $this->csvParser->validate($fullPath);
            $useSmartFallback = false;
            
            Log::info('File validation result', [
                'valid' => $validation['valid'],
                'message' => $validation['message'] ?? 'No message'
            ]);
            
            if (!$validation['valid']) {
                Log::info('File validation failed, checking smart fallback');
                // Check if we can use smart fallback
                if ($this->canUseSmartFallback($fullPath)) {
                    $useSmartFallback = true;
                    Log::info('Using smart fallback for file', ['file' => $originalName]);
                } else {
                    Log::error('File validation failed and smart fallback not available', [
                        'validation_message' => $validation['message']
                    ]);
                    Storage::disk('public')->delete($tempPath);
                    return back()->with('error', 'Invalid file: ' . $validation['message']);
                }
            }

            if ($useSmartFallback) {
                return $this->processSmartFallback($fullPath, $originalName, $request);
            }

            Log::info('File translation started', [
                'file' => $originalName,
                'rows' => $validation['rows'],
                'languages' => $validation['languages']
            ]);

            // Parse file
            $parsed = $this->csvParser->parse($fullPath);
            $headers = $parsed['headers'];
            $data = $parsed['data'];

            // Get target language columns (exclude 'en')
            $allTargetLanguages = array_diff($headers, ['en']);
            
            // Check if specific languages were selected
            $selectedLanguages = $request->input('languages', []);
            $targetLanguages = $allTargetLanguages;
            
            // If specific languages were selected, only translate those
            if (!empty($selectedLanguages)) {
                $targetLanguages = array_intersect($allTargetLanguages, $selectedLanguages);
                
                // Add missing selected languages as new columns if they don't exist
                foreach ($selectedLanguages as $lang) {
                    if (!in_array($lang, $headers)) {
                        $headers[] = $lang;
                        // Add empty column to all data rows
                        foreach ($data as $index => $row) {
                            $data[$index][$lang] = '';
                        }
                    }
                }
            }

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

            // Export to new file (use public disk temp directory)
            $outputExtension = $extension === 'xlsx' ? 'xlsx' : 'csv';
            $outputFilename = 'translated_' . time() . '_' . uniqid() . '.' . $outputExtension;
            $outputPath = storage_path('app/public/temp/' . $outputFilename);
            
            // Ensure temp directory exists in public storage
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $this->csvParser->exportToCsv($headers, $data, $outputPath);

            // Cleanup original upload
            Storage::disk('public')->delete($tempPath);

            Log::info('File translation completed', [
                'file' => $originalName,
                'translations_needed' => $translationsNeeded,
                'translations_completed' => $translationsCompleted
            ]);

            // Return file download and cleanup after download
            return response()->download($outputPath, 'translated_' . $originalName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('File translation failed', [
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

    /**
     * Check if file can use smart fallback
     */
    private function canUseSmartFallback(string $filePath): bool
    {
        try {
            $parsed = $this->csvParser->parse($filePath);
            $data = $parsed['data'];
            
            // Check if we have at least one row with text data
            foreach ($data as $row) {
                foreach ($row as $value) {
                    if (!empty(trim($value))) {
                        return true; // Found some text data
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Process file with smart fallback (language detection + multi-sheet output)
     */
    private function processSmartFallback(string $filePath, string $originalName, Request $request)
    {
        try {
            Log::info('Smart fallback process started', [
                'file_path' => $filePath,
                'original_name' => $originalName
            ]);
            
            // Parse file to get raw data
            Log::info('Parsing file for smart fallback');
            $parsed = $this->csvParser->parse($filePath);
            $data = $parsed['data'];
            
            Log::info('File parsed successfully', [
                'data_count' => count($data),
                'headers' => $parsed['headers'] ?? 'No headers'
            ]);
            
            // Extract all text content from the file
            $sourceTexts = [];
            foreach ($data as $row) {
                foreach ($row as $value) {
                    $text = trim($value);
                    if (!empty($text)) {
                        $sourceTexts[] = $text;
                    }
                }
            }
            
            Log::info('Text extraction completed', [
                'source_texts_count' => count($sourceTexts),
                'sample_texts' => array_slice($sourceTexts, 0, 3)
            ]);
            
            if (empty($sourceTexts)) {
                Log::error('No text content found in file');
                return back()->with('error', 'No text content found in file');
            }
            
            // Detect source language from first few texts
            $sampleText = implode(' ', array_slice($sourceTexts, 0, 3));
            Log::info('Detecting language', ['sample_text' => $sampleText]);
            $detectedLanguage = $this->languageDetection->detectLanguage($sampleText);
            
            Log::info('Smart fallback processing', [
                'file' => $originalName,
                'detected_language' => $detectedLanguage,
                'text_count' => count($sourceTexts)
            ]);
            
            // Get popular languages for translation
            $popularLanguages = $this->languageDetection->getPopularLanguages();
            
            // Remove detected language from target languages if it's in the list
            $targetLanguages = array_filter($popularLanguages, function($lang) use ($detectedLanguage) {
                return $lang !== $detectedLanguage;
            });
            
            // Limit to first 20 languages to avoid too many API calls
            $targetLanguages = array_slice($targetLanguages, 0, 20);
            
            $translations = [];
            $translationsCompleted = 0;
            
            // Translate to each target language
            foreach ($targetLanguages as $targetLang) {
                try {
                    $translatedTexts = $this->translationService->translateBatch($sourceTexts, $targetLang);
                    $translations[$targetLang] = $translatedTexts;
                    $translationsCompleted += count($translatedTexts);
                    
                    Log::info("Smart fallback translated {$targetLang}", [
                        'count' => count($translatedTexts),
                        'language' => $targetLang
                    ]);
                    
                } catch (\Exception $e) {
                    Log::warning("Smart fallback failed for {$targetLang}: " . $e->getMessage());
                    // Continue with other languages
                }
            }
            
            if (empty($translations)) {
                return back()->with('error', 'Failed to translate to any target languages');
            }
            
            // Create multi-sheet XLSX output
            $outputFilename = 'smart_translations_' . time() . '_' . uniqid() . '.xlsx';
            $outputPath = storage_path('app/public/temp/' . $outputFilename);
            
            // Ensure temp directory exists
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Create XLSX with separate sheets for each language
            $this->multiSheetService->createMultiSheetXlsx($sourceTexts, $translations, $detectedLanguage, $outputPath);
            
            Log::info('Smart fallback completed', [
                'file' => $originalName,
                'source_language' => $detectedLanguage,
                'target_languages' => array_keys($translations),
                'translations_completed' => $translationsCompleted
            ]);
            
            return response()->download($outputPath, $outputFilename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Smart fallback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Smart fallback failed: ' . $e->getMessage());
        }
    }
}


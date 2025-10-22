<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CsvTranslationJob;
use App\Jobs\ProcessCsvTranslationJob;
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
     * Process uploaded CSV file using queue for background processing
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
                'max:102400', // Increased to 100MB for large files
            ]
        ], [
            'csv_file.required' => 'Please upload a CSV or XLSX file',
            'csv_file.mimes' => 'File must be a CSV or XLSX file',
            'csv_file.max' => 'File size must not exceed 100MB',
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
            
            // Store file
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

            // Get selected languages
            $selectedLanguages = $request->input('languages', []);

            // Create translation job record
            $translationJob = CsvTranslationJob::create([
                'user_id' => auth()->id(),
                'original_filename' => $originalName,
                'file_path' => $tempPath,
                'status' => 'pending',
                'target_languages' => !empty($selectedLanguages) ? $selectedLanguages : null,
                'use_smart_fallback' => $useSmartFallback,
            ]);

            // Dispatch job to queue
            ProcessCsvTranslationJob::dispatch($translationJob);

            Log::info('Translation job created and dispatched', [
                'job_id' => $translationJob->id,
                'file' => $originalName,
                'use_smart_fallback' => $useSmartFallback
            ]);

            // Redirect to status page
            return redirect()->route('admin.csv-translations.status', $translationJob->id)
                ->with('success', 'File uploaded! Translation is processing in the background. This page will update automatically.');

        } catch (\Exception $e) {
            Log::error('File translation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Cleanup temp files
            if (isset($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }

            return back()->with('error', 'Translation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show translation job status
     */
    public function status(CsvTranslationJob $job)
    {
        // Ensure user can only see their own jobs (or admin sees all)
        if (!auth()->user()->is_admin && $job->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return view('admin.csv-translations.status', compact('job'));
    }

    /**
     * API endpoint for polling job status
     */
    public function statusApi(CsvTranslationJob $job)
    {
        // Ensure user can only see their own jobs (or admin sees all)
        if (!auth()->user()->is_admin && $job->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'status' => $job->status,
            'progress_percentage' => $job->progress_percentage,
            'processed_items' => $job->processed_items,
            'total_items' => $job->total_items,
            'failed_items' => $job->failed_items,
            'error_message' => $job->error_message,
            'is_completed' => $job->isCompleted(),
            'is_failed' => $job->isFailed(),
            'download_url' => $job->isCompleted() ? route('admin.csv-translations.download', $job->id) : null,
        ]);
    }

    /**
     * Download translated file
     */
    public function download(CsvTranslationJob $job)
    {
        // Ensure user can only download their own jobs (or admin downloads all)
        if (!auth()->user()->is_admin && $job->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!$job->isCompleted()) {
            return back()->with('error', 'Translation is not yet completed');
        }

        if (!$job->output_path) {
            return back()->with('error', 'Output file not found');
        }

        $fullPath = storage_path('app/public/' . $job->output_path);

        if (!file_exists($fullPath)) {
            return back()->with('error', 'Output file no longer exists');
        }

        $downloadName = 'translated_' . $job->original_filename;

        return response()->download($fullPath, $downloadName);
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
}


<?php

namespace App\Http\Controllers;

use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use App\Services\SanitizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioController extends Controller
{
    public function index()
    {
        $audioFiles = auth()->user()->audioFiles()->orderBy('created_at', 'desc')->paginate(10);
        $textToAudioFiles = auth()->user()->textToAudioFiles()->orderBy('created_at', 'desc')->paginate(10);
        $user = auth()->user();
        
        return view('audio.index', compact('audioFiles', 'textToAudioFiles', 'user'));
    }

    public function create()
    {
        $user = auth()->user();
        
        if (!$user->canMakeTranslation()) {
            return redirect()->route('audio.index')->with('error', 
                'You have no more translations available. Upgrade your account for more translations!'
            );
        }
        
        return view('audio.create', compact('user'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Check if user can make translation
        if (!$user->canMakeTranslation()) {
            return redirect()->route('audio.index')->with('error', 
                'You have no more translations available. Upgrade your account for more translations!'
            );
        }
        
        // Set PHP limits FIRST, before any processing
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '50M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');
        
        // Debug: Log request start
        Log::info('=== AUDIO UPLOAD DEBUG START ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request URL: ' . $request->fullUrl());
        Log::info('Content-Length header: ' . $request->header('content-length'));
        Log::info('Content-Type header: ' . $request->header('content-type'));
        Log::info('Has file audio: ' . ($request->hasFile('audio') ? 'YES' : 'NO'));
        
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            Log::info('File details:', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'is_valid' => $file->isValid(),
                'error' => $file->getError()
            ]);
        }
        
        Log::info('PHP limits set:', [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ]);
        
        // Check content length manually to bypass ValidatePostSize middleware
        $contentLength = $request->header('content-length');
        Log::info('Content length check:', [
            'content_length' => $contentLength,
            'max_allowed' => 50 * 1024 * 1024,
            'is_too_large' => $contentLength && $contentLength > 50 * 1024 * 1024
        ]);
        
        if ($contentLength && $contentLength > 50 * 1024 * 1024) {
            Log::warning('File too large, redirecting back');
            return redirect()->back()->withErrors([
                'audio' => 'File is too large. Maximum 50MB allowed.'
            ]);
        }
        
        Log::info('Starting validation...');
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,mp4|max:51200', // 50MB max, including M4A and MP4
            'source_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
            'target_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
            'voice' => 'required|string',
            'style_instruction' => 'nullable|string|max:5000', // Allow longer style instructions
        ]);
        Log::info('Validation passed!');

        try {
            Log::info('Starting file upload...');
            
            // Sanitize inputs
            $sanitizer = new SanitizationService();
            $sourceLanguage = $sanitizer->sanitizeLanguageCode($request->source_language);
            $targetLanguage = $sanitizer->sanitizeLanguageCode($request->target_language);
            $voice = $sanitizer->sanitizeVoiceName($request->voice);
            $styleInstruction = $sanitizer->sanitizeStyleInstruction($request->style_instruction);
            
            // Upload audio file
            $audioFile = $request->file('audio');
            $originalFilename = $sanitizer->sanitizeFilename($audioFile->getClientOriginalName());
            $filename = time() . '_' . $originalFilename;
            Log::info('Generated filename: ' . $filename);
            
            $path = $audioFile->storeAs('audio', $filename, 'public');
            Log::info('File stored at path: ' . $path);

            // Create database record
            Log::info('Creating database record...');
            $audioRecord = AudioFile::create([
                'user_id' => $user->id,
                'original_filename' => $originalFilename,
                'file_path' => $path,
                'file_size' => $audioFile->getSize(),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'uploaded'
            ]);
            Log::info('Database record created with ID: ' . $audioRecord->id);

            // Process immediately (sync queue)
            Log::info('Starting AI processing...');
            $this->processAudio($audioRecord->id);
            Log::info('AI processing completed');

            Log::info('Redirecting to show page: ' . route('audio.show', $audioRecord->id));
            return redirect()->route('audio.show', $audioRecord->id)
                ->with('success', 'Audio file processed successfully!');

        } catch (\Exception $e) {
            Log::error('Upload failed with exception: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        return view('audio.show', compact('audioFile'));
    }

    public function download($id)
    {
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        
        if (!$audioFile->isCompleted() || !$audioFile->translated_audio_path) {
            return back()->with('error', 'Audio file is not ready for download yet.');
        }

        return Storage::disk('public')->download($audioFile->translated_audio_path);
    }

    public function destroy($id)
    {
        try {
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);
            
            // Delete original audio file
            if ($audioFile->file_path && Storage::disk('public')->exists($audioFile->file_path)) {
                Storage::disk('public')->delete($audioFile->file_path);
            }
            
            // Delete translated audio file
            if ($audioFile->translated_audio_path && Storage::disk('public')->exists($audioFile->translated_audio_path)) {
                Storage::disk('public')->delete($audioFile->translated_audio_path);
            }
            
            // Delete database record (this will also delete related translations due to cascade)
            $audioFile->delete();
            
            return redirect()->route('audio.index')
                ->with('success', 'Audio translation successfully deleted.');
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    private function processAudio($audioFileId)
    {
        $audioFile = AudioFile::findOrFail($audioFileId);
        $processingService = new AudioProcessingService();
        
        try {
            // Step 1: Transcribe with Whisper
            $audioFile->update(['status' => 'transcribing']);
            $transcription = $processingService->transcribeAudio($audioFile);
            $audioFile->update(['transcription' => $transcription]);

            // Step 2: Translate text
            $audioFile->update(['status' => 'translating']);
            $translatedText = $processingService->translateText(
                $transcription,
                $audioFile->source_language,
                $audioFile->target_language
            );
            $audioFile->update(['translated_text' => $translatedText]);

            // Step 3: Generate audio with TTS
            $audioFile->update(['status' => 'generating_audio']);
            $translatedAudioPath = $processingService->generateAudio(
                $translatedText,
                $audioFile->target_language,
                $audioFile->voice,
                $audioFile->style_instruction
            );
            $audioFile->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $audioFile->user,
                0.5,
                'Credits used for audio translation'
            );

        } catch (\Exception $e) {
            Log::error('Audio processing failed', [
                'audio_file_id' => $audioFileId,
                'error' => $e->getMessage()
            ]);
            $audioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }

}

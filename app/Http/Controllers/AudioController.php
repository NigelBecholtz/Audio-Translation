<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAudioJob;
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
        $maxUploadSize = config('audio.max_upload_size', 100);
        ini_set('upload_max_filesize', $maxUploadSize . 'M');
        ini_set('post_max_size', ($maxUploadSize + 10) . 'M');
        ini_set('max_execution_time', config('audio.max_execution_time', 600));
        ini_set('max_input_time', config('audio.max_input_time', 600));
        ini_set('memory_limit', config('audio.memory_limit', '512M'));
        
        // Check content length manually
        $contentLength = $request->header('content-length');
        $maxSize = $maxUploadSize * 1024 * 1024;
        
        if ($contentLength && $contentLength > $maxSize) {
            return redirect()->back()->withErrors([
                'audio' => "File is too large. Maximum {$maxUploadSize}MB allowed (will be compressed automatically if needed)."
            ]);
        }
        
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,mp4,ogg,flac|max:' . ($maxUploadSize * 1024), // 100MB max (will compress)
            'source_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
            'target_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
            'voice' => 'required|string',
            'style_instruction' => 'nullable|string|max:5000', // Allow longer style instructions
        ]);

        try {
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
            $path = $audioFile->storeAs('audio', $filename, 'public');

            // Create database record
            $audioRecord = AudioFile::create([
                'user_id' => $user->id,
                'original_filename' => $originalFilename,
                'file_path' => $path,
                'file_size' => $audioFile->getSize(),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'uploaded',
                'processing_stage' => 'uploaded',
                'processing_progress' => 5,
                'processing_message' => 'Upload complete! Starting processing...'
            ]);

            // Dispatch job to queue for background processing
            ProcessAudioJob::dispatch($audioRecord);

            // Redirect immediately to show page with progress bar
            return redirect()->route('audio.show', $audioRecord->id)
                ->with('success', 'Audio file uploaded! Processing started...');

        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $audioFile = AudioFile::findOrFail($id);
        $this->authorize('view', $audioFile);
        
        return view('audio.show', compact('audioFile'));
    }

    public function download($id)
    {
        $audioFile = AudioFile::findOrFail($id);
        $this->authorize('download', $audioFile);
        
        if (!$audioFile->isCompleted() || !$audioFile->translated_audio_path) {
            return back()->with('error', 'Audio file is not ready for download yet.');
        }

        return Storage::disk('public')->download($audioFile->translated_audio_path);
    }

    public function destroy($id)
    {
        try {
            $audioFile = AudioFile::findOrFail($id);
            $this->authorize('delete', $audioFile);
            
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

    /**
     * Get processing status for AJAX polling
     */
    public function status($id)
    {
        $audioFile = AudioFile::findOrFail($id);
        $this->authorize('view', $audioFile);
        
        return response()->json([
            'status' => $audioFile->status,
            'processing_stage' => $audioFile->processing_stage,
            'processing_progress' => $audioFile->processing_progress,
            'processing_message' => $audioFile->processing_message,
            'error_message' => $audioFile->error_message,
            'is_completed' => $audioFile->status === 'completed',
            'is_failed' => $audioFile->status === 'failed',
            'is_processing' => in_array($audioFile->status, ['uploaded', 'transcribing', 'translating', 'generating_audio']),
        ]);
    }
}

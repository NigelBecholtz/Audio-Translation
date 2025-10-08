<?php

namespace App\Http\Controllers;

use App\Actions\Audio\CreateAudioTranslationAction;
use App\Http\Requests\StoreAudioRequest;
use App\Jobs\ProcessAudioJob;
use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use App\Services\SanitizationService;
use App\Traits\HasAudioFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AudioController extends Controller
{
    use HasAudioFiles;
    public function index()
    {
        // Eager load relationships to avoid N+1 queries
        $audioFiles = auth()->user()
            ->audioFiles()
            ->with('user') // Eager load user relationship
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $textToAudioFiles = auth()->user()
            ->textToAudioFiles()
            ->with('user') // Eager load user relationship
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
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

    public function store(StoreAudioRequest $request, CreateAudioTranslationAction $action)
    {
        try {
            // Create audio translation using Action
            $audioRecord = $action->execute(
                auth()->user(),
                $request->file('audio'),
                $request->validated()
            );

            // Dispatch job to queue for background processing
            ProcessAudioJob::dispatch($audioRecord);

            // Redirect immediately to show page with progress bar
            return redirect()->route('audio.show', $audioRecord->id)
                ->with('success', 'Audiobestand geüpload! Verwerking gestart...');

        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Upload mislukt: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Use user's audioFiles relationship for automatic authorization
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        
        return view('audio.show', compact('audioFile'));
    }

    public function download($id)
    {
        // Use user's audioFiles relationship for automatic authorization
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        
        if (!$audioFile->isCompleted() || !$audioFile->translated_audio_path) {
            return back()->with('error', 'Audiobestand is nog niet klaar voor download.');
        }

        return Storage::disk('public')->download($audioFile->translated_audio_path);
    }

    public function destroy($id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);
            
            // Delete audio files using trait
            $this->deleteAudioFile($audioFile->file_path);
            $this->deleteAudioFile($audioFile->translated_audio_path);
            
            // Delete database record (this will also delete related translations due to cascade)
            $audioFile->delete();
            
            return redirect()->route('audio.index')
                ->with('success', 'Audiovertaling succesvol verwijderd.');
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return back()->with('error', 'Verwijderen mislukt: ' . $e->getMessage());
        }
    }

    /**
     * Get processing status for AJAX polling
     */
    public function status($id)
    {
        // Use user's audioFiles relationship for automatic authorization
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        
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

<?php

namespace App\Http\Controllers;

use App\Actions\TextToAudio\CreateTextToAudioAction;
use App\Http\Requests\StoreTextToAudioRequest;
use App\Jobs\ProcessTextToAudioJob;
use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use App\Services\SanitizationService;
use App\Traits\HasAudioFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TextToAudioController extends Controller
{
    use HasAudioFiles;
    public function index()
    {
        $textToAudioFiles = auth()->user()->textToAudioFiles()->orderBy('created_at', 'desc')->paginate(10);
        $user = auth()->user();
        
        return view('text-to-audio.index', compact('textToAudioFiles', 'user'));
    }

    public function create()
    {
        $user = auth()->user();
        
        if (!$user->canMakeTranslation()) {
            return redirect()->route('text-to-audio.index')->with('error', 
                'You have no more translations available. Upgrade your account for more translations!'
            );
        }
        
        return view('text-to-audio.create', compact('user'));
    }

    public function store(StoreTextToAudioRequest $request, CreateTextToAudioAction $action)
    {
        try {
            // Create text-to-audio using Action
            $textToAudioRecord = $action->execute(
                auth()->user(),
                $request->validated()
            );

            // Dispatch job to queue for background processing
            ProcessTextToAudioJob::dispatch($textToAudioRecord);

            return redirect()->route('text-to-audio.show', $textToAudioRecord->id)
                ->with('success', 'Audio generatie gestart!');

        } catch (\Exception $e) {
            Log::error('Text to audio failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Text naar audio conversie mislukt: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Use user's textToAudioFiles relationship for automatic authorization
        $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
        
        return view('text-to-audio.show', compact('textToAudioFile'));
    }

    public function download($id)
    {
        // Use user's textToAudioFiles relationship for automatic authorization
        $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
        
        if (!$textToAudioFile->isCompleted() || !$textToAudioFile->audio_path) {
            return back()->with('error', 'Audiobestand is nog niet klaar voor download.');
        }

        return Storage::disk('public')->download($textToAudioFile->audio_path);
    }

    public function destroy($id)
    {
        try {
            // Use user's textToAudioFiles relationship for automatic authorization
            $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
            
            // Delete audio file using trait
            $this->deleteAudioFile($textToAudioFile->audio_path);
            
            // Delete database record
            $textToAudioFile->delete();
            
            return redirect()->route('text-to-audio.index')
                ->with('success', 'Text naar audio conversie succesvol verwijderd.');
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return back()->with('error', 'Verwijderen mislukt: ' . $e->getMessage());
        }
    }
}

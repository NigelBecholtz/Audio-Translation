<?php

namespace App\Http\Controllers;

use App\Models\TextToAudio;
use App\Services\AudioProcessingService;
use App\Services\SanitizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TextToAudioController extends Controller
{
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

    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Check if user can make translation
        if (!$user->canMakeTranslation()) {
            return redirect()->route('text-to-audio.index')->with('error', 
                'You have no more translations available. Upgrade your account for more translations!'
            );
        }
        
        $request->validate([
            'text_content' => 'required|string|max:50000',
            'language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',
            'voice' => 'required|string',
            'style_instruction' => 'nullable|string|max:5000',
        ]);

        try {
            // Sanitize inputs
            $sanitizer = new SanitizationService();
            $textContent = $sanitizer->sanitizeText($request->text_content);
            $language = $sanitizer->sanitizeLanguageCode($request->language);
            $voice = $sanitizer->sanitizeVoiceName($request->voice);
            $styleInstruction = $sanitizer->sanitizeStyleInstruction($request->style_instruction);

            // Create database record
            $textToAudioRecord = TextToAudio::create([
                'user_id' => $user->id,
                'text_content' => $textContent,
                'language' => $language,
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'processing'
            ]);

            // Process immediately (sync queue)
            $this->processTextToAudio($textToAudioRecord->id);

            return redirect()->route('text-to-audio.show', $textToAudioRecord->id)
                ->with('success', 'Audio generated successfully!');

        } catch (\Exception $e) {
            Log::error('Text to audio failed: ' . $e->getMessage());
            return back()->with('error', 'Text to audio failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
        return view('text-to-audio.show', compact('textToAudioFile'));
    }

    public function download($id)
    {
        $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
        
        if (!$textToAudioFile->isCompleted() || !$textToAudioFile->audio_path) {
            return back()->with('error', 'Audio file is not ready for download yet.');
        }

        return Storage::disk('public')->download($textToAudioFile->audio_path);
    }

    public function destroy($id)
    {
        try {
            $textToAudioFile = auth()->user()->textToAudioFiles()->findOrFail($id);
            
            // Delete audio file
            if ($textToAudioFile->audio_path && Storage::disk('public')->exists($textToAudioFile->audio_path)) {
                Storage::disk('public')->delete($textToAudioFile->audio_path);
            }
            
            // Delete database record
            $textToAudioFile->delete();
            
            return redirect()->route('text-to-audio.index')
                ->with('success', 'Text to audio conversion successfully deleted.');
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    private function processTextToAudio($textToAudioId)
    {
        $textToAudioFile = TextToAudio::findOrFail($textToAudioId);
        $processingService = new AudioProcessingService();
        
        try {
            // Generate audio with TTS
            $audioPath = $processingService->generateAudio(
                $textToAudioFile->text_content,
                $textToAudioFile->language,
                $textToAudioFile->voice,
                $textToAudioFile->style_instruction
            );
            
            $textToAudioFile->update([
                'audio_path' => $audioPath,
                'status' => 'completed'
            ]);
            
            // Deduct credits
            $processingService->deductCredits(
                $textToAudioFile->user,
                0.5,
                'Credits used for text to audio conversion'
            );

        } catch (\Exception $e) {
            Log::error('Text to audio processing failed', [
                'text_to_audio_id' => $textToAudioId,
                'error' => $e->getMessage()
            ]);
            $textToAudioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}

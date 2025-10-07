<?php

namespace App\Http\Controllers;

use App\Models\TextToAudio;
use App\Models\CreditTransaction;
use App\Services\GeminiTtsService;
use App\Services\SimpleTtsService;
use App\Services\SanitizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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
        Log::info('=== TEXT TO AUDIO PROCESSING DEBUG START ===');
        Log::info('Processing text to audio ID: ' . $textToAudioId);
        
        $textToAudioFile = TextToAudio::findOrFail($textToAudioId);
        Log::info('Text to audio file found');
        
        try {
            // Generate audio with TTS
            Log::info('Starting TTS generation...');
            $audioPath = $this->generateAudio($textToAudioFile->text_content, $textToAudioFile->language, $textToAudioFile->voice, $textToAudioFile->style_instruction);
            $textToAudioFile->update([
                'audio_path' => $audioPath,
                'status' => 'completed'
            ]);
            Log::info('TTS generation completed, path: ' . $audioPath);
            
            // Update user usage
            $this->updateUserUsage($textToAudioFile->user);
            
            Log::info('=== TEXT TO AUDIO PROCESSING DEBUG END - SUCCESS ===');

        } catch (\Exception $e) {
            Log::error('Text to audio processing failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            $textToAudioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            Log::info('=== TEXT TO AUDIO PROCESSING DEBUG END - FAILED ===');
        }
    }

    private function generateAudio($text, $language, $voice, $styleInstruction = null)
    {
        try {
            Log::info('=== GEMINI TTS GENERATION DEBUG ===');
            Log::info('Language: ' . $language);
            Log::info('Voice: ' . $voice);
            Log::info('Text length: ' . strlen($text));
            Log::info('Text preview: ' . substr($text, 0, 100) . '...');
            
            // Initialize Gemini TTS service
            $geminiTts = new GeminiTtsService();
            
            // Use Gemini TTS for better accent support
            Log::info('Using Gemini 2.5 Pro TTS for improved accent support');
            $path = $geminiTts->generateAudio($text, $language, $voice, $styleInstruction);
            
            Log::info('Gemini TTS audio generated successfully', [
                'language' => $language,
                'voice' => $voice,
                'file_path' => $path
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Gemini TTS generation failed', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Audio generation failed: ' . $e->getMessage());
        }
    }


    private function updateUserUsage($user)
    {
        // Use free translations first
        if ($user->translations_used < $user->translations_limit) {
            $user->increment('translations_used');
        } else {
            // Then use credits
            $user->decrement('credits', 0.50); // €0.50 per translation
            $newBalance = $user->fresh()->credits;
            
            // Create credit transaction record for usage
            CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => null, // System transaction
                'amount' => -0.50, // Negative amount for usage
                'type' => 'usage',
                'description' => 'Credits used for text to audio conversion',
                'balance_after' => $newBalance,
            ]);
        }
    }
}

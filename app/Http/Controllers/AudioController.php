<?php

namespace App\Http\Controllers;

use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Models\TextToAudio;
use App\Services\GeminiTtsService;
use App\Services\SimpleTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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
            
            // Upload audio file
            $audioFile = $request->file('audio');
            $filename = time() . '_' . $audioFile->getClientOriginalName();
            Log::info('Generated filename: ' . $filename);
            
            $path = $audioFile->storeAs('audio', $filename, 'public');
            Log::info('File stored at path: ' . $path);

            // Create database record
            Log::info('Creating database record...');
            $audioRecord = AudioFile::create([
                'user_id' => $user->id,
                'original_filename' => $audioFile->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $audioFile->getSize(),
                'source_language' => $request->source_language,
                'target_language' => $request->target_language,
                'voice' => $request->voice,
                'style_instruction' => $request->style_instruction,
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
        Log::info('=== AI PROCESSING DEBUG START ===');
        Log::info('Processing audio file ID: ' . $audioFileId);
        
        $audioFile = AudioFile::findOrFail($audioFileId);
        Log::info('Audio file found: ' . $audioFile->original_filename);
        
        try {
            // Step 1: Transcribe with Whisper
            Log::info('Step 1: Starting transcription...');
            $audioFile->update(['status' => 'transcribing']);
            $transcription = $this->transcribeWithWhisper($audioFile);
            $audioFile->update(['transcription' => $transcription]);
            Log::info('Step 1: Transcription completed, length: ' . strlen($transcription));

            // Step 2: Translate text
            Log::info('Step 2: Starting translation...');
            $audioFile->update(['status' => 'translating']);
            $translatedText = $this->translateText($transcription, $audioFile->source_language, $audioFile->target_language);
            $audioFile->update(['translated_text' => $translatedText]);
            Log::info('Step 2: Translation completed, length: ' . strlen($translatedText));

            // Step 3: Generate audio with TTS
            Log::info('Step 3: Starting TTS generation...');
            $audioFile->update(['status' => 'generating_audio']);
            $translatedAudioPath = $this->generateAudio($translatedText, $audioFile->target_language, $audioFile->voice, $audioFile->style_instruction);
            $audioFile->update([
                'translated_audio_path' => $translatedAudioPath,
                'status' => 'completed'
            ]);
            Log::info('Step 3: TTS generation completed, path: ' . $translatedAudioPath);
            
            // Update user usage
            $this->updateUserUsage($audioFile->user);
            
            Log::info('=== AI PROCESSING DEBUG END - SUCCESS ===');

        } catch (\Exception $e) {
            Log::error('AI processing failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            $audioFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            Log::info('=== AI PROCESSING DEBUG END - FAILED ===');
        }
    }

    private function transcribeWithWhisper(AudioFile $audioFile)
    {
        Log::info('=== WHISPER TRANSCRIPTION DEBUG ===');
        Log::info('Audio file ID: ' . $audioFile->id);
        Log::info('Source language: ' . $audioFile->source_language);
        
        try {
            $audioPath = Storage::disk('public')->path($audioFile->file_path);
            Log::info('Audio path: ' . $audioPath);
            Log::info('File exists: ' . (file_exists($audioPath) ? 'YES' : 'NO'));
            
            if (!file_exists($audioPath)) {
                throw new \Exception('Audio file not found: ' . $audioPath);
            }
            
            Log::info('Calling OpenAI Whisper API...');
            $response = OpenAI::audio()->transcribe([
                'file' => fopen($audioPath, 'r'),
                'model' => 'whisper-1',
                'language' => $audioFile->source_language,
                'response_format' => 'json'
            ]);

            Log::info('Whisper API response received');
            Log::info('Transcription length: ' . strlen($response->text));
            Log::info('Transcription preview: ' . substr($response->text, 0, 100) . '...');

            return $response->text;
            
        } catch (\Exception $e) {
            Log::error('Whisper transcription failed: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            throw new \Exception('Transcription failed: ' . $e->getMessage());
        }
    }

    private function translateText($text, $sourceLanguage, $targetLanguage)
    {
        try {
            // Skip translation if source and target are the same
            if ($sourceLanguage === $targetLanguage) {
                return $text;
            }
            
            $languageNames = [
                'en' => 'English',
                'es' => 'Spanish', 
                'fr' => 'French',
                'de' => 'German',
                'nl' => 'Dutch',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'zh' => 'Chinese',
                'ar' => 'Arabic',
                'hi' => 'Hindi',
                'sv' => 'Swedish',
                'sq' => 'Albanian',
                'bg' => 'Bulgarian',
                'sk' => 'Slovak',
                'lv' => 'Latvian',
                'fi' => 'Finnish',
                'el' => 'Greek',
                'ro' => 'Romanian',
                'ca' => 'Catalan'
            ];
            
            $sourceLangName = $languageNames[$sourceLanguage] ?? $sourceLanguage;
            $targetLangName = $languageNames[$targetLanguage] ?? $targetLanguage;
            
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a professional translator. Translate the following text from {$sourceLangName} to {$targetLangName}. Only return the translated text, nothing else."
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ]);
            
            $translatedText = trim($response->choices[0]->message->content);
            
            Log::info('Translation completed', [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'text_length' => strlen($text),
                'translated_length' => strlen($translatedText)
            ]);
            
            return $translatedText;
            
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Translation failed: ' . $e->getMessage());
        }
    }

    private function generateAudio($text, $language, $voice = null, $styleInstruction = null)
    {
        try {
            Log::info('=== GEMINI TTS GENERATION DEBUG ===');
            Log::info('Language: ' . $language);
            Log::info('Text length: ' . strlen($text));
            Log::info('Text preview: ' . substr($text, 0, 100) . '...');
            
            // Initialize Gemini TTS service
            $geminiTts = new GeminiTtsService();
            
            // Use Gemini TTS for better accent support
            Log::info('Using Gemini 2.5 Pro TTS for improved accent support');
            $path = $geminiTts->generateAudio($text, $language, $voice, $styleInstruction ?? null);
            
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
                'description' => 'Credits used for audio translation',
                'balance_after' => $newBalance,
            ]);
        }
    }
}

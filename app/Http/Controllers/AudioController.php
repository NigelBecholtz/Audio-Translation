<?php

namespace App\Http\Controllers;

use App\Actions\Audio\CreateAudioTranslationAction;
use App\Http\Requests\StoreAudioRequest;
use App\Jobs\ProcessAudioJob;
use App\Jobs\ProcessAdditionalAudioTranslation;
use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Models\TextToAudio;
use App\Models\AudioTranslation;
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
                ->with('success', __('Audio file uploaded! Processing started...'));

        } catch (\Exception $e) {
            Log::error('Audio upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', __('Upload failed: ') . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Use user's audioFiles relationship for automatic authorization
        // Eager load audioTranslations to prevent null issues in view
        $audioFile = auth()->user()->audioFiles()
            ->with('audioTranslations')
            ->findOrFail($id);
        
        return view('audio.show', compact('audioFile'));
    }

    public function download($id)
    {
        // Use user's audioFiles relationship for automatic authorization
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);
        
        if (!$audioFile->isCompleted() || !$audioFile->translated_audio_path) {
            return back()->with('error', __('Audio file is not ready for download yet.'));
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
                ->with('success', __('Audio translation deleted successfully.'));
                
        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return back()->with('error', __('Delete failed: ') . $e->getMessage());
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
            'is_pending_approval' => $audioFile->status === 'pending_approval',
            'is_pending_tts_approval' => $audioFile->status === 'pending_tts_approval',
        ]);
    }

    /**
     * Save edited transcription without proceeding to translation
     */
    public function saveTranscription(Request $request, $id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);

            if ($audioFile->status !== 'pending_approval') {
                return response()->json(['error' => __('This transcription is not pending approval.')], 400);
            }

            // Validate the edited transcription
            $request->validate([
                'transcription' => [
                    'required',
                    'string',
                    'max:50000', // Same max as text-to-audio
                ],
            ]);

            $editedTranscription = trim($request->input('transcription'));

            if (empty($editedTranscription)) {
                return response()->json(['error' => __('Transcription cannot be empty.')], 400);
            }

            // Update the transcription with the edited version
            $audioFile->update([
                'transcription' => $editedTranscription
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Transcription saved successfully!')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to save transcription', [
                'audio_file_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => __('Failed to save transcription: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Approve transcription and continue with translation and audio generation
     */
    public function approveTranscription(Request $request, $id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);

            if ($audioFile->status !== 'pending_approval') {
                return back()->with('error', __('This transcription is not pending approval.'));
            }

            if (!$audioFile->transcription) {
                return back()->with('error', __('No transcription found. Please save your transcription first.'));
            }

            // Update status to show translation is starting
            $audioFile->update([
                'status' => 'translating',
                'processing_stage' => 'translating',
                'processing_progress' => 10,
                'processing_message' => __('Starting translation...')
            ]);

            // Dispatch job to continue with translation and audio generation
            \App\Jobs\ProcessAudioTranslationJob::dispatch($audioFile);

            return redirect()->route('audio.show', $audioFile->id)
                ->with('success', __('Transcription approved! Translation and audio generation started...'));

        } catch (\Exception $e) {
            Log::error('Failed to approve transcription', [
                'audio_file_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', __('Failed to approve transcription: ') . $e->getMessage());
        }
    }

    /**
     * Save edited translated text
     */
    public function saveTranslatedText(Request $request, $id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);

            if ($audioFile->status !== 'pending_tts_approval') {
                return response()->json(['error' => __('This translation is not pending approval.')], 400);
            }

            // Validate the edited translated text
            $request->validate([
                'translated_text' => [
                    'required',
                    'string',
                    'max:50000', // Same max as text-to-audio
                ],
            ]);

            $editedTranslatedText = trim($request->input('translated_text'));

            if (empty($editedTranslatedText)) {
                return response()->json(['error' => __('Translated text cannot be empty.')], 400);
            }

            // Update the translated text with the edited version
            $audioFile->update([
                'translated_text' => $editedTranslatedText
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Translated text saved successfully!')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to save translated text', [
                'audio_file_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => __('Failed to save translated text: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Approve translation and start TTS generation
     */
    public function approveTTS($id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);

            if ($audioFile->status !== 'pending_tts_approval') {
                return back()->with('error', __('This translation is not pending TTS approval.'));
            }

            if (!$audioFile->translated_text) {
                return back()->with('error', __('No translated text found. Please save your translated text first.'));
            }

            // Update status to show TTS generation is starting
            $audioFile->update([
                'status' => 'generating_audio',
                'processing_stage' => 'generating_audio',
                'processing_progress' => 80,
                'processing_message' => __('Starting audio generation...')
            ]);

            // Dispatch job to generate TTS
            \App\Jobs\ProcessAudioTTSJob::dispatch($audioFile);

            return redirect()->route('audio.show', $audioFile->id)
                ->with('success', __('TTS generation started! Audio will be created soon...'));

        } catch (\Exception $e) {
            Log::error('Failed to approve TTS', [
                'audio_file_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', __('Failed to approve TTS: ') . $e->getMessage());
        }
    }

    /**
     * Retry processing for stuck jobs
     */
    public function retry($id)
    {
        try {
            // Use user's audioFiles relationship for automatic authorization
            $audioFile = auth()->user()->audioFiles()->findOrFail($id);
            
            // Only allow retry if status is 'uploaded' and it's been more than 2 minutes
            if ($audioFile->status !== 'uploaded') {
                return back()->with('error', __('This file is not in uploaded status. Cannot retry.'));
            }

            // Dispatch job again
            ProcessAudioJob::dispatch($audioFile);

            return redirect()->route('audio.show', $audioFile->id)
                ->with('success', __('Processing restarted! Please wait...'));

        } catch (\Exception $e) {
            Log::error('Failed to retry processing', [
                'audio_file_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', __('Failed to retry processing: ') . $e->getMessage());
        }
    }

    /**
     * Show form for selecting additional languages
     */
    public function showAdditionalTranslations($id)
    {
        // Eager load audioTranslations to prevent null issues
        $audioFile = auth()->user()->audioFiles()
            ->with('audioTranslations')
            ->findOrFail($id);

        // Only allow if the original translation is completed
        if (!$audioFile->isCompleted()) {
            return redirect()->route('audio.show', $audioFile->id)
                ->with('error', __('You can only add additional translations after the original translation is completed.'));
        }

        // Get available languages (exclude source and already translated languages)
        $availableLanguages = collect(config('audio.available_languages'))
            ->filter(function ($language, $code) use ($audioFile) {
                return $code !== $audioFile->source_language &&
                       !$audioFile->audioTranslations()->where('target_language', $code)->exists();
            });

        // Get existing audio translations - ensure it's always a collection
        $audioTranslations = $audioFile->audioTranslations ?? collect();

        return view('audio.additional-translations', compact('audioFile', 'availableLanguages', 'audioTranslations'));
    }

    /**
     * Store additional language translations
     */
    public function storeAdditionalTranslations(Request $request, $id)
    {
        $audioFile = auth()->user()->audioFiles()->findOrFail($id);

        if (!$audioFile->isCompleted()) {
            return redirect()->route('audio.show', $audioFile->id)
                ->with('error', __('You can only add additional translations after the original translation is completed.'));
        }

        $request->validate([
            'additional_languages' => 'required|string|in:' . implode(',', array_keys(config('audio.available_languages'))),
            'voice' => 'required|string',
            'style_instruction' => 'nullable|string|max:' . config('audio.max_style_instruction_length', 5000),
        ]);

        // Convert single language to array for processing
        $targetLanguage = $request->input('additional_languages');
        $additionalLanguages = [$targetLanguage];
        
        $voice = $request->input('voice');
        $styleInstruction = $request->input('style_instruction', $audioFile->style_instruction);

        // Check credits
        $totalCost = count($additionalLanguages) * config('stripe.default_cost_per_translation');
        if (!$audioFile->user->hasEnoughCredits($totalCost)) {
            return back()->with('error', __('You don\'t have enough credits. You need ') . $totalCost . __(' credits for this translation.'));
        }

        // Create audio translation records and dispatch jobs
        foreach ($additionalLanguages as $targetLanguage) {
            // Skip if already exists
            if ($audioFile->audioTranslations()->where('target_language', $targetLanguage)->exists()) {
                continue;
            }

            $audioTranslation = AudioTranslation::create([
                'audio_file_id' => $audioFile->id,
                'target_language' => $targetLanguage,
                'translated_text' => '', // Will be filled by the job
                'voice' => $voice,
                'style_instruction' => $styleInstruction,
                'status' => 'pending',
            ]);

            // Dispatch job for processing
            ProcessAdditionalAudioTranslation::dispatch($audioTranslation);
        }

        return redirect()->route('audio.show', $audioFile->id)
            ->with('success', __('Additional translations started! You will be notified when they are ready.'));
    }

    /**
     * Download specific audio translation
     */
    public function downloadTranslation($audioFileId, $translationId)
    {
        $audioFile = auth()->user()->audioFiles()->findOrFail($audioFileId);
        $audioTranslation = $audioFile->audioTranslations()->findOrFail($translationId);

        if (!$audioTranslation->isCompleted() || !$audioTranslation->translated_audio_path) {
            return back()->with('error', __('This translation is not ready for download yet.'));
        }

        $filePath = storage_path('app/public/' . $audioTranslation->translated_audio_path);

        if (!file_exists($filePath)) {
            return back()->with('error', __('Audio file not found.'));
        }

        $filename = pathinfo($audioFile->original_filename, PATHINFO_FILENAME) .
                   '_to_' . strtoupper($audioTranslation->target_language) .
                   '.' . pathinfo($audioTranslation->translated_audio_path, PATHINFO_EXTENSION);

        return response()->download($filePath, $filename);
    }
}

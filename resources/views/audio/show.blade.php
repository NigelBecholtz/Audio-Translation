@extends('layouts.app')

@section('title', 'Audio Translation Details')

@section('content')
<div class="px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 fade-in">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">{{ __('Audio Translation Details') }}</h1>
                <p class="text-white text-lg">{{ __('View the progress and results of your translation') }}</p>
            </div>
            <a href="{{ route('audio.index') }}" class="flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-colors font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>
                {{ __('Back to overview') }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Status Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <h3 class="text-xl font-bold text-white">{{ __('Status') }}</h3>
                        <span id="status-badge" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                            @if($audioFile->isCompleted()) bg-green-100 text-green-800
                            @elseif($audioFile->isFailed()) bg-red-100 text-red-800
                            @elseif($audioFile->isPendingApproval()) bg-yellow-100 text-yellow-800
                            @elseif($audioFile->isProcessing()) bg-yellow-100 text-yellow-800 pulse-animation
                            @else bg-gray-100 text-gray-800
                            @endif">
                            <i id="status-icon" class="mr-2 
                                @if($audioFile->isCompleted()) fas fa-check-circle
                                @elseif($audioFile->isFailed()) fas fa-exclamation-triangle
                                @elseif($audioFile->isPendingApproval()) fas fa-clock
                                @elseif($audioFile->isProcessing()) fas fa-spinner fa-spin
                                @else fas fa-upload
                                @endif"></i>
                            <span id="status-text">
                                @if($audioFile->isCompleted()) {{ __('Completed') }}
                                @elseif($audioFile->isFailed()) {{ __('Failed') }}
                                @elseif($audioFile->isPendingApproval()) {{ __('Awaiting Approval') }}
                                @elseif($audioFile->isProcessing()) {{ __('Processing...') }}
                                @else {{ __('Uploaded') }}
                                @endif
                            </span>
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    @if($audioFile->isProcessing() || !$audioFile->isCompleted())
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span id="progress-message" class="text-sm font-medium text-white">
                                {{ $audioFile->processing_message ?? __('Starting...') }}
                            </span>
                            <span id="progress-percentage" class="text-sm font-medium text-white">
                                {{ $audioFile->processing_progress ?? 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div id="progress-bar" class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500 ease-out" 
                                 style="width: {{ $audioFile->processing_progress ?? 0 }}%"></div>
                        </div>
                    </div>
                    @endif

                    <!-- Progress Steps -->
                    <div class="space-y-4">
                        <!-- Step 1: Upload -->
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-white text-lg">{{ __('Audio Uploaded') }}</h4>
                                <p class="text-sm text-gray-400">{{ $audioFile->created_at->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                        <!-- Step 2: Transcription -->
                        <div class="flex items-center gap-4">
                            @if($audioFile->transcription && !$audioFile->isPendingApproval())
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Audio Transcribed') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('Whisper AI has converted the audio to text') }}</p>
                                </div>
                            @elseif($audioFile->isPendingApproval())
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Awaiting Your Approval') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('Please review the transcription and approve to continue') }}</p>
                                </div>
                            @elseif($audioFile->status === 'transcribing')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Transcribing Audio...') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('Whisper AI is converting audio to text') }}</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Waiting for Transcription') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The audio will be transcribed soon') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Step 3: Translation -->
                        <div class="flex items-center gap-4">
                            @if($audioFile->translated_text)
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Text Translated') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The text has been translated to') }} {{ strtoupper($audioFile->target_language) }}</p>
                                </div>
                            @elseif($audioFile->status === 'translating')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Translating Text...') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The text is being translated to') }} {{ strtoupper($audioFile->target_language) }}</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Waiting for Translation') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The text will be translated soon') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Step 4: Audio Generation -->
                        <div class="flex items-center gap-4">
                            @if($audioFile->translated_audio_path)
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Audio Generated') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The translated audio file is ready') }} ({{ __('Voice:') }} {{ ucfirst($audioFile->voice) }})</p>
                                </div>
                            @elseif($audioFile->status === 'generating_audio')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Generating Audio...') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The translated audio file is being generated') }} ({{ __('Voice:') }} {{ ucfirst($audioFile->voice) }})</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">{{ __('Waiting for Audio Generation') }}</h4>
                                    <p class="text-sm text-gray-400">{{ __('The audio file will be generated soon') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Audio Preview (Original) -->
                @if($audioFile->file_path && $audioFile->status !== 'failed' && \Storage::disk('public')->exists($audioFile->file_path))
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-headphones mr-2 text-blue-400"></i>
                            {{ __('Original Audio Preview') }}
                        </h3>
                        <audio controls class="w-full rounded-lg" preload="metadata">
                            <source src="{{ asset('storage/' . $audioFile->file_path) }}" type="audio/mpeg">
                            {{ __('Your browser does not support the audio element.') }}
                        </audio>
                    </div>
                @endif

                <!-- Transcription -->
                @if($audioFile->transcription)
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-file-text mr-2 text-indigo-400"></i>
                                {{ __('Transcription') }} ({{ strtoupper($audioFile->source_language) }})
                            </h3>
                            @if($audioFile->isPendingApproval())
                                <div class="inline-flex items-center px-4 py-2 bg-yellow-500/20 border-2 border-yellow-500 rounded-xl">
                                    <i class="fas fa-clock mr-2 text-yellow-400"></i>
                                    <span class="text-yellow-300 font-semibold">{{ __('Awaiting Approval') }}</span>
                                </div>
                            @endif
                        </div>
                        
                        @if($audioFile->isPendingApproval())
                            <!-- Editable Transcription Form -->
                            <form method="POST" action="{{ route('audio.approve-transcription', $audioFile->id) }}" id="approveTranscriptionForm" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="edited_transcription" class="block text-sm font-semibold text-gray-300 mb-2">
                                        <i class="fas fa-edit mr-2 text-blue-400"></i>
                                        {{ __('Edit transcription if needed') }}
                                    </label>
                                    <textarea 
                                        id="edited_transcription" 
                                        name="transcription" 
                                        rows="8"
                                        class="w-full px-4 py-3 text-lg border-2 border-gray-600 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-400 focus:border-blue-500 transition-all bg-gray-700 text-white resize-none font-mono"
                                        placeholder="{{ __('Edit the transcription here...') }}">{{ old('transcription', $audioFile->transcription) }}</textarea>
                                    <p class="mt-2 text-xs text-gray-400 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        {{ __('You can edit the transcription before approving. The edited version will be used for translation and audio generation.') }}
                                    </p>
                                    @error('transcription')
                                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div class="bg-gradient-to-r from-blue-900/30 to-indigo-900/30 p-6 rounded-xl border-2 border-blue-500/50">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-white text-lg mb-2 flex items-center">
                                                <i class="fas fa-check-circle mr-2 text-blue-400"></i>
                                                {{ __('Review and Approve Transcription') }}
                                            </h4>
                                            <p class="text-gray-300 text-sm">
                                                {{ __('Review and edit the transcription above if needed. When ready, click the button below to continue with translation and audio generation.') }}
                                            </p>
                                        </div>
                                        <div class="flex gap-3 flex-shrink-0">
                                            <button 
                                                type="button" 
                                                onclick="resetTranscription()" 
                                                class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-all duration-200 shadow-lg hover:shadow-xl font-semibold">
                                                <i class="fas fa-undo mr-2"></i>
                                                {{ __('Reset') }}
                                            </button>
                                            <button 
                                                type="submit" 
                                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl font-bold text-lg">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                {{ __('Approve & Continue') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        @else
                            <!-- Read-only Transcription (when already approved or completed) -->
                            <div class="bg-gray-700/50 p-6 rounded-xl border border-gray-600/30">
                                <p class="text-white leading-relaxed text-lg whitespace-pre-wrap">{{ $audioFile->transcription }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Translated Text -->
                @if($audioFile->translated_text)
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-language mr-2 text-green-400"></i>
                            {{ __('Translated Text') }} ({{ strtoupper($audioFile->target_language) }})
                        </h3>
                        <div class="bg-gradient-to-r from-green-900/30 to-blue-900/30 p-6 rounded-xl border border-gray-600/30">
                            <p class="text-white leading-relaxed text-lg">{{ $audioFile->translated_text }}</p>
                        </div>
                    </div>
                @endif

                <!-- Translated Audio Preview -->
                @if($audioFile->translated_audio_path && $audioFile->isCompleted() && \Storage::disk('public')->exists($audioFile->translated_audio_path))
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-volume-up mr-2 text-green-400"></i>
                            {{ __('Translated Audio Preview') }}
                        </h3>
                        <audio controls class="w-full rounded-lg" preload="metadata">
                            <source src="{{ asset('storage/' . $audioFile->translated_audio_path) }}" type="audio/mpeg">
                            {{ __('Your browser does not support the audio element.') }}
                        </audio>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- File Info Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-indigo-400"></i>
                        {{ __('File Information') }}
                    </h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Filename') }}</dt>
                            <dd class="text-sm text-white font-medium">{{ $audioFile->original_filename }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Size') }}</dt>
                            <dd class="text-sm text-white">{{ number_format($audioFile->file_size / 1024, 2) }} KB</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Source Language') }}</dt>
                            <dd class="text-sm text-white">{{ strtoupper($audioFile->source_language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Target Language') }}</dt>
                            <dd class="text-sm text-white">{{ strtoupper($audioFile->target_language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Voice') }}</dt>
                            <dd class="text-sm text-white capitalize">{{ $audioFile->voice }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-400">{{ __('Uploaded on') }}</dt>
                            <dd class="text-sm text-white">{{ $audioFile->created_at->format('d-m-Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-download mr-2 text-green-400"></i>
                        {{ __('Actions') }}
                    </h3>
                    <div class="space-y-3">
                        @if($audioFile->isCompleted())
                            <a href="{{ route('audio.download', $audioFile->id) }}" 
                               class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium">
                                <i class="fas fa-download mr-2"></i>
                                {{ __('Download Translated Audio') }}
                            </a>
                        @endif
                        
                        @if($audioFile->isFailed())
                            <div class="p-4 bg-red-900/30 border border-red-600/30 rounded-xl">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-red-400 mt-1 mr-3 flex-shrink-0"></i>
                                    <div>
                                        <h4 class="font-semibold text-red-200">{{ __('Processing Failed') }}</h4>
                                        <p class="text-sm text-red-300 mt-1">{{ $audioFile->error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if($audioFile->status === 'uploaded' && $audioFile->created_at->diffInMinutes(now()) > 2)
                            <div class="p-4 bg-yellow-900/30 border border-yellow-600/30 rounded-xl mb-3">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-400 mt-1 mr-3 flex-shrink-0"></i>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-yellow-200">{{ __('Processing seems stuck') }}</h4>
                                        <p class="text-sm text-yellow-300 mt-1 mb-3">{{ __('The job may not have started. Try retrying the processing.') }}</p>
                                        <form method="POST" action="{{ route('audio.retry', $audioFile->id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-all font-semibold text-sm">
                                                <i class="fas fa-redo mr-2"></i>
                                                {{ __('Retry Processing') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this translation?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium cursor-pointer">
                                <i class="fas fa-trash mr-2"></i>
                                {{ __('Delete Translation') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time progress tracking
@if($audioFile->isProcessing() || $audioFile->status === 'uploaded' || (!$audioFile->isCompleted() && !$audioFile->isFailed() && !$audioFile->isPendingApproval()))
let pollInterval;
const audioFileId = {{ $audioFile->id }};

function updateProgress(data) {
    // Update progress bar
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    const progressMessage = document.getElementById('progress-message');
    
    if (progressBar && data.processing_progress !== null) {
        progressBar.style.width = data.processing_progress + '%';
    }
    
    if (progressPercentage && data.processing_progress !== null) {
        progressPercentage.textContent = data.processing_progress + '%';
    }
    
    if (progressMessage && data.processing_message) {
        progressMessage.textContent = data.processing_message;
    }
    
    // Update status badge
    const statusBadge = document.getElementById('status-badge');
    const statusIcon = document.getElementById('status-icon');
    const statusText = document.getElementById('status-text');
    
    if (data.is_pending_approval) {
        // Reload page to show approval button
        clearInterval(pollInterval);
        setTimeout(() => window.location.reload(), 1000);
    } else if (data.is_completed) {
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-check-circle mr-2';
        }
        if (statusText) {
            statusText.textContent = '{{ __('Completed') }}';
        }
        
        clearInterval(pollInterval);
        setTimeout(() => window.location.reload(), 1000);
    } else if (data.is_failed) {
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-exclamation-triangle mr-2';
        }
        if (statusText) {
            statusText.textContent = '{{ __('Failed') }}';
        }
        
        clearInterval(pollInterval);
        if (data.error_message) {
            alert('{{ __('Processing failed:') }} ' + data.error_message);
        }
        setTimeout(() => window.location.reload(), 2000);
    }
}

function pollStatus() {
    fetch(`/audio/${audioFileId}/status`)
        .then(response => response.json())
        .then(data => updateProgress(data))
        .catch(error => console.error('Error polling status:', error));
}

// Start polling every 3 seconds (reduced frequency for multiple concurrent users)
pollInterval = setInterval(pollStatus, 3000);
pollStatus(); // Initial poll

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (pollInterval) clearInterval(pollInterval);
});
@endif

// Reset transcription to original
function resetTranscription() {
    const originalTranscription = @json($audioFile->transcription ?? '');
    const textarea = document.getElementById('edited_transcription');
    if (textarea) {
        textarea.value = originalTranscription;
    }
}
</script>

@endsection
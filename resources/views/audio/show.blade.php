@extends('layouts.app')

@section('title', 'Audio Translation Details')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 fade-in">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Audio Translation Details</h1>
                <p class="text-white text-lg">View the progress and results of your translation</p>
            </div>
            <a href="{{ route('audio.index') }}" class="flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to overview
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Status Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white">Status</h3>
                        <span id="status-badge" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                            @if($audioFile->isCompleted()) bg-green-100 text-green-800
                            @elseif($audioFile->isFailed()) bg-red-100 text-red-800
                            @elseif($audioFile->isProcessing()) bg-yellow-100 text-yellow-800 pulse-animation
                            @else bg-gray-100 text-gray-800
                            @endif">
                            <i id="status-icon" class="mr-2 
                                @if($audioFile->isCompleted()) fas fa-check-circle
                                @elseif($audioFile->isFailed()) fas fa-exclamation-triangle
                                @elseif($audioFile->isProcessing()) fas fa-spinner fa-spin
                                @else fas fa-upload
                                @endif"></i>
                            <span id="status-text">
                                @if($audioFile->isCompleted()) Completed
                                @elseif($audioFile->isFailed()) Failed
                                @elseif($audioFile->isProcessing()) Processing...
                                @else Uploaded
                                @endif
                            </span>
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    @if($audioFile->isProcessing() || !$audioFile->isCompleted())
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span id="progress-message" class="text-sm font-medium text-white">
                                {{ $audioFile->processing_message ?? 'Starting...' }}
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
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-white text-lg">Audio Uploaded</h4>
                                <p class="text-sm text-white">{{ $audioFile->created_at->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                        <!-- Step 2: Transcription -->
                        <div class="flex items-center space-x-4">
                            @if($audioFile->transcription)
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Audio Transcribed</h4>
                                    <p class="text-sm text-white">Whisper AI has converted the audio to text</p>
                                </div>
                            @elseif($audioFile->status === 'transcribing')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Transcribing Audio...</h4>
                                    <p class="text-sm text-white">Whisper AI is converting audio to text</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Waiting for Transcription</h4>
                                    <p class="text-sm text-white">The audio will be transcribed soon</p>
                                </div>
                            @endif
                        </div>

                        <!-- Step 3: Translation -->
                        <div class="flex items-center space-x-4">
                            @if($audioFile->translated_text)
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Text Translated</h4>
                                    <p class="text-sm text-white">The text has been translated to {{ strtoupper($audioFile->target_language) }}</p>
                                </div>
                            @elseif($audioFile->status === 'translating')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Translating Text...</h4>
                                    <p class="text-sm text-white">The text is being translated to {{ strtoupper($audioFile->target_language) }}</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Waiting for Translation</h4>
                                    <p class="text-sm text-white">The text will be translated soon</p>
                                </div>
                            @endif
                        </div>

                        <!-- Step 4: Audio Generation -->
                        <div class="flex items-center space-x-4">
                            @if($audioFile->translated_audio_path)
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Audio Generated</h4>
                                    <p class="text-sm text-white">The translated audio file is ready (Voice: {{ ucfirst($audioFile->voice) }})</p>
                                </div>
                            @elseif($audioFile->status === 'generating_audio')
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Generating Audio...</h4>
                                    <p class="text-sm text-white">The translated audio file is being generated (Voice: {{ ucfirst($audioFile->voice) }})</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Waiting for Audio Generation</h4>
                                    <p class="text-sm text-white">The audio file will be generated soon</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Audio Preview (Original) -->
                @if($audioFile->file_path && $audioFile->status !== 'failed')
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-headphones mr-2 text-blue-400"></i>
                            Original Audio Preview
                        </h3>
                        <audio controls class="w-full rounded-lg" preload="metadata">
                            <source src="{{ asset('storage/' . $audioFile->file_path) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                @endif

                <!-- Transcription -->
                @if($audioFile->transcription)
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-file-text mr-2 text-indigo-400"></i>
                            Transcription ({{ strtoupper($audioFile->source_language) }})
                        </h3>
                        <div class="bg-gray-700/50 p-6 rounded-xl border border-gray-600/30">
                            <p class="text-white leading-relaxed text-lg">{{ $audioFile->transcription }}</p>
                        </div>
                    </div>
                @endif

                <!-- Translated Text -->
                @if($audioFile->translated_text)
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-language mr-2 text-green-400"></i>
                            Translated Text ({{ strtoupper($audioFile->target_language) }})
                        </h3>
                        <div class="bg-gradient-to-r from-green-900/30 to-blue-900/30 p-6 rounded-xl border border-gray-600/30">
                            <p class="text-white leading-relaxed text-lg">{{ $audioFile->translated_text }}</p>
                        </div>
                    </div>
                @endif

                <!-- Translated Audio Preview -->
                @if($audioFile->translated_audio_path && $audioFile->isCompleted())
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-volume-up mr-2 text-green-400"></i>
                            Translated Audio Preview
                        </h3>
                        <audio controls class="w-full rounded-lg" preload="metadata">
                            <source src="{{ asset('storage/' . $audioFile->translated_audio_path) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
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
                        File Information
                    </h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-white">Filename</dt>
                            <dd class="text-sm text-white font-medium">{{ $audioFile->original_filename }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Size</dt>
                            <dd class="text-sm text-white">{{ number_format($audioFile->file_size / 1024, 2) }} KB</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Source Language</dt>
                            <dd class="text-sm text-white">{{ strtoupper($audioFile->source_language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Target Language</dt>
                            <dd class="text-sm text-white">{{ strtoupper($audioFile->target_language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Voice</dt>
                            <dd class="text-sm text-white capitalize">{{ $audioFile->voice }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Uploaded on</dt>
                            <dd class="text-sm text-white">{{ $audioFile->created_at->format('d-m-Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-download mr-2 text-green-400"></i>
                        Actions
                    </h3>
                    <div class="space-y-3">
                        @if($audioFile->isCompleted())
                            <a href="{{ route('audio.download', $audioFile->id) }}" 
                               class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium">
                                <i class="fas fa-download mr-2"></i>
                                Download Translated Audio
                            </a>
                        @endif
                        
                        @if($audioFile->isFailed())
                            <div class="p-4 bg-red-900/30 border border-red-600/30 rounded-xl">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-red-400 mt-1 mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-red-200">Processing Failed</h4>
                                        <p class="text-sm text-red-300 mt-1">{{ $audioFile->error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" onsubmit="return confirm('Are you sure you want to delete this translation?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium cursor-pointer">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Translation
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
@if($audioFile->isProcessing() || (!$audioFile->isCompleted() && !$audioFile->isFailed()))
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
    
    if (data.is_completed) {
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-check-circle mr-2';
        }
        if (statusText) {
            statusText.textContent = 'Completed';
        }
        
        // Stop polling and reload page to show results
        clearInterval(pollInterval);
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } else if (data.is_failed) {
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-exclamation-triangle mr-2';
        }
        if (statusText) {
            statusText.textContent = 'Failed';
        }
        
        // Stop polling and show error
        clearInterval(pollInterval);
        if (data.error_message) {
            alert('Processing failed: ' + data.error_message);
        }
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
}

function pollStatus() {
    fetch(`/audio/${audioFileId}/status`)
        .then(response => response.json())
        .then(data => {
            console.log('Status update:', data);
            updateProgress(data);
        })
        .catch(error => {
            console.error('Error polling status:', error);
        });
}

// Start polling every 2 seconds
pollInterval = setInterval(pollStatus, 2000);

// Initial poll
pollStatus();

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
@endif
</script>

@endsection


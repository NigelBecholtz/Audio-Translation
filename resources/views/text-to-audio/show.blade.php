@extends('layouts.app')

@section('title', 'Text to Audio Details')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 fade-in">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Text to Audio Details</h1>
                <p class="text-white text-lg">View the progress and results of your text to audio conversion</p>
            </div>
            <a href="{{ route('text-to-audio.index') }}" class="flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-colors">
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
                        <span id="statusBadge" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                            @if($textToAudioFile->isCompleted()) bg-green-100 text-green-800
                            @elseif($textToAudioFile->isFailed()) bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800 pulse-animation
                            @endif">
                            <i id="statusIcon" class="mr-2 
                                @if($textToAudioFile->isCompleted()) fas fa-check-circle
                                @elseif($textToAudioFile->isFailed()) fas fa-exclamation-triangle
                                @else fas fa-spinner fa-spin
                                @endif"></i>
                            <span id="statusText">
                                @if($textToAudioFile->isCompleted()) Completed
                                @elseif($textToAudioFile->isFailed()) Failed
                                @else Processing...
                                @endif
                            </span>
                        </span>
                    </div>
                    
                    <!-- Live Polling Indicator -->
                    @if($textToAudioFile->isProcessing())
                    <div id="pollingIndicator" class="mb-6 p-4 bg-gradient-to-r from-blue-500/20 to-purple-500/20 border-2 border-blue-400/50 rounded-xl backdrop-blur-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                                    <div class="absolute inset-0 w-3 h-3 bg-blue-500 rounded-full animate-ping opacity-75"></div>
                                </div>
                                <p class="text-sm text-white font-bold">
                                    <i class="fas fa-sync-alt fa-spin mr-2 text-blue-400"></i>
                                    Live Status Updates Active
                                </p>
                            </div>
                            <span id="lastCheck" class="text-xs text-blue-200 font-mono bg-blue-900/30 px-2 py-1 rounded"></span>
                        </div>
                        <p class="text-xs text-blue-300 mt-2 ml-6">
                            Automatically checking every 3 seconds - page will refresh when complete
                        </p>
                    </div>
                    @endif

                    <!-- Progress Steps -->
                    <div class="space-y-4">
                        <!-- Step 1: Text Submitted -->
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-white text-lg">Text Submitted</h4>
                                <p class="text-sm text-white">{{ $textToAudioFile->created_at->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                        <!-- Step 2: Audio Generation -->
                        <div class="flex items-center space-x-4">
                            @if($textToAudioFile->isCompleted())
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Audio Generated</h4>
                                    <p class="text-sm text-white">The audio file is ready (Voice: {{ ucfirst($textToAudioFile->voice) }})</p>
                                </div>
                            @elseif($textToAudioFile->isFailed())
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-times text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Generation Failed</h4>
                                    <p class="text-sm text-white">{{ $textToAudioFile->error_message }}</p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-white text-lg">Generating Audio...</h4>
                                    <p class="text-sm text-white">OpenAI TTS is converting your text to audio (Voice: {{ ucfirst($textToAudioFile->voice) }})</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Original Text -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-file-text mr-2 text-indigo-400"></i>
                        Original Text ({{ strtoupper($textToAudioFile->language) }})
                    </h3>
                    <div class="bg-gray-700/50 p-6 rounded-xl border border-gray-600/30">
                        <p class="text-white leading-relaxed text-lg">{{ $textToAudioFile->text_content }}</p>
                    </div>
                </div>

                <!-- Audio Preview -->
                @if($textToAudioFile->audio_path && $textToAudioFile->isCompleted() && \Storage::disk('public')->exists($textToAudioFile->audio_path))
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-volume-up mr-2 text-green-400"></i>
                            Gegenereerde Audio Preview
                        </h3>
                        <audio controls class="w-full rounded-lg" preload="metadata">
                            <source src="{{ asset('storage/' . $textToAudioFile->audio_path) }}" type="audio/mpeg">
                            Je browser ondersteunt geen audio element.
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
                        Conversion Information
                    </h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-white">Language</dt>
                            <dd class="text-sm text-white">{{ strtoupper($textToAudioFile->language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Voice</dt>
                            <dd class="text-sm text-white capitalize">{{ $textToAudioFile->voice }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Text Length</dt>
                            <dd class="text-sm text-white">{{ strlen($textToAudioFile->text_content) }} characters</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-white">Created on</dt>
                            <dd class="text-sm text-white">{{ $textToAudioFile->created_at->format('d-m-Y H:i') }}</dd>
                        </div>
                        @if($textToAudioFile->isCompleted())
                            <div>
                                <dt class="text-sm font-medium text-white">Completed on</dt>
                                <dd class="text-sm text-white">{{ $textToAudioFile->updated_at->format('d-m-Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Actions Card -->
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-download mr-2 text-green-400"></i>
                        Actions
                    </h3>
                    <div class="space-y-3">
                        @if($textToAudioFile->isCompleted())
                            <a href="{{ route('text-to-audio.download', $textToAudioFile->id) }}" 
                               class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium">
                                <i class="fas fa-download mr-2"></i>
                                Download Audio File
                            </a>
                        @endif
                        
                        <form method="POST" action="{{ route('text-to-audio.destroy', $textToAudioFile->id) }}" 
                              onsubmit="return confirm('Are you sure you want to delete this text to audio conversion?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl hover-lift font-medium">
                                <i class="fas fa-trash mr-2"></i>
                                Delete Conversion
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
@if($textToAudioFile->isProcessing())
let pollInterval;
let pollCount = 0;
const textToAudioId = {{ $textToAudioFile->id }};

function updateStatus(data) {
    console.log('Status update:', data);
    
    // Update last check time
    const lastCheckEl = document.getElementById('lastCheck');
    if (lastCheckEl) {
        const now = new Date();
        lastCheckEl.textContent = `(${now.toLocaleTimeString()})`;
    }
    
    // Update status badge
    const statusBadge = document.getElementById('statusBadge');
    const statusIcon = document.getElementById('statusIcon');
    const statusText = document.getElementById('statusText');
    
    if (data.is_completed) {
        console.log('✅ Conversion completed!');
        
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-check-circle mr-2';
        }
        if (statusText) {
            statusText.textContent = 'Completed';
        }
        
        // Hide polling indicator
        const pollingIndicator = document.getElementById('pollingIndicator');
        if (pollingIndicator) {
            pollingIndicator.style.display = 'none';
        }
        
        clearInterval(pollInterval);
        setTimeout(() => window.location.reload(), 1000);
    } else if (data.is_failed) {
        console.log('❌ Conversion failed:', data.error_message);
        
        if (statusBadge) {
            statusBadge.className = 'inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800';
        }
        if (statusIcon) {
            statusIcon.className = 'fas fa-exclamation-triangle mr-2';
        }
        if (statusText) {
            statusText.textContent = 'Failed';
        }
        
        clearInterval(pollInterval);
        setTimeout(() => window.location.reload(), 2000);
    } else {
        console.log('⏳ Still processing... (check #' + (++pollCount) + ')');
    }
}

function pollStatus() {
    fetch(`/text-to-audio/${textToAudioId}/status`)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => updateStatus(data))
        .catch(error => {
            console.error('Error polling status:', error);
            const lastCheckEl = document.getElementById('lastCheck');
            if (lastCheckEl) {
                lastCheckEl.textContent = '(error - retrying...)';
            }
        });
}

// Start polling every 3 seconds (reduced frequency for multiple concurrent users)
console.log('🔄 Starting status polling for text-to-audio #' + textToAudioId);
pollInterval = setInterval(pollStatus, 3000);
pollStatus(); // Initial poll

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (pollInterval) {
        console.log('🛑 Stopping polling');
        clearInterval(pollInterval);
    }
});
@endif
</script>

@endsection

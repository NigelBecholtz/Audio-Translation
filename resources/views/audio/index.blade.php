@extends('layouts.app')

@section('title', 'Audio Translations')

@section('content')
<div style="padding: 32px 24px; max-width: 1200px; margin: 0 auto;">
    <!-- Header Section -->
    <div style="text-align: center; margin-bottom: 48px;">
        <h1 style="font-size: 48px; font-weight: bold; color: #f9fafb; margin-bottom: 16px;">Audio Translations</h1>
        <p style="font-size: 20px; color: #d1d5db; margin-bottom: 32px;">Transform your audio to any desired language with AI</p>
        <a href="{{ route('audio.create') }}" class="btn-primary" style="font-size: 20px; padding: 20px 40px;">
            <i class="fas fa-plus"></i>
            Start New Translation
        </a>
    </div>

    @if($audioFiles->count() > 0)
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
            <div class="card" style="border: 3px solid #4b5563;">
                <div style="display: flex; align-items: center;">
                    <div style="padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #374151 0%, #4b5563 100%); margin-right: 16px;">
                        <i class="fas fa-file-audio" style="color: #d1d5db; font-size: 24px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 0;">Total</p>
                        <p style="font-size: 32px; font-weight: bold; color: #f9fafb; margin: 0;">{{ $audioFiles->total() }}</p>
                    </div>
                </div>
            </div>
            <div class="card" style="border: 3px solid #6b7280;">
                <div style="display: flex; align-items: center;">
                    <div style="padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%); margin-right: 16px;">
                        <i class="fas fa-gift" style="color: #d1d5db; font-size: 24px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 0;">Left</p>
                        <p style="font-size: 32px; font-weight: bold; color: #f9fafb; margin: 0;">{{ $user->getRemainingTranslations() }}</p>
                    </div>
                </div>
            </div>
            <div class="card" style="border: 3px solid #10b981;">
                <div style="display: flex; align-items: center;">
                    <div style="padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #065f46 0%, #047857 100%); margin-right: 16px;">
                        <i class="fas fa-check-circle" style="color: #10b981; font-size: 24px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 0;">Completed</p>
                        <p style="font-size: 32px; font-weight: bold; color: #10b981; margin: 0;">{{ $audioFiles->where('status', 'completed')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="card" style="border: 3px solid #f59e0b;">
                <div style="display: flex; align-items: center;">
                    <div style="padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #92400e 0%, #b45309 100%); margin-right: 16px;">
                        <i class="fas fa-clock" style="color: #f59e0b; font-size: 24px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 0;">Processing</p>
                        <p style="font-size: 32px; font-weight: bold; color: #f59e0b; margin: 0;">{{ $audioFiles->whereIn('status', ['transcribing', 'translating', 'generating_audio'])->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="card" style="border: 3px solid #ef4444;">
                <div style="display: flex; align-items: center;">
                    <div style="padding: 16px; border-radius: 50%; background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%); margin-right: 16px;">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 24px;"></i>
                    </div>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: #9ca3af; margin: 0;">Failed</p>
                        <p style="font-size: 32px; font-weight: bold; color: #ef4444; margin: 0;">{{ $audioFiles->where('status', 'failed')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio Files Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            @foreach($audioFiles as $audioFile)
                <div class="bg-gray-800 rounded-2xl shadow-xl border-2 border-gray-600 hover:border-gray-500 hover:shadow-2xl transition-all duration-200 hover-lift fade-in">
                    <div class="p-6">
                        <!-- Status Badge -->
                        <div class="flex justify-between items-start mb-4">
                            @if($audioFile->isCompleted())
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-600 text-white border-2 border-green-500">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Completed
                                </span>
                            @elseif($audioFile->isFailed())
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-600 text-white border-2 border-red-500">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Failed
                                </span>
                            @elseif($audioFile->isProcessing())
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-yellow-600 text-white border-2 border-yellow-500 pulse-animation">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Processing...
                                </span>
                            @else
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-blue-600 text-white border-2 border-blue-500">
                                    <i class="fas fa-upload mr-2"></i>
                                    Uploaded
                                </span>
                            @endif
                            <span class="text-xs text-white font-medium">{{ $audioFile->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- File Info -->
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-white mb-2 truncate">
                                {{ $audioFile->original_filename }}
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-white">
                                <div class="flex items-center">
                                    <i class="fas fa-language mr-1"></i>
                                    {{ strtoupper($audioFile->source_language) }}
                                </div>
                                <i class="fas fa-arrow-right text-white"></i>
                                <div class="flex items-center">
                                    <i class="fas fa-language mr-1"></i>
                                    {{ strtoupper($audioFile->target_language) }}
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar for Processing -->
                        @if($audioFile->isProcessing())
                            <div class="mb-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full animate-pulse" style="width: 60%"></div>
                                </div>
                                <p class="text-xs text-white mt-1">Processing in progress...</p>
                            </div>
                        @endif

                        <!-- Transcription Preview -->
                        @if($audioFile->transcription)
                            <div class="mb-4">
                                <p class="text-sm text-white line-clamp-2">
                                    {{ Str::limit($audioFile->transcription, 120) }}
                                </p>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <a href="{{ route('audio.show', $audioFile->id) }}" class="flex-1 bg-gray-600 text-gray-100 px-4 py-3 rounded-xl hover:bg-gray-500 transition-colors text-center text-sm font-bold border-2 border-gray-500 hover:border-gray-400">
                                <i class="fas fa-eye mr-2"></i>
                                View
                            </a>
                            @if($audioFile->isCompleted())
                                <a href="{{ route('audio.download', $audioFile->id) }}" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl">
                                    <i class="fas fa-download mr-2"></i>
                                    Download
                                </a>
                            @endif
                            <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this translation?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 text-red-100 px-4 py-3 rounded-xl hover:bg-red-500 transition-colors text-center text-sm font-bold border-2 border-red-500 hover:border-red-400 cursor-pointer">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $audioFiles->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div style="text-align: center; padding: 64px 0;">
            <div style="width: 128px; height: 128px; background: linear-gradient(135deg, #374151 0%, #4b5563 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 4px solid #6b7280;">
                <i class="fas fa-microphone" style="font-size: 48px; color: #d1d5db;"></i>
            </div>
            <h3 style="font-size: 32px; font-weight: bold; color: #f9fafb; margin-bottom: 16px;">No audio files yet</h3>
            <p style="color: #d1d5db; margin-bottom: 32px; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 18px;">
                Upload your first audio file (MP3, WAV, M4A, max 50MB) and experience the power of AI-driven translations
            </p>
            <a href="{{ route('audio.create') }}" class="btn-primary" style="font-size: 20px; padding: 20px 40px;">
                <i class="fas fa-upload"></i>
                Upload your first audio file
            </a>
        </div>
    @endif
</div>

@endsection


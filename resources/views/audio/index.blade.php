@extends('layouts.app')

@section('title', 'Audio Translations')

@section('content')
<div class="px-4 sm:px-6 py-8 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="text-center mb-12 fade-in">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-50 mb-4">
            {{ __('Audio Translations') }}
        </h1>
        <p class="text-lg md:text-xl text-gray-300 mb-8">
            {{ __('Transform your audio to any desired language with AI') }}
        </p>
        <a href="{{ route('audio.create') }}" class="btn-primary inline-flex items-center gap-2 text-lg px-10 py-4">
            <i class="fas fa-plus"></i>
            {{ __('Start New Translation') }}
        </a>
    </div>

    @if($audioFiles->count() > 0)
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total -->
            <div class="card border-2 border-gray-600">
                <div class="flex items-center">
                    <div class="p-4 rounded-full bg-gradient-to-br from-gray-700 to-gray-600 mr-4">
                        <i class="fas fa-file-audio text-gray-300 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400">{{ __('Total') }}</p>
                        <p class="text-3xl font-bold text-gray-50">{{ $audioFiles->total() }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Left -->
            <div class="card border-2 border-gray-500">
                <div class="flex items-center">
                    <div class="p-4 rounded-full bg-gradient-to-br from-gray-600 to-gray-500 mr-4">
                        <i class="fas fa-gift text-gray-300 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400">{{ __('Left') }}</p>
                        <p class="text-3xl font-bold text-gray-50">{{ $user->getRemainingTranslations() }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Completed -->
            <div class="card border-2 border-green-600">
                <div class="flex items-center">
                    <div class="p-4 rounded-full bg-gradient-to-br from-green-700 to-green-600 mr-4">
                        <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400">{{ __('Completed') }}</p>
                        <p class="text-3xl font-bold text-green-500">{{ $audioFiles->where('status', 'completed')->count() }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Processing -->
            <div class="card border-2 border-yellow-600">
                <div class="flex items-center">
                    <div class="p-4 rounded-full bg-gradient-to-br from-yellow-700 to-yellow-600 mr-4">
                        <i class="fas fa-clock text-yellow-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400">{{ __('Processing') }}</p>
                        <p class="text-3xl font-bold text-yellow-500">{{ $audioFiles->whereIn('status', ['transcribing', 'translating', 'generating_audio'])->count() }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Failed -->
            <div class="card border-2 border-red-600">
                <div class="flex items-center">
                    <div class="p-4 rounded-full bg-gradient-to-br from-red-700 to-red-600 mr-4">
                        <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-400">{{ __('Failed') }}</p>
                        <p class="text-3xl font-bold text-red-500">{{ $audioFiles->where('status', 'failed')->count() }}</p>
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
                                    {{ __('Completed') }}
                                </span>
                            @elseif($audioFile->isFailed())
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-600 text-white border-2 border-red-500">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    {{ __('Failed') }}
                                </span>
                            @elseif($audioFile->isProcessing())
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-yellow-600 text-white border-2 border-yellow-500 pulse-animation">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    {{ __('Processing...') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-blue-600 text-white border-2 border-blue-500">
                                    <i class="fas fa-upload mr-2"></i>
                                    {{ __('Uploaded') }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-400 font-medium">{{ $audioFile->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- File Info -->
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-50 mb-2 truncate">
                                {{ $audioFile->original_filename }}
                            </h3>
                            <div class="flex items-center space-x-4 text-sm text-gray-300">
                                <div class="flex items-center">
                                    <i class="fas fa-language mr-1"></i>
                                    {{ strtoupper($audioFile->source_language) }}
                                </div>
                                <i class="fas fa-arrow-right text-gray-500"></i>
                                <div class="flex items-center">
                                    <i class="fas fa-language mr-1"></i>
                                    {{ strtoupper($audioFile->target_language) }}
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar for Processing -->
                        @if($audioFile->isProcessing())
                            <div class="mb-4">
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full animate-pulse w-3/5"></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">{{ __('Processing in progress...') }}</p>
                            </div>
                        @endif

                        <!-- Transcription Preview -->
                        @if($audioFile->transcription)
                            <div class="mb-4">
                                <p class="text-sm text-gray-300 line-clamp-2">
                                    {{ Str::limit($audioFile->transcription, 120) }}
                                </p>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('audio.show', $audioFile->id) }}" class="flex-1 bg-gray-600 text-gray-100 px-4 py-3 rounded-xl hover:bg-gray-500 transition-colors text-center text-sm font-bold border-2 border-gray-500 hover:border-gray-400">
                                <i class="fas fa-eye mr-2"></i>
                                {{ __('View') }}
                            </a>
                            @if($audioFile->isCompleted())
                                <a href="{{ route('audio.download', $audioFile->id) }}" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl">
                                    <i class="fas fa-download mr-2"></i>
                                    {{ __('Download') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this translation?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 text-red-100 px-4 py-3 rounded-xl hover:bg-red-500 transition-colors text-center text-sm font-bold border-2 border-red-500 hover:border-red-400 cursor-pointer">
                                    <i class="fas fa-trash"></i>
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
        <div class="text-center py-16 fade-in">
            <div class="w-32 h-32 bg-gradient-to-br from-gray-700 to-gray-600 rounded-full flex items-center justify-center mx-auto mb-8 border-4 border-gray-500">
                <i class="fas fa-microphone text-5xl text-gray-300"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-50 mb-4">{{ __('No audio files yet') }}</h3>
            <p class="text-gray-300 mb-8 max-w-md mx-auto text-lg">
                {{ __('Upload your first audio file') }} (MP3, WAV, M4A, MP4, max {{ config('audio.max_upload_size', 100) }}MB) {{ __('and experience the power of AI-driven translations') }}
            </p>
            <a href="{{ route('audio.create') }}" class="btn-primary inline-flex items-center gap-2 text-lg px-10 py-4">
                <i class="fas fa-upload"></i>
                {{ __('Upload your first audio file') }}
            </a>
        </div>
    @endif

    <!-- Text-to-Audio Section -->
    <div class="mt-16">
        <div class="text-center mb-8">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-50 mb-4">{{ __('Text to Audio') }}</h2>
            <p class="text-lg text-gray-300 mb-6">{{ __('Convert your text to speech with AI voices') }}</p>
            <a href="{{ route('text-to-audio.create') }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3">
                <i class="fas fa-plus"></i>
                {{ __('Create New Text to Audio') }}
            </a>
        </div>

        @if($textToAudioFiles->count() > 0)
            <!-- Text-to-Audio Files Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($textToAudioFiles as $textToAudio)
                    <div class="bg-gray-800 rounded-2xl shadow-xl border-2 border-gray-600 hover:border-gray-500 hover:shadow-2xl transition-all duration-200 hover-lift fade-in">
                        <div class="p-6">
                            <!-- Status Badge -->
                            <div class="flex justify-between items-start mb-4">
                                @if($textToAudio->isCompleted())
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-600 text-white border-2 border-green-500">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        {{ __('Completed') }}
                                    </span>
                                @elseif($textToAudio->isFailed())
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-600 text-white border-2 border-red-500">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        {{ __('Failed') }}
                                    </span>
                                @elseif($textToAudio->isProcessing())
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-yellow-600 text-white border-2 border-yellow-500 pulse-animation">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                        {{ __('Processing...') }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400 font-medium">{{ $textToAudio->created_at->diffForHumans() }}</span>
                            </div>

                            <!-- Text Content Preview -->
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-50 mb-2">
                                    {{ __('Text to Audio') }}
                                </h3>
                                <div class="flex items-center gap-4 text-sm text-gray-300 mb-2">
                                    <div class="flex items-center">
                                        <i class="fas fa-language mr-1"></i>
                                        {{ strtoupper($textToAudio->language) }}
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-microphone mr-1"></i>
                                        {{ ucfirst($textToAudio->voice) }}
                                    </div>
                                </div>
                                <p class="text-sm text-gray-300 line-clamp-3">
                                    {{ Str::limit($textToAudio->text_content, 150) }}
                                </p>
                            </div>

                            <!-- Progress Bar for Processing -->
                            @if($textToAudio->isProcessing())
                                <div class="mb-4">
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full animate-pulse w-3/5"></div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Generating audio...') }}</p>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex gap-2">
                                <a href="{{ route('text-to-audio.show', $textToAudio->id) }}" class="flex-1 bg-gray-600 text-gray-100 px-4 py-3 rounded-xl hover:bg-gray-500 transition-colors text-center text-sm font-bold border-2 border-gray-500 hover:border-gray-400">
                                    <i class="fas fa-eye mr-2"></i>
                                    {{ __('View') }}
                                </a>
                                @if($textToAudio->isCompleted())
                                    <a href="{{ route('text-to-audio.download', $textToAudio->id) }}" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all text-center text-sm font-bold shadow-lg hover:shadow-xl">
                                        <i class="fas fa-download mr-2"></i>
                                        {{ __('Download') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('text-to-audio.destroy', $textToAudio->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this text-to-audio conversion?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-red-100 px-4 py-3 rounded-xl hover:bg-red-500 transition-colors text-center text-sm font-bold border-2 border-red-500 hover:border-red-400 cursor-pointer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination for Text-to-Audio -->
            <div class="flex justify-center">
                {{ $textToAudioFiles->links() }}
            </div>
        @else
            <!-- Empty State for Text-to-Audio -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gradient-to-br from-gray-700 to-gray-600 rounded-full flex items-center justify-center mx-auto mb-6 border-3 border-gray-500">
                    <i class="fas fa-text-width text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-50 mb-3">{{ __('No text-to-audio conversions yet') }}</h3>
                <p class="text-gray-300 mb-6 max-w-md mx-auto">
                    {{ __('Convert your text to speech with AI voices in multiple languages') }}
                </p>
                <a href="{{ route('text-to-audio.create') }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3">
                    <i class="fas fa-plus"></i>
                    {{ __('Create your first text-to-audio') }}
                </a>
            </div>
        @endif
    </div>
</div>

@endsection
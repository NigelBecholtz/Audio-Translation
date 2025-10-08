@extends('layouts.app')

@section('title', 'Text to Audio')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 fade-in">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Text to Audio</h1>
                <p class="text-white text-lg">Convert your text to speech with AI voices</p>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('text-to-audio.create') }}" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium">
                    <i class="fas fa-plus mr-2"></i>
                    New Text to Audio
                </a>
                @if($textToAudioFiles->count() > 0)
                    <a href="{{ route('export.text-to-audio') }}" class="flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Export CSV
                    </a>
                @endif
                <a href="{{ route('audio.index') }}" class="flex items-center px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-colors">
                    <i class="fas fa-microphone mr-2"></i>
                    Audio Translation
                </a>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 mb-8 fade-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $user->getRemainingTranslations() }}</div>
                        <div class="text-sm text-white">Remaining</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $user->credits }}</div>
                        <div class="text-sm text-white">Credits</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ $user->textToAudioFiles()->count() }}</div>
                        <div class="text-sm text-white">Text to Audio</div>
                    </div>
                </div>
                @if($user->credits < 1)
                    <a href="{{ route('payment.credits') }}" class="flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 font-medium">
                        <i class="fas fa-credit-card mr-2"></i>
                        Buy Credits
                    </a>
                @endif
            </div>
        </div>

        <!-- Text to Audio Files List -->
        @if($textToAudioFiles->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($textToAudioFiles as $textToAudioFile)
                    <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 fade-in hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-white mb-2">
                                    {{ Str::limit($textToAudioFile->text_content, 50) }}
                                </h3>
                                <div class="flex items-center space-x-4 text-sm text-white">
                                    <span class="flex items-center">
                                        <i class="fas fa-language mr-1"></i>
                                        {{ strtoupper($textToAudioFile->language) }}
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-microphone mr-1"></i>
                                        {{ ucfirst($textToAudioFile->voice) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end space-y-2">
                                @if($textToAudioFile->isCompleted())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Completed
                                    </span>
                                @elseif($textToAudioFile->isFailed())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>
                                        Processing
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-white mb-4">
                            <div class="flex items-center justify-between">
                                <span>Created: {{ $textToAudioFile->created_at->format('d-m-Y H:i') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('text-to-audio.show', $textToAudioFile->id) }}" 
                               class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </a>
                            
                            @if($textToAudioFile->isCompleted())
                                <a href="{{ route('text-to-audio.download', $textToAudioFile->id) }}" 
                                   class="flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors text-sm font-medium">
                                    <i class="fas fa-download mr-2"></i>
                                    Download
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $textToAudioFiles->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-file-audio text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-4">No Text to Audio Conversions Yet</h3>
                <p class="text-white mb-8 max-w-md mx-auto">
                    Start by converting your first text to audio. Choose from 6 different AI voices and 22 languages.
                </p>
                <a href="{{ route('text-to-audio.create') }}" 
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium text-lg">
                    <i class="fas fa-plus mr-3"></i>
                    Create Your First Text to Audio
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

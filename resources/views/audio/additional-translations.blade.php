@extends('layouts.app')

@section('title', __('Add Additional Translations'))

@section('content')
<div class="min-h-screen bg-gray-900 text-white">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">{{ __('Add Additional Translations') }}</h1>
                    <p class="text-gray-400">{{ __('Translate your audio to more languages') }}</p>
                </div>
                <a href="{{ route('audio.show', $audioFile->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    {{ __('Back to Audio') }}
                </a>
            </div>

            <!-- Original Audio Info -->
            <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                    <i class="fas fa-file-audio mr-2 text-blue-400"></i>
                    {{ __('Original Audio') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-400">{{ __('Filename') }}</dt>
                        <dd class="text-sm text-white font-medium">{{ $audioFile->original_filename }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-400">{{ __('Source Language') }}</dt>
                        <dd class="text-sm text-white">{{ strtoupper($audioFile->source_language) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-400">{{ __('Size') }}</dt>
                        <dd class="text-sm text-white">{{ number_format($audioFile->file_size / 1024, 2) }} KB</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Translations -->
        @if($audioTranslations->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                <i class="fas fa-list mr-2 text-green-400"></i>
                {{ __('Existing Translations') }}
            </h2>
            <div class="grid gap-4">
                @foreach($audioTranslations as $translation)
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-xl border border-gray-600/30 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-volume-up text-green-400"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">
                                    {{ __('Translation to') }} {{ strtoupper($translation->target_language) }}
                                </h3>
                                <p class="text-sm text-gray-400">
                                    @if($translation->isCompleted())
                                        {{ __('Completed') }}
                                    @elseif($translation->isProcessing())
                                        {{ __('Processing...') }}
                                    @elseif($translation->isFailed())
                                        {{ __('Failed') }}
                                    @else
                                        {{ __('Pending') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($translation->isCompleted())
                        <a href="{{ route('audio.download-translation', [$audioFile->id, $translation->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-download mr-2"></i>
                            {{ __('Download') }}
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Add New Translations Form -->
        <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-plus mr-2 text-purple-400"></i>
                {{ __('Add New Translations') }}
            </h2>

            <form method="POST" action="{{ route('audio.store-additional-translations', $audioFile->id) }}" class="space-y-6">
                @csrf

                <!-- Language Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-3">
                        <i class="fas fa-language mr-2 text-blue-400"></i>
                        {{ __('Select languages to translate to') }}
                    </label>
                    @if($availableLanguages->isEmpty())
                        <div class="p-4 bg-gray-700/50 border border-gray-600/50 rounded-xl text-gray-300">
                            {{ __('All available languages have already been generated for this audio file.') }}
                        </div>
                    @else
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            @foreach($availableLanguages as $code => $name)
                                @php $inputId = 'language-' . \Illuminate\Support\Str::slug($code); @endphp
                                <div>
                                    <input type="checkbox"
                                           id="{{ $inputId }}"
                                           name="additional_languages[]"
                                           value="{{ $code }}"
                                           class="language-checkbox peer sr-only"
                                           data-language="{{ $name }}">
                                    <label for="{{ $inputId }}"
                                           class="block p-3 bg-gray-700/50 border-2 border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-gray-500 peer-checked:border-blue-500 peer-checked:bg-blue-500/20 focus-within:ring-2 focus-within:ring-blue-400">
                                        <div class="flex items-start justify-between">
                                            <div>
                                            <div class="text-lg font-semibold text-white">{{ strtoupper($code) }}</div>
                                            <div class="text-sm text-gray-400">{{ $name }}</div>
                                            </div>
                                            <span class="mt-1 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-xs opacity-0 peer-checked:opacity-100 transition-opacity">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 text-sm text-gray-300 gap-2">
                            <span>{{ __('Selected languages:') }} <strong id="selectedLanguagesCount">0</strong></span>
                            <span>{{ __('Estimated cost:') }} <strong id="estimatedCost">0</strong> {{ __('credits') }}</span>
                        </div>
                    @endif
                    @error('additional_languages')
                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Voice Selection -->
                <div>
                    <label for="voice" class="block text-sm font-semibold text-gray-300 mb-3">
                        <i class="fas fa-microphone mr-2 text-green-400"></i>
                        {{ __('Voice for all translations') }}
                    </label>
                    <select name="voice" id="voice"
                            class="w-full px-4 py-3 text-lg border-2 border-gray-600 rounded-xl focus:outline-none focus:ring-4 focus:ring-green-400 focus:border-green-500 transition-all bg-gray-700 text-white">
                        @foreach(config('audio.available_voices') as $voiceCode => $voiceName)
                        <option value="{{ $voiceCode }}" {{ $audioFile->voice === $voiceCode ? 'selected' : '' }}>
                            {{ $voiceName }}
                        </option>
                        @endforeach
                    </select>
                    @error('voice')
                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Style Instruction -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="style_instruction" class="block text-sm font-semibold text-gray-300">
                            <i class="fas fa-palette mr-2 text-pink-400"></i>
                            {{ __('Style Instruction (optional)') }}
                        </label>
                        <span class="text-xs text-gray-400">
                            {{ __('Will be applied to every selected language.') }}
                        </span>
                    </div>
                    <textarea name="style_instruction"
                              id="style_instruction"
                              rows="4"
                              class="w-full px-4 py-3 text-base border-2 border-gray-600 rounded-xl focus:outline-none focus:ring-4 focus:ring-pink-400 focus:border-pink-500 transition-all bg-gray-700 text-white resize-y"
                              placeholder="{{ __('Describe tone, pacing, mood, etc...') }}">{{ old('style_instruction', $audioFile->style_instruction) }}</textarea>
                    <p class="mt-2 text-xs text-gray-400 flex items-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('Leave empty to reuse the style instruction from the original translation.') }}
                    </p>
                    @error('style_instruction')
                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Cost Information -->
                <div class="bg-yellow-900/30 border border-yellow-600/30 rounded-xl p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-yellow-400 mt-1 mr-3 flex-shrink-0"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-200">{{ __('Cost Information') }}</h4>
                            <p class="text-sm text-yellow-300 mt-1">
                                {{ __('Each additional translation costs') }}
                                <strong>{{ config('stripe.default_cost_per_translation') }}</strong>
                                {{ __('credits. The total cost will be calculated based on the number of languages you select.') }}
                            </p>
                            <p class="text-sm text-yellow-300 mt-2">
                                {{ __('Your current balance:') }}
                                <strong>{{ auth()->user()->credits ?? 0 }}</strong>
                                {{ __('credits') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl font-bold text-lg">
                        <i class="fas fa-plus mr-2"></i>
                        {{ __('Start Additional Translations') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.language-checkbox');
    const selectedCountEl = document.getElementById('selectedLanguagesCount');
    const estimatedCostEl = document.getElementById('estimatedCost');
    const costPerLanguage = {{ (float) config('stripe.default_cost_per_translation', 0) }};

    if (!checkboxes.length || !selectedCountEl || !estimatedCostEl) {
        return;
    }

    const updateSummary = () => {
        const count = Array.from(checkboxes).filter(cb => cb.checked).length;
        selectedCountEl.textContent = count;
        estimatedCostEl.textContent = (count * costPerLanguage).toFixed(2);
    };

    checkboxes.forEach(cb => cb.addEventListener('change', updateSummary));
    updateSummary();
});
</script>
@endpush

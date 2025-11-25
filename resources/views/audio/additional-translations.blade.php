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
                {{ __('Add Additional Translation') }}
            </h2>

            <form method="POST" action="{{ route('audio.store-additional-translations', $audioFile->id) }}" class="space-y-6">
                @csrf

                <!-- Language Selection -->
                <div>
                    <label for="additional_languages" class="block text-sm font-semibold text-gray-300 mb-3">
                        <i class="fas fa-language mr-2 text-blue-400"></i>
                        {{ __('Select languages to translate to') }}
                    </label>
                    @if($availableLanguages->isEmpty())
                        <div class="p-4 bg-gray-700/50 border border-gray-600/50 rounded-xl text-gray-300">
                            {{ __('All available languages have already been generated for this audio file.') }}
                        </div>
                    @else
                        <select name="additional_languages" id="additional_languages"
                                class="w-full px-4 py-3 text-lg border-2 border-gray-600 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-400 focus:border-blue-500 transition-all bg-gray-700 text-white">
                            <option value="">{{ __('Select additional language') }}</option>

                            <!-- Priority Languages -->
                            <optgroup label="⭐ Most Used Languages">
                                @if(isset($availableLanguages['en-gb'])) <option value="en-gb">🇬🇧 English (UK)</option> @endif
                                @if(isset($availableLanguages['es'])) <option value="es">🇪🇸 Spanish</option> @endif
                                @if(isset($availableLanguages['de'])) <option value="de">🇩🇪 German</option> @endif
                                @if(isset($availableLanguages['fr'])) <option value="fr">🇫🇷 French</option> @endif
                                @if(isset($availableLanguages['it'])) <option value="it">🇮🇹 Italian</option> @endif
                                @if(isset($availableLanguages['nl'])) <option value="nl">🇳🇱 Dutch</option> @endif
                                @if(isset($availableLanguages['ro'])) <option value="ro">🇷🇴 Romanian</option> @endif
                                @if(isset($availableLanguages['el'])) <option value="el">🇬🇷 Greek</option> @endif
                                @if(isset($availableLanguages['sq'])) <option value="sq">🇦🇱 Albanian</option> @endif
                                @if(isset($availableLanguages['sk'])) <option value="sk">🇸🇰 Slovak</option> @endif
                                @if(isset($availableLanguages['lv'])) <option value="lv">🇱🇻 Latvian</option> @endif
                                @if(isset($availableLanguages['bg'])) <option value="bg">🇧🇬 Bulgarian</option> @endif
                                @if(isset($availableLanguages['fi'])) <option value="fi">🇫🇮 Finnish</option> @endif
                                @if(isset($availableLanguages['ca'])) <option value="ca">🇪🇸 Catalan</option> @endif
                            </optgroup>

                            <!-- Other English Variants -->
                            <optgroup label="🇺🇸 Other English">
                                @if(isset($availableLanguages['en-us'])) <option value="en-us">🇺🇸 English (US)</option> @endif
                                @if(isset($availableLanguages['en-au'])) <option value="en-au">🇦🇺 English (Australia)</option> @endif
                                @if(isset($availableLanguages['en-ca'])) <option value="en-ca">🇨🇦 English (Canada)</option> @endif
                                @if(isset($availableLanguages['en-in'])) <option value="en-in">🇮🇳 English (India)</option> @endif
                                @if(isset($availableLanguages['en'])) <option value="en">🌐 English (General)</option> @endif
                            </optgroup>

                            <!-- Major Languages -->
                            <optgroup label="🌍 Other Major Languages">
                                @if(isset($availableLanguages['pt'])) <option value="pt">🇵🇹 Portuguese</option> @endif
                                @if(isset($availableLanguages['ru'])) <option value="ru">🇷🇺 Russian</option> @endif
                                @if(isset($availableLanguages['ja'])) <option value="ja">🇯🇵 Japanese</option> @endif
                                @if(isset($availableLanguages['ko'])) <option value="ko">🇰🇷 Korean</option> @endif
                                @if(isset($availableLanguages['zh'])) <option value="zh">🇨🇳 Chinese</option> @endif
                                @if(isset($availableLanguages['ar'])) <option value="ar">🇸🇦 Arabic</option> @endif
                                @if(isset($availableLanguages['hi'])) <option value="hi">🇮🇳 Hindi</option> @endif
                            </optgroup>

                            <!-- European Languages -->
                            <optgroup label="🇪🇺 European Languages">
                                @if(isset($availableLanguages['sv'])) <option value="sv">🇸🇪 Swedish</option> @endif
                                @if(isset($availableLanguages['da'])) <option value="da">🇩🇰 Danish</option> @endif
                                @if(isset($availableLanguages['no'])) <option value="no">🇳🇴 Norwegian</option> @endif
                                @if(isset($availableLanguages['pl'])) <option value="pl">🇵🇱 Polish</option> @endif
                                @if(isset($availableLanguages['cs'])) <option value="cs">🇨🇿 Czech</option> @endif
                                @if(isset($availableLanguages['hu'])) <option value="hu">🇭🇺 Hungarian</option> @endif
                                @if(isset($availableLanguages['hr'])) <option value="hr">🇭🇷 Croatian</option> @endif
                                @if(isset($availableLanguages['sl'])) <option value="sl">🇸🇮 Slovenian</option> @endif
                                @if(isset($availableLanguages['tr'])) <option value="tr">🇹🇷 Turkish</option> @endif
                                @if(isset($availableLanguages['uk'])) <option value="uk">🇺🇦 Ukrainian</option> @endif
                                @if(isset($availableLanguages['lt'])) <option value="lt">🇱🇹 Lithuanian</option> @endif
                                @if(isset($availableLanguages['et'])) <option value="et">🇪🇪 Estonian</option> @endif
                                @if(isset($availableLanguages['eu'])) <option value="eu">🇪🇸 Basque</option> @endif
                            </optgroup>

                            <!-- Asian Languages -->
                            <optgroup label="🌏 Asian Languages">
                                @if(isset($availableLanguages['th'])) <option value="th">🇹🇭 Thai</option> @endif
                                @if(isset($availableLanguages['vi'])) <option value="vi">🇻🇳 Vietnamese</option> @endif
                                @if(isset($availableLanguages['id'])) <option value="id">🇮🇩 Indonesian</option> @endif
                                @if(isset($availableLanguages['ms'])) <option value="ms">🇲🇾 Malay</option> @endif
                                @if(isset($availableLanguages['tl'])) <option value="tl">🇵🇭 Filipino</option> @endif
                                @if(isset($availableLanguages['bn'])) <option value="bn">🇧🇩 Bengali</option> @endif
                                @if(isset($availableLanguages['ta'])) <option value="ta">🇮🇳 Tamil</option> @endif
                                @if(isset($availableLanguages['te'])) <option value="te">🇮🇳 Telugu</option> @endif
                                @if(isset($availableLanguages['ml'])) <option value="ml">🇮🇳 Malayalam</option> @endif
                                @if(isset($availableLanguages['kn'])) <option value="kn">🇮🇳 Kannada</option> @endif
                                @if(isset($availableLanguages['gu'])) <option value="gu">🇮🇳 Gujarati</option> @endif
                                @if(isset($availableLanguages['pa'])) <option value="pa">🇮🇳 Punjabi</option> @endif
                                @if(isset($availableLanguages['ur'])) <option value="ur">🇵🇰 Urdu</option> @endif
                                @if(isset($availableLanguages['si'])) <option value="si">🇱🇰 Sinhala</option> @endif
                                @if(isset($availableLanguages['my'])) <option value="my">🇲🇲 Burmese</option> @endif
                                @if(isset($availableLanguages['km'])) <option value="km">🇰🇭 Khmer</option> @endif
                                @if(isset($availableLanguages['lo'])) <option value="lo">🇱🇦 Lao</option> @endif
                                @if(isset($availableLanguages['mn'])) <option value="mn">🇲🇳 Mongolian</option> @endif
                            </optgroup>

                            <!-- African & Other Languages -->
                            <optgroup label="🌍 African & Other Languages">
                                @if(isset($availableLanguages['af'])) <option value="af">🇿🇦 Afrikaans</option> @endif
                                @if(isset($availableLanguages['sw'])) <option value="sw">🇰🇪 Swahili</option> @endif
                                @if(isset($availableLanguages['am'])) <option value="am">🇪🇹 Amharic</option> @endif
                                @if(isset($availableLanguages['hy'])) <option value="hy">🇦🇲 Armenian</option> @endif
                                @if(isset($availableLanguages['az'])) <option value="az">🇦🇿 Azerbaijani</option> @endif
                                @if(isset($availableLanguages['ka'])) <option value="ka">🇬🇪 Georgian</option> @endif
                                @if(isset($availableLanguages['he'])) <option value="he">🇮🇱 Hebrew</option> @endif
                                @if(isset($availableLanguages['fa'])) <option value="fa">🇮🇷 Persian</option> @endif
                                @if(isset($availableLanguages['ps'])) <option value="ps">🇦🇫 Pashto</option> @endif
                                @if(isset($availableLanguages['ne'])) <option value="ne">🇳🇵 Nepali</option> @endif
                            </optgroup>
                        </select>
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
                                {{ __('This additional translation costs') }}
                                <strong>{{ config('stripe.default_cost_per_translation') }}</strong>
                                {{ __('credits.') }}
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
                        {{ __('Start Additional Translation') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


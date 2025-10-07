@extends('layouts.app')

@section('title', 'New Text to Audio')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 48px; font-weight: bold; color: #ffffff; margin-bottom: 16px;">New Text to Audio</h1>
            <p style="font-size: 20px; color: #ffffff;">Convert your text to speech with AI voices</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Text Input Form -->
            <div style="grid-column: span 2;">
                <div class="card" style="border: 3px solid #4b5563;">
                    <div style="padding: 32px;">
                        <form id="textToAudioForm" action="{{ route('text-to-audio.store') }}" method="POST" class="space-y-8">
                            @csrf
                            
                            <!-- Text Input -->
                            <div>
                                <label style="display: block; font-size: 24px; font-weight: bold; color: #ffffff; margin-bottom: 24px;">
                                    <i class="fas fa-file-text" style="margin-right: 12px; color: #60a5fa;"></i>
                                    Enter Your Text
                                </label>
                                <textarea id="text_content" 
                                          name="text_content" 
                                          rows="8"
                                          required
                                          maxlength="50000"
                                          placeholder="Enter the text you want to convert to audio..."
                                          class="w-full px-6 py-4 text-lg border-3 border-blue-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all bg-white shadow-lg resize-none"
                                          style="font-family: inherit;">{{ old('text_content') }}</textarea>
                                <div class="flex justify-between items-center mt-2">
                                    <p class="text-sm text-white">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        For best voice consistency, keep text under 900 characters. Longer texts will be chunked (voice may vary slightly between chunks).
                                    </p>
                                    <span id="charCount" class="text-sm text-white">0 / 50000</span>
                                </div>
                                <div id="chunkingWarning" class="hidden mt-2 p-4 bg-yellow-500/20 border-2 border-yellow-500 rounded-lg">
                                    <p class="text-sm text-yellow-300 font-semibold">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        ⚠️ This text will be split into chunks. Voice consistency may vary between chunks due to API limitations.
                                    </p>
                                </div>
                                @error('text_content')
                                    <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Language and Voice Selection -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Language Selection -->
                                <div>
                                    <label for="language" class="block text-xl font-bold text-white mb-4">
                                        <i class="fas fa-language mr-3 text-blue-400"></i>
                                        Language
                                    </label>
                                    <select id="language" 
                                            name="language" 
                                            required
                                            class="w-full pl-6 pr-10 py-4 text-lg border-3 border-blue-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all bg-white shadow-lg">
                                        <option value="">Select language</option>
                                        
                                        <!-- Major Languages -->
                                        <optgroup label="🌍 Major Languages">
                                            <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>🇺🇸 English</option>
                                            <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>🇪🇸 Spanish</option>
                                            <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>🇫🇷 French</option>
                                            <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>🇩🇪 German</option>
                                            <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>🇮🇹 Italian</option>
                                            <option value="pt" {{ old('language') == 'pt' ? 'selected' : '' }}>🇵🇹 Portuguese</option>
                                            <option value="ru" {{ old('language') == 'ru' ? 'selected' : '' }}>🇷🇺 Russian</option>
                                            <option value="ja" {{ old('language') == 'ja' ? 'selected' : '' }}>🇯🇵 Japanese</option>
                                            <option value="ko" {{ old('language') == 'ko' ? 'selected' : '' }}>🇰🇷 Korean</option>
                                            <option value="zh" {{ old('language') == 'zh' ? 'selected' : '' }}>🇨🇳 Chinese</option>
                                            <option value="ar" {{ old('language') == 'ar' ? 'selected' : '' }}>🇸🇦 Arabic</option>
                                            <option value="hi" {{ old('language') == 'hi' ? 'selected' : '' }}>🇮🇳 Hindi</option>
                                        </optgroup>
                                        
                                        <!-- European Languages -->
                                        <optgroup label="🇪🇺 European Languages">
                                            <option value="nl" {{ old('language') == 'nl' ? 'selected' : '' }}>🇳🇱 Dutch</option>
                                            <option value="sv" {{ old('language') == 'sv' ? 'selected' : '' }}>🇸🇪 Swedish</option>
                                            <option value="da" {{ old('language') == 'da' ? 'selected' : '' }}>🇩🇰 Danish</option>
                                            <option value="no" {{ old('language') == 'no' ? 'selected' : '' }}>🇳🇴 Norwegian</option>
                                            <option value="fi" {{ old('language') == 'fi' ? 'selected' : '' }}>🇫🇮 Finnish</option>
                                            <option value="pl" {{ old('language') == 'pl' ? 'selected' : '' }}>🇵🇱 Polish</option>
                                            <option value="cs" {{ old('language') == 'cs' ? 'selected' : '' }}>🇨🇿 Czech</option>
                                            <option value="sk" {{ old('language') == 'sk' ? 'selected' : '' }}>🇸🇰 Slovak</option>
                                            <option value="hu" {{ old('language') == 'hu' ? 'selected' : '' }}>🇭🇺 Hungarian</option>
                                            <option value="ro" {{ old('language') == 'ro' ? 'selected' : '' }}>🇷🇴 Romanian</option>
                                            <option value="bg" {{ old('language') == 'bg' ? 'selected' : '' }}>🇧🇬 Bulgarian</option>
                                            <option value="hr" {{ old('language') == 'hr' ? 'selected' : '' }}>🇭🇷 Croatian</option>
                                            <option value="sl" {{ old('language') == 'sl' ? 'selected' : '' }}>🇸🇮 Slovenian</option>
                                            <option value="el" {{ old('language') == 'el' ? 'selected' : '' }}>🇬🇷 Greek</option>
                                            <option value="tr" {{ old('language') == 'tr' ? 'selected' : '' }}>🇹🇷 Turkish</option>
                                            <option value="uk" {{ old('language') == 'uk' ? 'selected' : '' }}>🇺🇦 Ukrainian</option>
                                            <option value="lv" {{ old('language') == 'lv' ? 'selected' : '' }}>🇱🇻 Latvian</option>
                                            <option value="lt" {{ old('language') == 'lt' ? 'selected' : '' }}>🇱🇹 Lithuanian</option>
                                            <option value="et" {{ old('language') == 'et' ? 'selected' : '' }}>🇪🇪 Estonian</option>
                                            <option value="ca" {{ old('language') == 'ca' ? 'selected' : '' }}>🇪🇸 Catalan</option>
                                            <option value="eu" {{ old('language') == 'eu' ? 'selected' : '' }}>🇪🇸 Basque</option>
                                        </optgroup>
                                        
                                        <!-- Asian Languages -->
                                        <optgroup label="🌏 Asian Languages">
                                            <option value="th" {{ old('language') == 'th' ? 'selected' : '' }}>🇹🇭 Thai</option>
                                            <option value="vi" {{ old('language') == 'vi' ? 'selected' : '' }}>🇻🇳 Vietnamese</option>
                                            <option value="id" {{ old('language') == 'id' ? 'selected' : '' }}>🇮🇩 Indonesian</option>
                                            <option value="ms" {{ old('language') == 'ms' ? 'selected' : '' }}>🇲🇾 Malay</option>
                                            <option value="tl" {{ old('language') == 'tl' ? 'selected' : '' }}>🇵🇭 Filipino</option>
                                            <option value="bn" {{ old('language') == 'bn' ? 'selected' : '' }}>🇧🇩 Bengali</option>
                                            <option value="ta" {{ old('language') == 'ta' ? 'selected' : '' }}>🇮🇳 Tamil</option>
                                            <option value="te" {{ old('language') == 'te' ? 'selected' : '' }}>🇮🇳 Telugu</option>
                                            <option value="ml" {{ old('language') == 'ml' ? 'selected' : '' }}>🇮🇳 Malayalam</option>
                                            <option value="kn" {{ old('language') == 'kn' ? 'selected' : '' }}>🇮🇳 Kannada</option>
                                            <option value="gu" {{ old('language') == 'gu' ? 'selected' : '' }}>🇮🇳 Gujarati</option>
                                            <option value="pa" {{ old('language') == 'pa' ? 'selected' : '' }}>🇮🇳 Punjabi</option>
                                            <option value="ur" {{ old('language') == 'ur' ? 'selected' : '' }}>🇵🇰 Urdu</option>
                                            <option value="si" {{ old('language') == 'si' ? 'selected' : '' }}>🇱🇰 Sinhala</option>
                                            <option value="my" {{ old('language') == 'my' ? 'selected' : '' }}>🇲🇲 Burmese</option>
                                            <option value="km" {{ old('language') == 'km' ? 'selected' : '' }}>🇰🇭 Khmer</option>
                                            <option value="lo" {{ old('language') == 'lo' ? 'selected' : '' }}>🇱🇦 Lao</option>
                                            <option value="mn" {{ old('language') == 'mn' ? 'selected' : '' }}>🇲🇳 Mongolian</option>
                                        </optgroup>
                                        
                                        <!-- African & Other Languages -->
                                        <optgroup label="🌍 African & Other Languages">
                                            <option value="af" {{ old('language') == 'af' ? 'selected' : '' }}>🇿🇦 Afrikaans</option>
                                            <option value="sw" {{ old('language') == 'sw' ? 'selected' : '' }}>🇰🇪 Swahili</option>
                                            <option value="am" {{ old('language') == 'am' ? 'selected' : '' }}>🇪🇹 Amharic</option>
                                            <option value="sq" {{ old('language') == 'sq' ? 'selected' : '' }}>🇦🇱 Albanian</option>
                                            <option value="hy" {{ old('language') == 'hy' ? 'selected' : '' }}>🇦🇲 Armenian</option>
                                            <option value="az" {{ old('language') == 'az' ? 'selected' : '' }}>🇦🇿 Azerbaijani</option>
                                            <option value="ka" {{ old('language') == 'ka' ? 'selected' : '' }}>🇬🇪 Georgian</option>
                                            <option value="he" {{ old('language') == 'he' ? 'selected' : '' }}>🇮🇱 Hebrew</option>
                                            <option value="fa" {{ old('language') == 'fa' ? 'selected' : '' }}>🇮🇷 Persian</option>
                                            <option value="ps" {{ old('language') == 'ps' ? 'selected' : '' }}>🇦🇫 Pashto</option>
                                            <option value="ne" {{ old('language') == 'ne' ? 'selected' : '' }}>🇳🇵 Nepali</option>
                                            <option value="si" {{ old('language') == 'si' ? 'selected' : '' }}>🇱🇰 Sinhala</option>
                                        </optgroup>
                                    </select>
                                    @error('language')
                                        <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Voice Selection -->
                                <div>
                                    <label for="voice" class="block text-xl font-bold text-white mb-4">
                                        <i class="fas fa-microphone mr-3 text-purple-400"></i>
                                        Voice Selection
                                    </label>
                                    <select id="voice" 
                                            name="voice" 
                                            required
                                            class="w-full pl-6 pr-10 py-4 text-lg border-3 border-purple-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-purple-300 focus:border-purple-500 transition-all bg-white shadow-lg">
                                        <option value="">Select voice</option>
                                        
                                        <!-- Gemini 2.5 Pro TTS Voices -->
                <optgroup label="🎯 Gemini 2.5 Pro TTS Voices">
                    <!-- Female Voices -->
                    <option value="achernar" {{ old('voice') == 'achernar' ? 'selected' : '' }}>👩 Achernar - Clear and expressive female voice</option>
                    <option value="aoede" {{ old('voice') == 'aoede' ? 'selected' : '' }}>👩 Aoede - Warm and engaging female voice</option>
                    <option value="autonoe" {{ old('voice') == 'autonoe' ? 'selected' : '' }}>👩 Autonoe - Soft and gentle female voice</option>
                    <option value="callirrhoe" {{ old('voice') == 'callirrhoe' ? 'selected' : '' }}>👩 Callirrhoe - Bright and energetic female voice</option>
                    <option value="despina" {{ old('voice') == 'despina' ? 'selected' : '' }}>👩 Despina - Smooth and professional female voice</option>
                    <option value="erinome" {{ old('voice') == 'erinome' ? 'selected' : '' }}>👩 Erinome - Wise and calm female voice</option>
                    <option value="gacrux" {{ old('voice') == 'gacrux' ? 'selected' : '' }}>👩 Gacrux - Vibrant and lively female voice</option>
                    <option value="kore" {{ old('voice') == 'kore' ? 'selected' : '' }}>👩 Kore - Balanced and versatile female voice</option>
                    <option value="laomedeia" {{ old('voice') == 'laomedeia' ? 'selected' : '' }}>👩 Laomedeia - Warm and engaging female voice</option>
                    <option value="leda" {{ old('voice') == 'leda' ? 'selected' : '' }}>👩 Leda - Clear and expressive female voice</option>
                    <option value="pulcherrima" {{ old('voice') == 'pulcherrima' ? 'selected' : '' }}>👩 Pulcherrima - Bright and energetic female voice</option>
                    <option value="sulafat" {{ old('voice') == 'sulafat' ? 'selected' : '' }}>👩 Sulafat - Soft and gentle female voice</option>
                    <option value="vindemiatrix" {{ old('voice') == 'vindemiatrix' ? 'selected' : '' }}>👩 Vindemiatrix - Smooth and professional female voice</option>
                    <option value="zephyr" {{ old('voice') == 'zephyr' ? 'selected' : '' }}>👩 Zephyr - Vibrant and lively female voice</option>
                    
                    <!-- Male Voices -->
                    <option value="achird" {{ old('voice') == 'achird' ? 'selected' : '' }}>👨 Achird - Deep and authoritative male voice</option>
                    <option value="algenib" {{ old('voice') == 'algenib' ? 'selected' : '' }}>👨 Algenib - Strong and confident male voice</option>
                    <option value="algieba" {{ old('voice') == 'algieba' ? 'selected' : '' }}>👨 Algieba - Warm and engaging male voice</option>
                    <option value="alnilam" {{ old('voice') == 'alnilam' ? 'selected' : '' }}>👨 Alnilam - Clear and expressive male voice</option>
                    <option value="charon" {{ old('voice') == 'charon' ? 'selected' : '' }}>👨 Charon - Deep and authoritative male voice</option>
                    <option value="enceladus" {{ old('voice') == 'enceladus' ? 'selected' : '' }}>👨 Enceladus - Strong and confident male voice</option>
                    <option value="fenrir" {{ old('voice') == 'fenrir' ? 'selected' : '' }}>👨 Fenrir - Powerful and commanding male voice</option>
                    <option value="lapetus" {{ old('voice') == 'lapetus' ? 'selected' : '' }}>👨 Lapetus - Warm and engaging male voice</option>
                    <option value="orus" {{ old('voice') == 'orus' ? 'selected' : '' }}>👨 Orus - Clear and expressive male voice</option>
                    <option value="puck" {{ old('voice') == 'puck' ? 'selected' : '' }}>👨 Puck - Energetic and lively male voice</option>
                    <option value="rasalgethi" {{ old('voice') == 'rasalgethi' ? 'selected' : '' }}>👨 Rasalgethi - Deep and authoritative male voice</option>
                    <option value="sadachbia" {{ old('voice') == 'sadachbia' ? 'selected' : '' }}>👨 Sadachbia - Strong and confident male voice</option>
                    <option value="sadaltager" {{ old('voice') == 'sadaltager' ? 'selected' : '' }}>👨 Sadaltager - Warm and engaging male voice</option>
                    <option value="schedar" {{ old('voice') == 'schedar' ? 'selected' : '' }}>👨 Schedar - Clear and expressive male voice</option>
                    <option value="umbriel" {{ old('voice') == 'umbriel' ? 'selected' : '' }}>👨 Umbriel - Deep and authoritative male voice</option>
                    <option value="zubenelgenubi" {{ old('voice') == 'zubenelgenubi' ? 'selected' : '' }}>👨 Zubenelgenubi - Strong and confident male voice</option>
                </optgroup>
                                    </select>
                                    <p class="mt-2 text-sm text-white">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Choose the voice that will speak your text. Gemini 2.5 Pro TTS offers better accent support and more natural pronunciation.
                                    </p>
                                    @error('voice')
                                        <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Style Instruction (Optional) -->
                            <div>
                                <label for="style_instruction" class="block text-xl font-bold text-white mb-4">
                                    <i class="fas fa-palette mr-3 text-green-400"></i>
                                    Style Instruction (Optional)
                                </label>
                                <textarea id="style_instruction" 
                                        name="style_instruction" 
                                        rows="3"
                                        placeholder="e.g., 'Speak with enthusiasm and energy', 'Use a calm and soothing tone', 'Sound professional and authoritative' (up to 5000 characters)"
                                        class="w-full pl-6 pr-6 py-4 text-lg border-3 border-green-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all bg-white shadow-lg resize-none">{{ old('style_instruction') }}</textarea>
                                <p class="mt-2 text-sm text-white">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Provide style instructions to customize how the voice should speak (tone, emotion, pace, etc.). Only works with Gemini 2.5 Pro TTS voices.
                                </p>
                                @error('style_instruction')
                                    <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div style="display: flex; justify-content: flex-end; gap: 24px;">
                                <a href="{{ route('text-to-audio.index') }}" class="btn-secondary" style="font-size: 18px; padding: 16px 32px;">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancel
                                </a>
                                <button type="submit" id="submitButton" class="btn-primary" style="font-size: 20px; padding: 16px 48px;">
                                    <i class="fas fa-magic"></i>
                                    Generate Audio
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Sidebar -->
            <div class="lg:col-span-1">
                <div class="card" style="border: 3px solid #4b5563;">
                    <div style="padding: 32px;">
                        <h3 class="text-xl font-bold text-white mb-6">How it works</h3>
                        <div class="space-y-6">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-600">1</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white">Enter Text</h4>
                                    <p class="text-sm text-white">Type or paste your text (up to 50,000 characters)</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-600">2</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white">Select Language & Voice</h4>
                                    <p class="text-sm text-white">Choose language and AI voice for your audio</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-600">3</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white">AI Processing</h4>
                                    <p class="text-sm text-white">Gemini 2.5 Pro TTS converts your text to audio with automatic chunking</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-indigo-600">4</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white">Download Result</h4>
                                    <p class="text-sm text-white">Download your generated audio file</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8 p-4 bg-gradient-to-r from-gray-700 to-gray-600 rounded-xl">
                            <h4 class="font-semibold text-white mb-2">
                                <i class="fas fa-lightbulb mr-2 text-yellow-400"></i>
                                Pro Tip
                            </h4>
                            <p class="text-sm text-white">
                                For best results, use clear and well-formatted text without special characters.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('text_content');
    const charCount = document.getElementById('charCount');
    
    function updateCharCount() {
        const count = textarea.value.length;
        const chunkingWarning = document.getElementById('chunkingWarning');
        
        charCount.textContent = `${count} / 50000`;
        
        if (count > 50000) {
            charCount.classList.add('text-red-500');
            charCount.classList.remove('text-white');
        } else {
            charCount.classList.add('text-white');
            charCount.classList.remove('text-red-500');
        }
        
        // Show warning if text will be chunked (>900 characters)
        if (count > 900) {
            chunkingWarning.classList.remove('hidden');
        } else {
            chunkingWarning.classList.add('hidden');
        }
    }
    
    textarea.addEventListener('input', updateCharCount);
    updateCharCount(); // Initial count
});
</script>
@endsection

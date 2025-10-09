@extends('layouts.app')

@section('title', 'New Audio Translation')

@section('content')
<div class="px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12 fade-in">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">{{ __('New Audio Translation') }}</h1>
            <p class="text-lg md:text-xl text-gray-300">{{ __('Upload your audio file and let AI translate it for you') }}</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Upload Form -->
            <div class="lg:col-span-2">
                <div class="card border-2 border-gray-600">
                    <div class="p-6 md:p-8">
                        <form id="uploadForm" action="{{ route('audio.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                            @csrf
                            
                            <!-- Drag & Drop Upload Area -->
                            <div>
                                <label class="block text-2xl font-bold text-white mb-6">
                                    <i class="fas fa-upload mr-3 text-blue-400"></i>
                                    {{ __('Upload Audio File') }}
                                </label>
                                
                                <!-- Upload Progress (Hidden by default) -->
                                <div id="uploadProgress" class="hidden mb-6">
                                    <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-6">
                                        <div class="flex items-center mb-4">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                            </div>
                                            <h4 class="text-lg font-bold text-blue-800">Upload in progress...</h4>
                                        </div>
                                        <div class="w-full bg-blue-200 rounded-full h-3">
                                            <div id="progressBar" class="bg-blue-600 h-3 rounded-full transition-all duration-300 animate-pulse" style="width: 60%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="dropZone" class="relative border-4 border-dashed border-blue-400 rounded-2xl p-12 text-center bg-gradient-to-br from-gray-800 to-gray-700 cursor-pointer transition-all hover:border-blue-300 hover:bg-gradient-to-br hover:from-gray-700 hover:to-gray-600" onclick="document.getElementById('audio').click()">
                                    <input type="file" 
                                           id="audio" 
                                           name="audio" 
                                           accept=".mp3,.wav,.m4a,.mp4"
                                           required
                                           class="hidden">
                                    
                                    <div id="dropZoneContent">
                                        <div class="w-24 h-24 bg-gradient-to-br from-gray-700 to-gray-600 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-blue-400">
                                            <i class="fas fa-microphone text-4xl text-blue-400"></i>
                                        </div>
                                        <p class="text-2xl font-bold text-white mb-3">
                                            {{ __('Drag your audio file here') }}
                                        </p>
                                        <p class="text-lg text-gray-300 mb-6">
                                            {{ __('or click to select a file') }}
                                        </p>
                                        <div class="inline-flex items-center px-8 py-4 bg-blue-500 text-white rounded-xl text-lg font-bold shadow-lg hover:bg-blue-600 transition">
                                            <i class="fas fa-folder-open mr-3"></i>
                                            {{ __('Select File') }}
                                        </div>
                                    </div>
                                    
                                    <div id="fileInfo" class="hidden">
                                        <div class="flex items-center justify-center gap-4">
                                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center border-4 border-green-200">
                                                <i class="fas fa-check text-green-600 text-2xl"></i>
                                            </div>
                                            <div class="text-left">
                                                <p class="font-bold text-white text-lg" id="fileName"></p>
                                                <p class="text-sm text-gray-300 font-medium" id="fileSize"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 flex items-center justify-center gap-8 flex-wrap">
                                    <div class="flex items-center bg-gray-700 px-4 py-2 rounded-lg border-2 border-blue-400">
                                        <i class="fas fa-file-audio mr-2 text-blue-400"></i>
                                        <span class="text-sm font-semibold text-white">MP3, WAV, M4A</span>
                                    </div>
                                    <div class="flex items-center bg-gray-700 px-4 py-2 rounded-lg border-2 border-green-500">
                                        <i class="fas fa-weight mr-2 text-green-500"></i>
                                        <span class="text-sm font-semibold text-white">Max {{ config('audio.max_upload_size', 100) }}MB</span>
                                    </div>
                                    <div class="flex items-center bg-gray-700 px-4 py-2 rounded-lg border-2 border-purple-500">
                                        <i class="fas fa-clock mr-2 text-purple-500"></i>
                                        <span class="text-sm font-semibold text-white">Max 5 min</span>
                                    </div>
                                </div>
                                
                                @error('audio')
                                    <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Language Selection -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Source Language -->
                                <div>
                                    <label for="source_language" class="block text-xl font-bold text-white mb-4">
                                        <i class="fas fa-language mr-2 text-blue-400"></i>
                                        {{ __('Source Language') }}
                                    </label>
                                    <select id="source_language" 
                                            name="source_language" 
                                            required
                                            class="w-full px-6 py-4 text-lg border-2 border-blue-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-400 focus:border-blue-500 transition-all bg-white shadow-lg">
                                        <option value="">{{ __('Select source language') }}</option>
                                        
                                        <!-- Priority Languages -->
                                        <optgroup label="⭐ Most Used Languages">
                                            <option value="en-gb">🇬🇧 English (UK)</option>
                                            <option value="es">🇪🇸 Spanish</option>
                                            <option value="de">🇩🇪 German</option>
                                            <option value="fr">🇫🇷 French</option>
                                            <option value="it">🇮🇹 Italian</option>
                                            <option value="nl">🇳🇱 Dutch</option>
                                            <option value="ro">🇷🇴 Romanian</option>
                                            <option value="el">🇬🇷 Greek</option>
                                            <option value="sq">🇦🇱 Albanian</option>
                                            <option value="sk">🇸🇰 Slovak</option>
                                            <option value="lv">🇱🇻 Latvian</option>
                                            <option value="bg">🇧🇬 Bulgarian</option>
                                            <option value="fi">🇫🇮 Finnish</option>
                                            <option value="ca">🇪🇸 Catalan</option>
                                        </optgroup>
                                        
                                        <!-- Other English Variants -->
                                        <optgroup label="🇺🇸 Other English">
                                            <option value="en-us">🇺🇸 English (US)</option>
                                            <option value="en-au">🇦🇺 English (Australia)</option>
                                            <option value="en-ca">🇨🇦 English (Canada)</option>
                                            <option value="en-in">🇮🇳 English (India)</option>
                                            <option value="en">🌐 English (General)</option>
                                        </optgroup>
                                        
                                        <!-- Major Languages -->
                                        <optgroup label="🌍 Other Major Languages">
                                            <option value="pt">🇵🇹 Portuguese</option>
                                            <option value="ru">🇷🇺 Russian</option>
                                            <option value="ja">🇯🇵 Japanese</option>
                                            <option value="ko">🇰🇷 Korean</option>
                                            <option value="zh">🇨🇳 Chinese</option>
                                            <option value="ar">🇸🇦 Arabic</option>
                                            <option value="hi">🇮🇳 Hindi</option>
                                        </optgroup>
                                        
                                        <!-- European Languages -->
                                        <optgroup label="🇪🇺 European Languages">
                                            <option value="nl">🇳🇱 Dutch</option>
                                            <option value="sv">🇸🇪 Swedish</option>
                                            <option value="da">🇩🇰 Danish</option>
                                            <option value="no">🇳🇴 Norwegian</option>
                                            <option value="fi">🇫🇮 Finnish</option>
                                            <option value="pl">🇵🇱 Polish</option>
                                            <option value="cs">🇨🇿 Czech</option>
                                            <option value="sk">🇸🇰 Slovak</option>
                                            <option value="hu">🇭🇺 Hungarian</option>
                                            <option value="ro">🇷🇴 Romanian</option>
                                            <option value="bg">🇧🇬 Bulgarian</option>
                                            <option value="hr">🇭🇷 Croatian</option>
                                            <option value="sl">🇸🇮 Slovenian</option>
                                            <option value="el">🇬🇷 Greek</option>
                                            <option value="tr">🇹🇷 Turkish</option>
                                            <option value="uk">🇺🇦 Ukrainian</option>
                                            <option value="lv">🇱🇻 Latvian</option>
                                            <option value="lt">🇱🇹 Lithuanian</option>
                                            <option value="et">🇪🇪 Estonian</option>
                                            <option value="ca">🇪🇸 Catalan</option>
                                            <option value="eu">🇪🇸 Basque</option>
                                        </optgroup>
                                        
                                        <!-- Asian Languages -->
                                        <optgroup label="🌏 Asian Languages">
                                            <option value="th">🇹🇭 Thai</option>
                                            <option value="vi">🇻🇳 Vietnamese</option>
                                            <option value="id">🇮🇩 Indonesian</option>
                                            <option value="ms">🇲🇾 Malay</option>
                                            <option value="tl">🇵🇭 Filipino</option>
                                            <option value="bn">🇧🇩 Bengali</option>
                                            <option value="ta">🇮🇳 Tamil</option>
                                            <option value="te">🇮🇳 Telugu</option>
                                            <option value="ml">🇮🇳 Malayalam</option>
                                            <option value="kn">🇮🇳 Kannada</option>
                                            <option value="gu">🇮🇳 Gujarati</option>
                                            <option value="pa">🇮🇳 Punjabi</option>
                                            <option value="ur">🇵🇰 Urdu</option>
                                            <option value="si">🇱🇰 Sinhala</option>
                                            <option value="my">🇲🇲 Burmese</option>
                                            <option value="km">🇰🇭 Khmer</option>
                                            <option value="lo">🇱🇦 Lao</option>
                                            <option value="mn">🇲🇳 Mongolian</option>
                                        </optgroup>
                                        
                                        <!-- African & Other Languages -->
                                        <optgroup label="🌍 African & Other Languages">
                                            <option value="af">🇿🇦 Afrikaans</option>
                                            <option value="sw">🇰🇪 Swahili</option>
                                            <option value="am">🇪🇹 Amharic</option>
                                            <option value="sq">🇦🇱 Albanian</option>
                                            <option value="hy">🇦🇲 Armenian</option>
                                            <option value="az">🇦🇿 Azerbaijani</option>
                                            <option value="ka">🇬🇪 Georgian</option>
                                            <option value="he">🇮🇱 Hebrew</option>
                                            <option value="fa">🇮🇷 Persian</option>
                                            <option value="ps">🇦🇫 Pashto</option>
                                            <option value="ne">🇳🇵 Nepali</option>
                                        </optgroup>
                                    </select>
                                    @error('source_language')
                                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Target Language -->
                                <div>
                                    <label for="target_language" class="block text-xl font-bold text-white mb-4">
                                        <i class="fas fa-flag mr-2 text-green-400"></i>
                                        {{ __('Target Language') }}
                                    </label>
                                    <select id="target_language" 
                                            name="target_language" 
                                            required
                                            class="w-full px-6 py-4 text-lg border-2 border-green-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-green-400 focus:border-green-500 transition-all bg-white shadow-lg">
                                        <option value="">{{ __('Select target language') }}</option>
                                        
                                        <!-- Priority Languages -->
                                        <optgroup label="⭐ Most Used Languages">
                                            <option value="en-gb">🇬🇧 English (UK)</option>
                                            <option value="es">🇪🇸 Spanish</option>
                                            <option value="de">🇩🇪 German</option>
                                            <option value="fr">🇫🇷 French</option>
                                            <option value="it">🇮🇹 Italian</option>
                                            <option value="nl">🇳🇱 Dutch</option>
                                            <option value="ro">🇷🇴 Romanian</option>
                                            <option value="el">🇬🇷 Greek</option>
                                            <option value="sq">🇦🇱 Albanian</option>
                                            <option value="sk">🇸🇰 Slovak</option>
                                            <option value="lv">🇱🇻 Latvian</option>
                                            <option value="bg">🇧🇬 Bulgarian</option>
                                            <option value="fi">🇫🇮 Finnish</option>
                                            <option value="ca">🇪🇸 Catalan</option>
                                        </optgroup>
                                        
                                        <!-- Other English Variants -->
                                        <optgroup label="🇺🇸 Other English">
                                            <option value="en-us">🇺🇸 English (US)</option>
                                            <option value="en-au">🇦🇺 English (Australia)</option>
                                            <option value="en-ca">🇨🇦 English (Canada)</option>
                                            <option value="en-in">🇮🇳 English (India)</option>
                                            <option value="en">🌐 English (General)</option>
                                        </optgroup>
                                        
                                        <!-- Major Languages -->
                                        <optgroup label="🌍 Other Major Languages">
                                            <option value="pt">🇵🇹 Portuguese</option>
                                            <option value="ru">🇷🇺 Russian</option>
                                            <option value="ja">🇯🇵 Japanese</option>
                                            <option value="ko">🇰🇷 Korean</option>
                                            <option value="zh">🇨🇳 Chinese</option>
                                            <option value="ar">🇸🇦 Arabic</option>
                                            <option value="hi">🇮🇳 Hindi</option>
                                        </optgroup>
                                        
                                        <!-- European Languages -->
                                        <optgroup label="🇪🇺 European Languages">
                                            <option value="nl">🇳🇱 Dutch</option>
                                            <option value="sv">🇸🇪 Swedish</option>
                                            <option value="da">🇩🇰 Danish</option>
                                            <option value="no">🇳🇴 Norwegian</option>
                                            <option value="fi">🇫🇮 Finnish</option>
                                            <option value="pl">🇵🇱 Polish</option>
                                            <option value="cs">🇨🇿 Czech</option>
                                            <option value="sk">🇸🇰 Slovak</option>
                                            <option value="hu">🇭🇺 Hungarian</option>
                                            <option value="ro">🇷🇴 Romanian</option>
                                            <option value="bg">🇧🇬 Bulgarian</option>
                                            <option value="hr">🇭🇷 Croatian</option>
                                            <option value="sl">🇸🇮 Slovenian</option>
                                            <option value="el">🇬🇷 Greek</option>
                                            <option value="tr">🇹🇷 Turkish</option>
                                            <option value="uk">🇺🇦 Ukrainian</option>
                                            <option value="lv">🇱🇻 Latvian</option>
                                            <option value="lt">🇱🇹 Lithuanian</option>
                                            <option value="et">🇪🇪 Estonian</option>
                                            <option value="ca">🇪🇸 Catalan</option>
                                            <option value="eu">🇪🇸 Basque</option>
                                        </optgroup>
                                        
                                        <!-- Asian Languages -->
                                        <optgroup label="🌏 Asian Languages">
                                            <option value="th">🇹🇭 Thai</option>
                                            <option value="vi">🇻🇳 Vietnamese</option>
                                            <option value="id">🇮🇩 Indonesian</option>
                                            <option value="ms">🇲🇾 Malay</option>
                                            <option value="tl">🇵🇭 Filipino</option>
                                            <option value="bn">🇧🇩 Bengali</option>
                                            <option value="ta">🇮🇳 Tamil</option>
                                            <option value="te">🇮🇳 Telugu</option>
                                            <option value="ml">🇮🇳 Malayalam</option>
                                            <option value="kn">🇮🇳 Kannada</option>
                                            <option value="gu">🇮🇳 Gujarati</option>
                                            <option value="pa">🇮🇳 Punjabi</option>
                                            <option value="ur">🇵🇰 Urdu</option>
                                            <option value="si">🇱🇰 Sinhala</option>
                                            <option value="my">🇲🇲 Burmese</option>
                                            <option value="km">🇰🇭 Khmer</option>
                                            <option value="lo">🇱🇦 Lao</option>
                                            <option value="mn">🇲🇳 Mongolian</option>
                                        </optgroup>
                                        
                                        <!-- African & Other Languages -->
                                        <optgroup label="🌍 African & Other Languages">
                                            <option value="af">🇿🇦 Afrikaans</option>
                                            <option value="sw">🇰🇪 Swahili</option>
                                            <option value="am">🇪🇹 Amharic</option>
                                            <option value="sq">🇦🇱 Albanian</option>
                                            <option value="hy">🇦🇲 Armenian</option>
                                            <option value="az">🇦🇿 Azerbaijani</option>
                                            <option value="ka">🇬🇪 Georgian</option>
                                            <option value="he">🇮🇱 Hebrew</option>
                                            <option value="fa">🇮🇷 Persian</option>
                                            <option value="ps">🇦🇫 Pashto</option>
                                            <option value="ne">🇳🇵 Nepali</option>
                                        </optgroup>
                                    </select>
                                    @error('target_language')
                                        <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Voice Selection -->
                            <div>
                                <label for="voice" class="block text-xl font-bold text-white mb-4">
                                    <i class="fas fa-microphone mr-2 text-purple-400"></i>
                                    {{ __('Voice Selection') }}
                                </label>
                                <select id="voice" 
                                        name="voice" 
                                        required
                                        class="w-full px-6 py-4 text-lg border-2 border-purple-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-purple-400 focus:border-purple-500 transition-all bg-white shadow-lg">
                                    <option value="">{{ __('Select voice for translation') }}</option>
                                    
                                    <!-- Gemini 2.5 Pro TTS Voices -->
                                    <optgroup label="🎯 Gemini 2.5 Pro TTS Voices">
                                        <!-- Female Voices -->
                                        <option value="achernar">👩 Achernar - Clear and expressive female voice</option>
                                        <option value="aoede">👩 Aoede - Warm and engaging female voice</option>
                                        <option value="autonoe">👩 Autonoe - Soft and gentle female voice</option>
                                        <option value="callirrhoe">👩 Callirrhoe - Bright and energetic female voice</option>
                                        <option value="despina">👩 Despina - Smooth and professional female voice</option>
                                        <option value="erinome">👩 Erinome - Wise and calm female voice</option>
                                        <option value="gacrux">👩 Gacrux - Vibrant and lively female voice</option>
                                        <option value="kore">👩 Kore - Balanced and versatile female voice</option>
                                        <option value="laomedeia">👩 Laomedeia - Warm and engaging female voice</option>
                                        <option value="leda">👩 Leda - Clear and expressive female voice</option>
                                        <option value="pulcherrima">👩 Pulcherrima - Bright and energetic female voice</option>
                                        <option value="sulafat">👩 Sulafat - Soft and gentle female voice</option>
                                        <option value="vindemiatrix">👩 Vindemiatrix - Smooth and professional female voice</option>
                                        <option value="zephyr">👩 Zephyr - Vibrant and lively female voice</option>
                                        
                                        <!-- Male Voices -->
                                        <option value="achird">👨 Achird - Deep and authoritative male voice</option>
                                        <option value="algenib">👨 Algenib - Strong and confident male voice</option>
                                        <option value="algieba">👨 Algieba - Warm and engaging male voice</option>
                                        <option value="alnilam">👨 Alnilam - Clear and expressive male voice</option>
                                        <option value="charon">👨 Charon - Deep and authoritative male voice</option>
                                        <option value="enceladus">👨 Enceladus - Strong and confident male voice</option>
                                        <option value="fenrir">👨 Fenrir - Powerful and commanding male voice</option>
                                        <option value="lapetus">👨 Lapetus - Warm and engaging male voice</option>
                                        <option value="orus">👨 Orus - Clear and expressive male voice</option>
                                        <option value="puck">👨 Puck - Energetic and lively male voice</option>
                                        <option value="rasalgethi">👨 Rasalgethi - Deep and authoritative male voice</option>
                                        <option value="sadachbia">👨 Sadachbia - Strong and confident male voice</option>
                                        <option value="sadaltager">👨 Sadaltager - Warm and engaging male voice</option>
                                        <option value="schedar">👨 Schedar - Clear and expressive male voice</option>
                                        <option value="umbriel">👨 Umbriel - Deep and authoritative male voice</option>
                                        <option value="zubenelgenubi">👨 Zubenelgenubi - Strong and confident male voice</option>
                                    </optgroup>
                                </select>
                                <p class="mt-2 text-sm text-gray-300">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    {{ __('Choose the voice that will speak your translated text') }}. Gemini 2.5 Pro TTS offers better accent support and more natural pronunciation.
                                </p>
                                @error('voice')
                                    <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Upload Progress -->
                            <div id="uploadProgress" class="hidden">
                                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6 mb-6">
                                    <div class="flex items-center mb-4">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                        </div>
                                        <h4 class="text-lg font-bold text-blue-800">Upload in progress...</h4>
                                    </div>
                                    <div class="w-full bg-blue-200 rounded-full h-3 mb-2">
                                        <div id="progressBar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                    <p id="progressText" class="text-sm text-blue-700">Preparing upload...</p>
                                </div>
                            </div>

                            <!-- Style Instruction (Optional) -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <label for="style_instruction" class="block text-xl font-bold text-white">
                                        <i class="fas fa-palette mr-2 text-pink-400"></i>
                                        {{ __('Style Instruction (Optional)') }}
                                    </label>
                                    <a href="{{ route('style-presets.index') }}" target="_blank" class="text-blue-400 hover:text-blue-300 text-sm font-semibold">
                                        <i class="fas fa-cog mr-1"></i>
                                        Manage Presets
                                    </a>
                                </div>
                                
                                <!-- Preset Selector -->
                                <div class="mb-4">
                                    <select id="stylePresetSelect" 
                                            class="w-full px-6 py-3 text-base border-2 border-pink-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-pink-400 focus:border-pink-500 transition-all bg-white shadow-lg">
                                        <option value="">{{ __('Choose a preset or write custom...') }}</option>
                                        <option value="custom">✏️ {{ __('Custom (manual)') }}</option>
                                    </select>
                                    <p class="mt-2 text-xs text-gray-400">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Select a preset to auto-fill, or write your own below
                                    </p>
                                </div>
                                
                                <textarea id="style_instruction" 
                                        name="style_instruction" 
                                        rows="3"
                                        placeholder="e.g., 'Speak with enthusiasm and energy', 'Use a calm and soothing tone', 'Sound professional and authoritative' (up to 5000 characters)"
                                        class="w-full px-6 py-4 text-lg border-2 border-pink-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-pink-400 focus:border-pink-500 transition-all bg-white shadow-lg resize-none">{{ old('style_instruction') }}</textarea>
                                <p class="mt-2 text-sm text-gray-300">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    {{ __('Provide style instructions to customize how the voice should speak') }} (tone, emotion, pace, etc.). Only works with Gemini 2.5 Pro TTS voices.
                                </p>
                                @error('style_instruction')
                                    <p class="mt-2 text-sm text-red-400 flex items-center font-semibold">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end gap-6">
                                <a href="{{ route('audio.index') }}" class="btn-secondary text-lg px-8 py-4">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" id="submitButton" class="btn-primary text-xl px-12 py-4">
                                    <i class="fas fa-magic mr-2"></i>
                                    {{ __('Upload & Translate') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Sidebar -->
            <div class="lg:col-span-1">
                <div class="card border-2 border-gray-600 sticky top-24">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-indigo-400"></i>
                        {{ __('How does it work?') }}
                    </h3>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">{{ __('Upload Audio') }}</h4>
                                <p class="text-sm text-gray-300">{{ __('Upload your MP3, WAV or M4A file') }} (max 5 minutes)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">{{ __('Select Languages') }}</h4>
                                <p class="text-sm text-gray-300">{{ __('Choose the source and target language for translation') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">3</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">{{ __('AI Processing') }}</h4>
                                <p class="text-sm text-gray-300">{{ __('Whisper transcribes and translates automatically') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">4</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">{{ __('Download Result') }}</h4>
                                <p class="text-sm text-gray-300">{{ __('Download your translated audio file') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 p-4 bg-gradient-to-r from-gray-700 to-gray-600 rounded-xl">
                        <h4 class="font-semibold text-white mb-2">
                            <i class="fas fa-lightbulb mr-2 text-yellow-400"></i>
                            {{ __('Pro Tip') }}
                        </h4>
                        <p class="text-sm text-gray-300">
                            {{ __('For best results, use clear audio without background noise.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Audio create page JavaScript loaded');
    
    const dropZone = document.getElementById('dropZone');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const audioInput = document.getElementById('audio');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const submitButton = document.getElementById('submitButton');
    const uploadProgress = document.getElementById('uploadProgress');

    if (!dropZone || !audioInput) {
        console.error('Required elements not found');
        return;
    }

    // Drag and drop functionality
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('border-indigo-400');
        dropZone.classList.add('bg-indigo-900/20');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('border-indigo-400');
        dropZone.classList.remove('bg-indigo-900/20');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('border-indigo-400');
        dropZone.classList.remove('bg-indigo-900/20');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Set files to input element
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            audioInput.files = dt.files;
            handleFile(files[0]);
        }
    });

    // File input change
    audioInput.addEventListener('change', function(e) {
        console.log('File input changed', e.target.files);
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    // Form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            console.log('Form submitting');
            if (uploadProgress) {
                uploadProgress.classList.remove('hidden');
            }
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> {{ __("Uploading...") }}';
            }
        });
    }

    function handleFile(file) {
        console.log('Handling file:', file.name, file.type, file.size);
        
        const allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'audio/mp4a-latm', 'audio/x-mp4', 'audio/ogg', 'audio/flac', 'audio/x-flac', 'video/mp4'];
        const allowedExtensions = ['.mp3', '.wav', '.m4a', '.mp4', '.ogg', '.flac'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        const isValidMimeType = allowedTypes.includes(file.type);
        const isValidExtension = allowedExtensions.includes(fileExtension);
        
        if (!isValidMimeType && !isValidExtension) {
            console.log('Invalid file type:', file.type, fileExtension);
            alert('Only audio files are allowed (MP3, WAV, M4A, MP4, OGG, FLAC).');
            audioInput.value = '';
            return;
        }

        const maxSize = {{ config('audio.max_upload_size', 100) }} * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File is too large. Maximum {{ config("audio.max_upload_size", 100) }}MB allowed.');
            audioInput.value = '';
            return;
        }

        // Update UI
        if (fileName) fileName.textContent = file.name;
        if (fileSize) fileSize.textContent = formatFileSize(file.size);
        
        if (dropZoneContent) dropZoneContent.classList.add('hidden');
        if (fileInfo) fileInfo.classList.remove('hidden');
        
        console.log('File accepted:', file.name);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Style Preset Functionality
    const stylePresetSelect = document.getElementById('stylePresetSelect');
    const styleInstructionTextarea = document.getElementById('style_instruction');
    
    if (stylePresetSelect && styleInstructionTextarea) {
        // Load presets from API
        fetch('{{ route("style-presets.api") }}')
            .then(response => response.json())
            .then(presets => {
                presets.forEach(preset => {
                    const option = document.createElement('option');
                    option.value = preset.id;
                    option.textContent = (preset.is_default ? '⭐ ' : '📝 ') + preset.name;
                    option.dataset.instruction = preset.instruction;
                    stylePresetSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading presets:', error));
        
        // Handle preset selection
        stylePresetSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.dataset.instruction) {
                styleInstructionTextarea.value = selectedOption.dataset.instruction;
            } else if (this.value === '') {
                styleInstructionTextarea.value = '';
            }
            // 'custom' option leaves textarea as-is for manual input
        });
    }
});
</script>
@endsection
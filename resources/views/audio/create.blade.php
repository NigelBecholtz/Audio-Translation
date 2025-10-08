@extends('layouts.app')

@section('title', 'New Audio Translation')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 48px; font-weight: bold; color: #ffffff; margin-bottom: 16px;">New Audio Translation</h1>
            <p style="font-size: 20px; color: #ffffff;">Upload your audio file and let AI translate it for you</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Upload Form -->
            <div style="grid-column: span 2;">
                <div class="card" style="border: 3px solid #4b5563;">
                    <div style="padding: 32px;">
                        <form id="uploadForm" action="{{ route('audio.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                            @csrf
                            
                            <!-- Drag & Drop Upload Area -->
                            <div>
                                <label style="display: block; font-size: 24px; font-weight: bold; color: #ffffff; margin-bottom: 24px;">
                                    <i class="fas fa-upload" style="margin-right: 12px; color: #60a5fa;"></i>
                                    Upload Audio File
                                </label>
                                <div id="dropZone" style="position: relative; border: 4px dashed #60a5fa; border-radius: 16px; padding: 48px; text-align: center; background: linear-gradient(135deg, #1f2937 0%, #374151 100%); cursor: pointer; transition: all 0.2s;">
                                    <input type="file" 
                                           id="audio" 
                                           name="audio" 
                                           accept=".mp3,.wav,.m4a,.mp4"
                                           required
                                           style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                                    
                                    <div id="dropZoneContent">
                                        <div style="width: 96px; height: 96px; background: linear-gradient(135deg, #374151 0%, #4b5563 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; border: 4px solid #60a5fa;">
                                            <i class="fas fa-microphone" style="font-size: 36px; color: #60a5fa;"></i>
                                        </div>
                                        <p style="font-size: 24px; font-weight: bold; color: #ffffff; margin-bottom: 12px;">
                                            Drag your audio file here
                                        </p>
                                        <p style="font-size: 18px; color: #d1d5db; margin-bottom: 24px;">
                                            or click to select a file
                                        </p>
                                        <div style="display: inline-flex; align-items: center; padding: 16px 32px; background: #60a5fa; color: white; border-radius: 12px; font-size: 18px; font-weight: bold; box-shadow: 0 4px 12px rgba(96, 165, 250, 0.3);">
                                            <i class="fas fa-folder-open" style="margin-right: 12px;"></i>
                                            Select File
                                        </div>
                                    </div>
                                    
                                    <div id="fileInfo" class="hidden">
                                        <div class="flex items-center justify-center space-x-4">
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
                                
                                <div style="margin-top: 24px; display: flex; align-items: center; justify-content: center; gap: 32px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; background: #374151; padding: 8px 16px; border-radius: 8px; border: 2px solid #60a5fa;">
                                        <i class="fas fa-file-audio" style="margin-right: 8px; color: #60a5fa;"></i>
                                        <span style="font-size: 14px; font-weight: 600; color: #ffffff;">MP3, WAV, M4A</span>
                                    </div>
                                    <div style="display: flex; align-items: center; background: #374151; padding: 8px 16px; border-radius: 8px; border: 2px solid #10b981;">
                                        <i class="fas fa-weight" style="margin-right: 8px; color: #10b981;"></i>
                                        <span style="font-size: 14px; font-weight: 600; color: #ffffff;">Max {{ config('audio.max_upload_size', 100) }}MB</span>
                                    </div>
                                    <div style="display: flex; align-items: center; background: #374151; padding: 8px 16px; border-radius: 8px; border: 2px solid #8b5cf6;">
                                        <i class="fas fa-clock" style="margin-right: 8px; color: #8b5cf6;"></i>
                                        <span style="font-size: 14px; font-weight: 600; color: #ffffff;">Max 5 min</span>
                                    </div>
                                </div>
                                
                                @error('audio')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
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
                                    <i class="fas fa-language mr-3 text-blue-400" aria-hidden="true"></i>
                                    Source Language
                                </label>
                                <select id="source_language" 
                                        name="source_language" 
                                        required
                                        aria-label="Selecteer brontaal"
                                        class="w-full pl-6 pr-10 py-4 text-lg border-3 border-blue-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-300 focus:border-blue-500 transition-all bg-white shadow-lg">
                                    <option value="">Selecteer brontaal</option>
                                        
                                        <!-- Major Languages -->
                                        <optgroup label="🌍 Major Languages">
                                            <option value="en">🇺🇸 English</option>
                                            <option value="es">🇪🇸 Spanish</option>
                                            <option value="fr">🇫🇷 French</option>
                                            <option value="de">🇩🇪 German</option>
                                            <option value="it">🇮🇹 Italian</option>
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
                                        <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <!-- Target Language -->
                                <div>
                                <label for="target_language" class="block text-xl font-bold text-white mb-4">
                                    <i class="fas fa-flag mr-3 text-green-400" aria-hidden="true"></i>
                                    Target Language
                                </label>
                                <select id="target_language" 
                                        name="target_language" 
                                        required
                                        aria-label="Selecteer doeltaal"
                                        class="w-full pl-6 pr-10 py-4 text-lg border-3 border-green-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-green-300 focus:border-green-500 transition-all bg-white shadow-lg">
                                    <option value="">Selecteer doeltaal</option>
                                        
                                        <!-- Major Languages -->
                                        <optgroup label="🌍 Major Languages">
                                            <option value="en">🇺🇸 English</option>
                                            <option value="es">🇪🇸 Spanish</option>
                                            <option value="fr">🇫🇷 French</option>
                                            <option value="de">🇩🇪 German</option>
                                            <option value="it">🇮🇹 Italian</option>
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
                                        <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
                                            <i class="fas fa-exclamation-circle mr-2"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Voice Selection -->
                            <div>
                                <label for="voice" class="block text-xl font-bold text-white mb-4">
                                    <i class="fas fa-microphone mr-3 text-purple-400" aria-hidden="true"></i>
                                    Voice Selection
                                </label>
                                <select id="voice" 
                                        name="voice" 
                                        required
                                        aria-label="Selecteer stem voor vertaling"
                                        class="w-full pl-6 pr-10 py-4 text-lg border-3 border-purple-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-purple-300 focus:border-purple-500 transition-all bg-white shadow-lg">
                                    <option value="">Selecteer stem voor vertaling</option>
                                    
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
                                <p class="mt-2 text-sm text-white">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Choose the voice that will speak your translated text. Gemini 2.5 Pro TTS offers better accent support and more natural pronunciation.
                                </p>
                                @error('voice')
                                    <p class="mt-2 text-sm text-red-600 flex items-center font-bold">
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
                                <a href="{{ route('audio.index') }}" class="btn-secondary" style="font-size: 18px; padding: 16px 32px;">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancel
                                </a>
                                <button type="submit" id="submitButton" class="btn-primary" style="font-size: 20px; padding: 16px 48px;">
                                    <i class="fas fa-magic"></i>
                                    Upload & Translate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-gray-800 shadow-2xl rounded-2xl border-2 border-gray-600 p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-indigo-400"></i>
                        How does it work?
                    </h3>
                    
                    <div class="space-y-6">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">1</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Upload Audio</h4>
                                <p class="text-sm text-white">Upload your MP3, WAV or M4A file (max 5 minutes)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">2</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Select Languages</h4>
                                <p class="text-sm text-white">Choose the source and target language for translation</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">3</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">AI Processing</h4>
                                <p class="text-sm text-white">Whisper transcribes and translates automatically</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">4</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Download Result</h4>
                                <p class="text-sm text-white">Download your translated audio file</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 p-4 bg-gradient-to-r from-gray-700 to-gray-600 rounded-xl">
                        <h4 class="font-semibold text-white mb-2">
                            <i class="fas fa-lightbulb mr-2 text-yellow-400"></i>
                            Pro Tip
                        </h4>
                        <p class="text-sm text-white">
                            For best results, use clear audio without background noise.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript loaded successfully!');
    
    const dropZone = document.getElementById('dropZone');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const audioInput = document.getElementById('audio');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const submitButton = document.getElementById('submitButton');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    // Drag and drop functionality
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-indigo-400', 'bg-indigo-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-indigo-400', 'bg-indigo-50');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    // File input change
    audioInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    // Simple form submission - just show progress
    uploadForm.addEventListener('submit', function(e) {
        console.log('Form submit event triggered!');
        
        // Show progress immediately
        uploadProgress.classList.remove('hidden');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        // Let the form submit naturally
        console.log('Form submitting to:', uploadForm.action);
    });

    function handleFile(file) {
        // Get allowed types from backend config
        const allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'audio/mp4a-latm', 'audio/x-mp4', 'audio/ogg', 'audio/flac', 'audio/x-flac'];
        const allowedExtensions = ['.mp3', '.wav', '.m4a', '.mp4', '.ogg', '.flac'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        // Check if either MIME type OR file extension is allowed
        const isValidMimeType = allowedTypes.includes(file.type);
        const isValidExtension = allowedExtensions.includes(fileExtension);
        
        if (!isValidMimeType && !isValidExtension) {
            console.log('File type:', file.type, 'Extension:', fileExtension);
            alert('Only audio files are allowed (MP3, WAV, M4A, MP4, OGG, FLAC).');
            return;
        }

        // Validate file size (from backend config - 100MB upload, will compress if >25MB)
        const maxSize = {{ config('audio.max_upload_size', 100) }} * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File is too large. Maximum {{ config("audio.max_upload_size", 100) }}MB allowed. Files over 25MB will be automatically compressed.');
            return;
        }

        // Show file info
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        
        dropZoneContent.classList.add('hidden');
        fileInfo.classList.remove('hidden');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
@endsection


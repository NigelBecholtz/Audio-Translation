<?php

$defaultLanguages = [
    'en-us' => 'English (US)',
    'en-gb' => 'English (UK)',
    'en-au' => 'English (Australia)',
    'en-ca' => 'English (Canada)',
    'en-in' => 'English (India)',
    'en' => 'English (General)',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'nl' => 'Dutch',
    'it' => 'Italian',
    'pt' => 'Portuguese',
    'ru' => 'Russian',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'zh' => 'Chinese',
    'ar' => 'Arabic',
    'hi' => 'Hindi',
    'sv' => 'Swedish',
    'sq' => 'Albanian',
    'bg' => 'Bulgarian',
    'sk' => 'Slovak',
    'lv' => 'Latvian',
    'fi' => 'Finnish',
    'el' => 'Greek',
    'ro' => 'Romanian',
    'ca' => 'Catalan',
];

$defaultVoices = [
    // Female voices
    'achernar' => 'Achernar - Clear and expressive female voice',
    'aoede' => 'Aoede - Warm and engaging female voice',
    'autonoe' => 'Autonoe - Soft and gentle female voice',
    'callirrhoe' => 'Callirrhoe - Bright and energetic female voice',
    'despina' => 'Despina - Smooth and professional female voice',
    'erinome' => 'Erinome - Wise and calm female voice',
    'gacrux' => 'Gacrux - Vibrant and lively female voice',
    'kore' => 'Kore - Balanced and versatile female voice',
    'laomedeia' => 'Laomedeia - Warm and engaging female voice',
    'leda' => 'Leda - Clear and expressive female voice',
    'pulcherrima' => 'Pulcherrima - Bright and energetic female voice',
    'sulafat' => 'Sulafat - Soft and gentle female voice',
    'vindemiatrix' => 'Vindemiatrix - Smooth and professional female voice',
    'zephyr' => 'Zephyr - Vibrant and lively female voice',
    // Male voices
    'achird' => 'Achird - Deep and authoritative male voice',
    'algenib' => 'Algenib - Strong and confident male voice',
    'algieba' => 'Algieba - Warm and engaging male voice',
    'alnilam' => 'Alnilam - Clear and expressive male voice',
    'charon' => 'Charon - Deep and authoritative male voice',
    'enceladus' => 'Enceladus - Strong and confident male voice',
    'fenrir' => 'Fenrir - Powerful and commanding male voice',
    'lapetus' => 'Lapetus - Warm and engaging male voice',
    'orus' => 'Orus - Clear and expressive male voice',
    'puck' => 'Puck - Energetic and lively male voice',
    'rasalgethi' => 'Rasalgethi - Deep and authoritative male voice',
    'sadachbia' => 'Sadachbia - Strong and confident male voice',
    'sadaltager' => 'Sadaltager - Warm and engaging male voice',
    'schedar' => 'Schedar - Clear and expressive male voice',
    'umbriel' => 'Umbriel - Deep and authoritative male voice',
    'zubenelgenubi' => 'Zubenelgenubi - Strong and confident male voice',
];

return [
    /*
    |--------------------------------------------------------------------------
    | Audio Processing Configuration
    |--------------------------------------------------------------------------
    */

    'max_file_size' => env('AUDIO_MAX_FILE_SIZE', 25), // MB (OpenAI Whisper limit)
    'max_upload_size' => env('AUDIO_MAX_UPLOAD_SIZE', 100), // MB (Will be compressed if needed)
    'max_duration' => env('AUDIO_MAX_DURATION', 600), // seconds
    'max_execution_time' => env('AUDIO_MAX_EXECUTION_TIME', 600), // seconds
    'max_input_time' => env('AUDIO_MAX_INPUT_TIME', 600), // seconds
    'memory_limit' => env('AUDIO_MEMORY_LIMIT', '512M'),
    
    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    */
    
    'storage_disk' => env('AUDIO_STORAGE_DISK', 'public'),
    'cleanup_after_days' => env('AUDIO_CLEANUP_AFTER_DAYS', 30), // 0 = never

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    */

    'languages' => $defaultLanguages,
    'available_languages' => $defaultLanguages,
    'available_voices' => $defaultVoices,

    'language_codes' => 'en-us,en-gb,en-au,en-ca,en-in,en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */

    'allowed_types' => explode(',', env('AUDIO_ALLOWED_TYPES', 'mp3,wav,m4a,mp4,ogg,flac')),
    'allowed_mimes' => ['mp3', 'wav', 'm4a', 'mp4', 'ogg', 'flac'], // For validation
    'max_text_length' => env('AUDIO_MAX_TEXT_LENGTH', 50000),
    'max_style_instruction_length' => env('AUDIO_MAX_STYLE_LENGTH', 5000),

    /*
    |--------------------------------------------------------------------------
    | TTS Settings
    |--------------------------------------------------------------------------
    */

    'tts_chunk_size' => env('TTS_CHUNK_SIZE', 900), // bytes - Gemini TTS limit
    'tts_chunk_delay' => env('TTS_CHUNK_DELAY', 2), // seconds between chunks
];


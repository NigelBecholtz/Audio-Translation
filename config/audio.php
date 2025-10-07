<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audio Processing Configuration
    |--------------------------------------------------------------------------
    */

    'max_file_size' => env('AUDIO_MAX_FILE_SIZE', 50), // MB
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

    'languages' => [
        'en' => 'English',
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
        'ca' => 'Catalan'
    ],

    'language_codes' => 'en,es,fr,de,it,pt,ru,ja,ko,zh,ar,hi,nl,sv,da,no,fi,pl,cs,sk,hu,ro,bg,hr,sl,el,tr,uk,lv,lt,et,ca,eu,th,vi,id,ms,tl,bn,ta,te,ml,kn,gu,pa,ur,si,my,km,lo,mn,af,sw,am,sq,hy,az,ka,he,fa,ps,ne',

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


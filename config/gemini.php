<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Google Gemini API settings. You can find your
    | API key in the Google AI Studio at https://aistudio.google.com/
    |
    */

    'api_key' => env('GEMINI_API_KEY'),
    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    /*
    |--------------------------------------------------------------------------
    | TTS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Text-to-Speech functionality using Gemini 2.5 Pro
    |
    */

    'tts' => [
        'model' => 'gemini-2.5-pro-tts',
        'default_voice' => 'Kore',
        'max_text_length' => 900, // Gemini TTS byte limit
        'max_prompt_length' => 900, // Style instruction byte limit
        'supported_languages' => [
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
        'voice_mapping' => [
            // Gebruik 'Aoede' - universele voice die alle talen ondersteunt
            'en' => 'Aoede',
            'es' => 'Aoede',
            'fr' => 'Aoede',
            'de' => 'Aoede',
            'nl' => 'Aoede',
            'it' => 'Aoede',
            'pt' => 'Aoede',
            'ru' => 'Aoede',
            'ja' => 'Aoede',
            'ko' => 'Aoede',
            'zh' => 'Aoede',
            'ar' => 'Aoede',
            'hi' => 'Aoede',
            'sv' => 'Aoede',
            'sq' => 'Aoede',
            'bg' => 'Aoede',
            'sk' => 'Aoede',
            'lv' => 'Aoede',
            'fi' => 'Aoede',
            'el' => 'Aoede',
            'ro' => 'Aoede',
            'ca' => 'Aoede'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests in seconds
    |
    */

    'timeout' => env('GEMINI_TIMEOUT', 120), // Increased to 2 minutes for better reliability

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for Gemini TTS API calls to prevent quota issues
    |
    */

    'rate_limit' => [
        'max_attempts' => env('GEMINI_RATE_LIMIT_ATTEMPTS', 60), // Max requests per time window
        'decay_minutes' => env('GEMINI_RATE_LIMIT_DECAY', 1), // Time window in minutes
    ],
];

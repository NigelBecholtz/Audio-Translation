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
            'en' => 'Kore',
            'es' => 'Puck',
            'fr' => 'Puck',
            'de' => 'Puck',
            'nl' => 'Puck',
            'it' => 'Puck',
            'pt' => 'Puck',
            'ru' => 'Puck',
            'ja' => 'Puck',
            'ko' => 'Puck',
            'zh' => 'Puck',
            'ar' => 'Puck',
            'hi' => 'Puck',
            'sv' => 'Puck',
            'sq' => 'Puck',
            'bg' => 'Puck',
            'sk' => 'Puck',
            'lv' => 'Puck',
            'fi' => 'Puck',
            'el' => 'Puck',
            'ro' => 'Puck',
            'ca' => 'Puck'
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
];

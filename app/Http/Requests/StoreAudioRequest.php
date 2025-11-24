<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAudioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canMakeTranslation();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxUploadSize = config('audio.max_upload_size', 100);
        $languageCodes = explode(',', config('audio.language_codes'));
        
        return [
            'audio' => [
                'required',
                'file',
                'mimes:mp3,wav,m4a,mp4,ogg,flac',
                'max:' . ($maxUploadSize * 1024), // Convert MB to KB
                function ($attribute, $value, $fail) {
                    // Extra MIME validation with finfo
                    if (!$value->isValid()) {
                        $fail('The file is corrupted or invalid.');
                        return;
                    }
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $value->getRealPath());
                    finfo_close($finfo);
                    
                    $allowedMimes = [
                        'audio/mpeg',
                        'audio/mp3', 
                        'audio/wav',
                        'audio/x-wav',
                        'audio/wave',
                        'audio/m4a',
                        'audio/x-m4a',
                        'audio/mp4',
                        'audio/x-mp4',
                        'video/mp4', // Allow MP4 video (will extract audio)
                        'audio/ogg',
                        'audio/flac',
                        'audio/x-flac',
                        'application/octet-stream' // Some systems report this for audio files
                    ];
                    
                    if (!in_array($mimeType, $allowedMimes)) {
                        $fail("This file type ($mimeType) is not supported. Please upload a valid audio file.");
                    }
                },
            ],
            'source_language' => [
                'required',
                'string',
                Rule::in($languageCodes)
            ],
            'target_language' => [
                'required',
                'string',
                Rule::in($languageCodes),
                // Allow same language for accent improvement (e.g., English to English)
                // Removed 'different:source_language' rule to enable accent improvement feature
            ],
            'voice' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z]+$/' // Only letters
            ],
            'style_instruction' => [
                'nullable',
                'string',
                'max:' . config('audio.max_style_instruction_length', 5000)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $maxUploadSize = config('audio.max_upload_size', 100);
        
        return [
            'audio.required' => 'Please upload an audio file to translate.',
            'audio.file' => 'The uploaded file is invalid.',
            'audio.mimes' => 'Only MP3, WAV, M4A, MP4, OGG and FLAC files are allowed.',
            'audio.max' => "The audio file must not exceed {$maxUploadSize}MB.",
            
            'source_language.required' => 'Please select the source language of your audio.',
            'source_language.in' => 'The selected source language is invalid.',
            
            'target_language.required' => 'Please select the target language for translation or accent improvement.',
            'target_language.in' => 'The selected target language is invalid.',
            
            'voice.required' => 'Please select a voice for the translated audio.',
            'voice.regex' => 'The selected voice is invalid.',
            
            'style_instruction.max' => 'The style instruction must not exceed :max characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'audio' => 'audio file',
            'source_language' => 'source language',
            'target_language' => 'target language',
            'voice' => 'voice',
            'style_instruction' => 'style instruction',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            __('You have no more translations available. Purchase credits to continue!')
        );
    }

    /**
     * Get base language code (e.g., 'en-gb' -> 'en', 'es' -> 'es')
     *
     * @param string $languageCode
     * @return string
     */
    private function getBaseLanguageCode(string $languageCode): string
    {
        $code = strtolower(trim($languageCode));
        
        // Extract base language code if it's in format 'xx-XX' or 'xx_XX'
        if (preg_match('/^([a-z]{2})(?:[-_][a-z]{2,})?$/i', $code, $matches)) {
            return strtolower($matches[1]);
        }
        
        return $code;
    }
}
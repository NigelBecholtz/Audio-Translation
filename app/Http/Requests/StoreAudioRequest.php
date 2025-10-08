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
        // Check if user can make translation
        return $this->user()->canMakeTranslation();
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
                        $fail('Het bestand is beschadigd of ongeldig.');
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
                        $fail("Dit bestandstype ($mimeType) wordt niet ondersteund. Upload een geldig audiobestand.");
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
                'different:source_language'
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
            'audio.required' => 'Upload een audiobestand om te vertalen.',
            'audio.file' => 'Het geüploade bestand is ongeldig.',
            'audio.mimes' => 'Alleen MP3, WAV, M4A, MP4, OGG en FLAC bestanden zijn toegestaan.',
            'audio.max' => "Het audiobestand mag maximaal {$maxUploadSize}MB zijn.",
            
            'source_language.required' => 'Selecteer de brontaal van je audio.',
            'source_language.in' => 'De geselecteerde brontaal is ongeldig.',
            
            'target_language.required' => 'Selecteer de doeltaal voor de vertaling.',
            'target_language.in' => 'De geselecteerde doeltaal is ongeldig.',
            'target_language.different' => 'De doeltaal moet verschillen van de brontaal.',
            
            'voice.required' => 'Selecteer een stem voor de vertaalde audio.',
            'voice.regex' => 'De geselecteerde stem is ongeldig.',
            
            'style_instruction.max' => 'De stijlinstructie mag maximaal :max karakters bevatten.',
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
            'audio' => 'audiobestand',
            'source_language' => 'brontaal',
            'target_language' => 'doeltaal',
            'voice' => 'stem',
            'style_instruction' => 'stijlinstructie',
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
            'Je hebt geen vertalingen meer over. Koop credits om door te gaan!'
        );
    }
}
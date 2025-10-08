<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTextToAudioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Temporarily always return true for debugging
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $languageCodes = explode(',', config('audio.language_codes'));
        
        return [
            'text_content' => [
                'required',
                'string',
                'min:10',
                'max:' . config('audio.max_text_length', 50000),
            ],
            'language' => [
                'required',
                'string',
                Rule::in($languageCodes)
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
        return [
            'text_content.required' => 'Voer de tekst in die je naar audio wilt converteren.',
            'text_content.min' => 'De tekst moet minimaal :min karakters bevatten.',
            'text_content.max' => 'De tekst mag maximaal :max karakters bevatten.',
            
            'language.required' => 'Selecteer de taal voor de audio.',
            'language.in' => 'De geselecteerde taal is ongeldig.',
            
            'voice.required' => 'Selecteer een stem voor de audio.',
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
            'text_content' => 'tekst',
            'language' => 'taal',
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
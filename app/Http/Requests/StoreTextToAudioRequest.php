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
        return auth()->check() && auth()->user()->canMakeTranslation();
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
            'text_content.required' => 'Please enter the text you want to convert to audio.',
            'text_content.min' => 'The text must be at least :min characters.',
            'text_content.max' => 'The text must not exceed :max characters.',
            
            'language.required' => 'Please select the language for the audio.',
            'language.in' => 'The selected language is invalid.',
            
            'voice.required' => 'Please select a voice for the audio.',
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
            'text_content' => 'text',
            'language' => 'language',
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
}
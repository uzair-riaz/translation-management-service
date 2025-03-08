<?php

namespace App\Http\Requests;

class StoreTranslationRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'key' => 'required|string|max:255',
            'value' => 'required|string',
            'locale' => 'required|string|max:10',
            'tags' => 'required|array|min:1',
            'tags.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'key.required' => 'The translation key is required.',
            'key.max' => 'The translation key may not be greater than 255 characters.',
            'value.required' => 'The translation value is required.',
            'locale.required' => 'The locale is required.',
            'locale.max' => 'The locale may not be greater than 10 characters.',
            'tags.required' => 'At least one tag is required for each translation.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.min' => 'At least one tag is required for each translation.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag may not be greater than 255 characters.',
        ];
    }
} 
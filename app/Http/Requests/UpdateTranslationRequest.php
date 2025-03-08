<?php

namespace App\Http\Requests;

class UpdateTranslationRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'value' => 'required|string',
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
            'value.required' => 'The translation value is required.',
            'tags.required' => 'At least one tag is required for each translation.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.min' => 'At least one tag is required for each translation.',
            'tags.*.string' => 'Each tag must be a string.',
            'tags.*.max' => 'Each tag may not be greater than 255 characters.',
        ];
    }
} 
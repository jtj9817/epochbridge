<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertDateRequest extends FormRequest
{
    /**
     * Supported input types matching the landing page surface.
     */
    public const INPUT_TYPES = [
        'gregorian',
        'jalali',
        'unix',
        'iso8601',
        'jd',
        'mjd',
        'hebrew',
        'islamic',
        'french_republican',
        'chinese',
        'japanese',
        'coptic',
        'egyptian',
        'ethiopian',
        'babylonian',
        'assyrian',
        'macedonian',
        'mayan',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'string', 'max:100'],
            'input_type' => ['required', 'string', Rule::in(self::INPUT_TYPES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'A date value is required.',
            'date.max' => 'The date value is too long.',
            'input_type.required' => 'An input type is required.',
            'input_type.in' => 'The selected input type is not supported.',
        ];
    }
}

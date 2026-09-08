<?php

namespace App\Http\Requests\Frontend;

use App\Rules\MaxPayloadBytes;
use Illuminate\Foundation\Http\FormRequest;

class StoreSavedConversionRequest extends FormRequest
{
    /**
     * Upper bound for the client-supplied conversion payload; real payloads
     * are far smaller, but the cap keeps rows bounded.
     */
    private const MAX_CONVERSION_DATA_BYTES = 65536;

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
            'original_input' => ['required', 'string', 'max:255'],
            'conversion_data' => ['required', 'array', 'max:128', new MaxPayloadBytes(self::MAX_CONVERSION_DATA_BYTES)],
            'note' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'conversion_data.required' => 'Conversion data is required.',
            'conversion_data.max' => 'Conversion data has too many entries.',
        ];
    }
}

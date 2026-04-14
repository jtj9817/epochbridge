<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class MigrateGuestConversionsRequest extends FormRequest
{
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
            'items' => ['required', 'array', 'max:3'],
            'items.*.original_input' => ['required', 'string', 'max:255'],
            'items.*.conversion_data' => ['required', 'array'],
            'items.*.note' => ['nullable', 'string', 'max:2048'],
            'items.*.created_at' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Guest conversion data is required.',
            'items.max' => 'A maximum of 3 guest conversions can be migrated at once.',
            'items.*.original_input.required' => 'Each conversion must have an original input value.',
            'items.*.conversion_data.required' => 'Each conversion must include conversion data.',
        ];
    }
}

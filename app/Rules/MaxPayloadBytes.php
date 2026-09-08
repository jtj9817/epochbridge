<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use JsonException;

/**
 * Caps the serialized JSON byte size of a payload. Also rejects values too
 * deeply nested to encode.
 */
class MaxPayloadBytes implements ValidationRule
{
    public function __construct(private readonly int $maxBytes) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $fail("The {$attribute} field contains data that cannot be encoded.");

            return;
        }

        if (strlen($encoded) > $this->maxBytes) {
            $fail("The {$attribute} field must not exceed {$this->maxBytes} bytes.");
        }
    }
}

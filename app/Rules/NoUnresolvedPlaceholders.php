<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A reply still containing "[[DA COMPLETARE: …]]" (saved-reply variable without value, AI gap) must not go out.
 */
class NoUnresolvedPlaceholders implements ValidationRule
{
    public const MARKER = '[[DA COMPLETARE';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::contains($value)) {
            $fail('Il testo contiene ancora dei punti da completare ([[DA COMPLETARE…]]): sostituiscili prima di inviare.');
        }
    }

    public static function contains(string $text): bool
    {
        return stripos($text, self::MARKER) !== false;
    }
}

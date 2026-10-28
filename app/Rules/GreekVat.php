<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Έλεγχος εγκυρότητας ελληνικού ΑΦΜ (9 ψηφία, ψηφίο ελέγχου modulo 11).
 * Πιάνει τα τυπογραφικά λάθη πριν φτάσουν στην τιμολόγηση.
 */
class GreekVat implements ValidationRule
{
    public static function isValid(string $vat): bool
    {
        if (! preg_match('/^\d{9}$/', $vat) || $vat === '000000000') {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $vat[$i] * (2 ** (8 - $i));
        }

        return ($sum % 11) % 10 === (int) $vat[8];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid(preg_replace('/\D/', '', (string) $value))) {
            $fail('Ο ΑΦΜ δεν είναι έγκυρος. Ελέγξτε τα 9 ψηφία.');
        }
    }
}

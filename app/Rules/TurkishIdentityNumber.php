<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TurkishIdentityNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $number = preg_replace('/\D/', '', (string) $value);

        if (strlen($number) !== 11 || $number[0] === '0') {
            $fail('Geçerli bir T.C. kimlik numarası girin.');

            return;
        }

        $digits = array_map('intval', str_split($number));
        $odd = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $even = $digits[1] + $digits[3] + $digits[5] + $digits[7];

        if (((($odd * 7) - $even) % 10 + 10) % 10 !== $digits[9]
            || array_sum(array_slice($digits, 0, 10)) % 10 !== $digits[10]) {
            $fail('Geçerli bir T.C. kimlik numarası girin.');
        }
    }
}

<?php

namespace App\Rules;

use App\Support\ListingDecimal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

class ListingDecimalPrecision implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            ListingDecimal::normalize($value, $attribute);
        } catch (ValidationException $exception) {
            $fail($exception->errors()[$attribute][0]);
        }
    }
}

<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Validation\ValidationException;

class ListingDecimal
{
    public const FIELDS = ['price', 'square_meters', 'net_square_meters', 'deposit_price', 'dues_price'];

    public static function normalize(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $number = (string) BigDecimal::of((string) $value)->stripTrailingZeros();
        } catch (MathException $exception) {
            throw ValidationException::withMessages([$field => 'Geçerli bir sayısal değer giriniz.']);
        }

        [$integer, $fraction] = array_pad(explode('.', ltrim($number, '-'), 2), 2, '');

        // DECIMAL(65, 30): reject excess precision before the database can round it.
        if (strlen($integer) > 35 || strlen($fraction) > 30) {
            throw ValidationException::withMessages([
                $field => 'En fazla 35 tam sayı ve 30 ondalık basamak girilebilir. Değer yuvarlanmadan kaydedilmelidir.',
            ]);
        }

        return $number;
    }

    public static function format(mixed $value): string
    {
        $number = (string) BigDecimal::of((string) $value)->stripTrailingZeros();
        [$integer, $fraction] = array_pad(explode('.', $number, 2), 2, '');
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);

        return $integer.($fraction !== '' ? ','.$fraction : '');
    }
}

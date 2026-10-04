<?php

namespace App\Casts;

use App\Support\ListingDecimal;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ListingDecimalCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return ListingDecimal::normalize($value, $key);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return ListingDecimal::normalize($value, $key);
    }
}

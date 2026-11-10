<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentPeriodRate extends Model
{
    protected $guarded = [];

    protected $casts = ['period' => 'date', 'published_on' => 'date', 'rate' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $rate) {
            $host = parse_url($rate->source_url, PHP_URL_HOST);
            if (! is_string($host) || ! in_array($host, ['data.tuik.gov.tr', 'veriportali.tuik.gov.tr', 'www.tuik.gov.tr', 'tuik.gov.tr'], true) || parse_url($rate->source_url, PHP_URL_SCHEME) !== 'https' || $rate->period->day !== 1 || $rate->published_on->isFuture() || $rate->period->startOfMonth()->gt(now()->startOfMonth()) || $rate->published_on->gt($rate->period->copy()->endOfMonth()) || $rate->rate < 0 || $rate->rate > 999) {
                throw ValidationException::withMessages(['data.source_url' => 'Yayımlanmış dönem için resmî TÜİK HTTPS kaynağı, yayın tarihi ve geçerli oran girin.']);
            }
            $rate->updated_by = auth()->id();
        });
        static::saved(function (self $rate) {
            DB::table('rent_rate_logs')->insert(['rate_id' => $rate->id, 'user_id' => auth()->id(), 'snapshot' => json_encode($rate->only(['period', 'rate', 'source_url', 'published_on'])), 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Cache (Redis) destekli Setting alma metodu.
     */
    public static function get(string $key, $default = null)
    {
        // 1 Gün (86400 sn) boyunca Redis'te tut, yoksa DB'den çekip Redis'e at
        return Cache::remember("site_setting_{$key}", 86400, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Değişiklik olduğunda Redis önbelleğini temizleme metodu.
     */
    public static function clearCache(string $key): void
    {
        Cache::forget("site_setting_{$key}");
    }
}

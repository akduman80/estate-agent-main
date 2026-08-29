<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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

    public static function getSecret(string $key): ?string
    {
        $encrypted = static::get($key);

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function putSecret(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => Crypt::encryptString($value)]);
        static::clearCache($key);
    }
}

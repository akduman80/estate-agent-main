<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OsmaniyeWeatherService
{
    public const URL = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';

    public function current(): array
    {
        $data = Cache::store('file')->remember('osmaniye-weather-v1', 1800, function () {
            try {
                $response = Http::withUserAgent('zydemlak.com/1.0 https://www.zydemlak.com/iletisim')
                    ->acceptJson()->connectTimeout(2)->timeout(4)->get(self::URL, ['lat' => '37.0742', 'lon' => '36.2478']);
                if (! $response->successful()) {
                    return [];
                }
                $series = $response->json('properties.timeseries', []);
                foreach ($series as $entry) {
                    $time = Carbon::parse($entry['time']);
                    if ($time->gt(now()) || $time->lt(now()->subHours(2))) {
                        continue;
                    }
                    $details = data_get($entry, 'data.instant.details', []);
                    foreach (['air_temperature', 'relative_humidity', 'wind_speed'] as $key) {
                        if (! isset($details[$key]) || ! is_numeric($details[$key])) {
                            return [];
                        }
                    }
                    $symbol = data_get($entry, 'data.next_1_hours.summary.symbol_code', 'cloudy');
                    $label = match (true) {
                        str_contains($symbol, 'thunder') => 'Gök gürültülü',
                        str_contains($symbol, 'snow'), str_contains($symbol, 'sleet') => 'Kar yağışlı',
                        str_contains($symbol, 'rain') => 'Yağmurlu',
                        str_contains($symbol, 'fog') => 'Sisli',
                        str_contains($symbol, 'partlycloudy'), str_contains($symbol, 'fair') => 'Parçalı bulutlu',
                        str_contains($symbol, 'clearsky') => 'Açık',
                        default => 'Bulutlu',
                    };
                    return ['temperature' => (int) round($details['air_temperature']), 'humidity' => (int) round($details['relative_humidity']), 'wind' => (int) round($details['wind_speed'] * 3.6), 'label' => $label, 'time' => $time->toIso8601String()];
                }
            } catch (\Throwable) {
                // Do not replace missing weather with invented readings.
            }
            return [];
        });

        return $data && Carbon::parse($data['time'])->gte(now()->subHours(2)) ? $data : [];
    }
}

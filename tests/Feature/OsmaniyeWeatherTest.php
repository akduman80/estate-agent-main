<?php

namespace Tests\Feature;

use App\Services\OsmaniyeWeatherService;
use App\Services\OsmaniyePrayerTimesService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsmaniyeWeatherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.file' => ['driver' => 'array']]);
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(10, 30));
        Http::preventStrayRequests();
    }

    private function response(string $time): array
    {
        return ['properties' => ['timeseries' => [['time' => $time, 'data' => ['instant' => ['details' => ['air_temperature' => 27.6, 'relative_humidity' => 45, 'wind_speed' => 3]], 'next_1_hours' => ['summary' => ['symbol_code' => 'partlycloudy_day']]]]]]];
    }

    public function test_weather_is_cached_converted_and_attributed(): void
    {
        Http::fake([OsmaniyeWeatherService::URL.'*' => Http::response($this->response(now()->startOfHour()->toIso8601String()))]);
        $service = app(OsmaniyeWeatherService::class);
        $data = $service->current();
        $this->assertSame(28, $data['temperature']);
        $this->assertSame(11, $data['wind']);
        $this->assertSame('Parçalı bulutlu', $data['label']);
        $service->current();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->header('User-Agent')[0], 'zydemlak.com'));
    }

    public function test_stale_and_failed_weather_do_not_show_fake_readings(): void
    {
        Http::fake([OsmaniyeWeatherService::URL.'*' => Http::response($this->response(now()->subDay()->toIso8601String()))]);
        $this->assertSame([], app(OsmaniyeWeatherService::class)->current());
    }

    public function test_network_failure_is_handled(): void
    {
        Http::fake([OsmaniyeWeatherService::URL.'*' => Http::failedConnection()]);
        $this->assertSame([], app(OsmaniyeWeatherService::class)->current());
    }

    public function test_tomorrows_prayers_are_selected_using_their_own_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 22:00', 'Europe/Istanbul'));
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::response('<table><tr><td>17 Ekim 2026 Cumartesi</td><td>Hicri</td><td>05:18</td><td>06:37</td><td>12:25</td><td>15:33</td><td>18:04</td><td>19:18</td></tr></table>')]);
        $result = app(OsmaniyePrayerTimesService::class)->today(1);
        $this->assertSame('2026-10-17', $result['date']);
        $this->assertSame('05:18', $result['times']['İmsak']);
    }
}

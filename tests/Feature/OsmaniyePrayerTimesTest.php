<?php

namespace Tests\Feature;

use App\Services\OsmaniyePrayerTimesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsmaniyePrayerTimesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.file' => ['driver' => 'array']]);
        Carbon::setTestNow(Carbon::parse('2026-10-16 20:59:00', 'UTC'));
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function row(string $date, string $first = '05:17'): string
    {
        return '<table><tr><td>'.$date.'</td><td>5 Cemaziyelevvel 1448</td><td>'.$first.'</td><td>06:36</td><td>12:26</td><td>15:34</td><td>18:05</td><td>19:19</td></tr></table>';
    }

    public function test_official_times_are_selected_by_date_and_cached(): void
    {
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::response($this->row('15 Ekim 2026 Perşembe').$this->row('16 Ekim 2026 Cuma'))]);
        $service = app(OsmaniyePrayerTimesService::class);
        $result = $service->today();
        $this->assertSame('2026-10-16', $result['date']);
        $this->assertSame('16 Ekim 2026 Cuma', $result['label']);
        $this->assertSame(['İmsak' => '05:17', 'Güneş' => '06:36', 'Öğle' => '12:26', 'İkindi' => '15:34', 'Akşam' => '18:05', 'Yatsı' => '19:19'], $result['times']);
        $service->today();
        Http::assertSentCount(1);
    }

    public function test_istanbul_midnight_does_not_reuse_yesterdays_times(): void
    {
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::response($this->row('16 Ekim 2026 Cuma'))]);
        $service = app(OsmaniyePrayerTimesService::class);
        $this->assertNotEmpty($service->today()['times']);
        Carbon::setTestNow(Carbon::parse('2026-10-16 21:01:00', 'UTC'));
        $this->assertSame('2026-10-17', $service->today()['date']);
        $this->assertSame([], $service->today()['times']);
        Http::assertSentCount(2);
    }

    public function test_unavailable_source_retries_after_short_cache_without_inventing_times(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 20:50:00', 'UTC'));
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::sequence()->pushStatus(503)->push($this->row('16 Ekim 2026 Cuma'))]);
        $service = app(OsmaniyePrayerTimesService::class);
        $this->assertSame([], $service->today()['times']);
        $this->assertSame([], $service->today()['times']);
        Http::assertSentCount(1);
        $this->travel(301)->seconds();
        $this->assertNotEmpty($service->today()['times']);
        Http::assertSentCount(2);
    }

    public function test_malformed_times_are_rejected(): void
    {
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::response($this->row('16 Ekim 2026 Cuma', '25:88'))]);
        $this->assertSame([], app(OsmaniyePrayerTimesService::class)->today()['times']);
    }

    public function test_connection_failure_returns_an_empty_result(): void
    {
        Http::fake([OsmaniyePrayerTimesService::SOURCE_URL => Http::failedConnection()]);
        $this->assertSame([], app(OsmaniyePrayerTimesService::class)->today()['times']);
    }
}

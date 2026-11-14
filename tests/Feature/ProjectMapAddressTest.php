<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectMapAddressTest extends TestCase
{
    public function test_it_returns_real_street_and_door_records_and_uses_line_distance(): void
    {
        $this->fakeMaks();
        $reply = $this->getJson($this->url())->assertOk()
            ->assertJsonPath('source', 'TKGM MAKS')->assertJsonPath('street', '6002 Sokak')
            ->assertJsonPath('candidates.0.street', '6002 Sokak')->assertJsonPath('candidates.0.door', '12A')
            ->assertJsonPath('candidates.0.latitude', 37.05024)->assertJsonPath('partial', false)
            ->assertDontSee('must-not-leak')->assertDontSee('9999');
        $this->assertLessThan(5, $reply->json('candidates.0.distance'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/numaratajliste/4'));
        $count = count(Http::recorded());
        $this->getJson($this->url())->assertOk();
        Http::assertSentCount($count);
    }

    public function test_it_validates_coordinates_before_contacting_the_provider(): void
    {
        Http::fake();
        $this->getJson(route('projects.map.address', ['lat' => 91, 'lon' => 36]))->assertUnprocessable();
        $this->getJson(route('projects.map.address', ['lat' => '1/../../host', 'lon' => 36]))->assertUnprocessable();
        $this->getJson(route('projects.map.address', ['lat' => 37, 'lon' => 'NaN']))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_it_reports_provider_failure_without_inventing_an_address(): void
    {
        Http::fake(['cbsapi.tkgm.gov.tr/*' => Http::response('Unavailable', 503)]);
        $this->getJson($this->url())->assertStatus(503)->assertJsonStructure(['message'])->assertJsonMissingPath('candidates');
    }

    public function test_a_region_hole_cannot_supply_an_unrelated_street(): void
    {
        $outer = [[36, 37], [37, 37], [37, 38], [36, 38], [36, 37]];
        $hole = [[36.2, 37.01], [36.4, 37.01], [36.4, 37.1], [36.2, 37.1], [36.2, 37.01]];
        Http::fake(['cbsapi.tkgm.gov.tr/*' => Http::response(['features' => [[
            'properties' => ['id' => '1', 'text' => 'İl'],
            'geometry' => ['type' => 'Polygon', 'coordinates' => [$outer, $hole]],
        ]]])]);
        $this->getJson($this->url())->assertOk()->assertJsonCount(0, 'candidates')->assertJsonPath('street', null);
        Http::assertSentCount(1);
    }

    public function test_it_keeps_available_door_records_and_retries_a_failed_street(): void
    {
        $failed = true;
        $this->fakeMaks(function ($request) use (&$failed) {
            if (str_ends_with($request->url(), '/numaratajliste/5')) {
                return Http::response($failed ? [] : ['features' => []], $failed ? 503 : 200);
            }

            return null;
        });
        $this->getJson($this->url())->assertOk()->assertJsonPath('partial', true)->assertJsonPath('candidates.0.door', '12A');
        $failed = false;
        $this->getJson($this->url())->assertOk()->assertJsonPath('partial', false)->assertJsonPath('candidates.0.door', '12A');
        $requests = Http::recorded(fn ($request) => str_ends_with($request->url(), '/numaratajliste/5'));
        $this->assertCount(2, $requests);
    }

    private function fakeMaks(?callable $override = null): void
    {
        $region = ['geometry' => ['type' => 'Polygon', 'coordinates' => [[[36, 37], [37, 37], [37, 38], [36, 38], [36, 37]]]],
            'properties' => ['id' => '1', 'text' => 'Bölge']];
        Http::fake(function ($request) use ($region, $override) {
            $custom = $override ? $override($request) : null;
            if ($custom) {
                return $custom;
            }
            if (str_contains($request->url(), 'numaratajliste/')) {
                return Http::response(['features' => [
                    ['geometry' => ['type' => 'Point', 'coordinates' => [36.26814, 37.05024]], 'properties' => ['text' => '12A (konut)', 'private' => 'must-not-leak']],
                    ['geometry' => ['type' => 'Point', 'coordinates' => [36.27814, 37.05024]], 'properties' => ['text' => '9999']],
                ]]);
            }
            if (str_contains($request->url(), 'yolliste/')) {
                return Http::response(['features' => [
                    ['properties' => ['id' => '4', 'text' => '6002 Sokak'], 'geometry' => ['type' => 'LineString', 'coordinates' => [[36.25, 37.0502], [36.28, 37.0502]]]],
                    ['properties' => ['id' => '5', 'text' => '6003 Sokak'], 'geometry' => ['type' => 'LineString', 'coordinates' => [[36.25, 37.0507], [36.28, 37.0507]]]],
                ]]);
            }

            return Http::response(['features' => [$region]]);
        });
    }

    private function url(): string
    {
        return route('projects.map.address', ['lat' => 37.0502237, 'lon' => 36.2681844]);
    }
}

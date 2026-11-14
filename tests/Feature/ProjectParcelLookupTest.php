<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectParcelLookupTest extends TestCase
{
    public function test_it_requests_the_fixed_tkgm_endpoint_and_returns_only_mapped_fields_and_geometry(): void
    {
        Http::fake([
            'cbsapi.tkgm.gov.tr/*' => Http::response([
                'properties' => [
                    'ilAd' => 'Osmaniye', 'ilceAd' => 'Merkez', 'mahalleAd' => 'Raufbey',
                    'adaNo' => '123', 'parselNo' => '45', 'alan' => '250.50',
                    'nitelik' => 'Arsa', 'pafta' => 'E25C04A', 'mevkii' => 'Örnek mevkii',
                    'unrelatedPrivateField' => 'must-not-leak',
                ],
                'geometry' => ['type' => 'Polygon', 'coordinates' => [[[36.2, 37.0], [36.21, 37.0], [36.21, 37.01], [36.2, 37.0]]]],
            ]),
        ]);

        $this->getJson(route('projects.parcel.lookup', ['lat' => '37.074', 'lon' => '36.246']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('parcel.province', 'Osmaniye')
            ->assertJsonPath('parcel.block', '123')
            ->assertJsonPath('parcel.parcel', '45')
            ->assertJsonPath('geometry.type', 'Polygon')
            ->assertDontSee('must-not-leak');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/parsel/37.074/36.246/')
            && $request->hasHeader('Referer', 'https://parselsorgu.tkgm.gov.tr/'));
    }

    public function test_it_reports_no_parcel_instead_of_presenting_empty_fields_as_a_success(): void
    {
        Http::fake(['cbsapi.tkgm.gov.tr/*' => Http::response(['properties' => [], 'geometry' => null])]);

        $this->getJson(route('projects.parcel.lookup', ['lat' => 37.074, 'lon' => 36.246]))
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('message', 'Bu noktada parsel bulunamadı.')
            ->assertJsonPath('parcel', null)
            ->assertJsonPath('geometry', null);
    }

    public function test_it_rejects_out_of_range_and_injected_coordinates_without_calling_tkgm(): void
    {
        Http::fake();

        $this->getJson(route('projects.parcel.lookup', ['lat' => 91, 'lon' => 36]))->assertUnprocessable();
        $this->getJson(route('projects.parcel.lookup', ['lat' => '1/../../attacker.example', 'lon' => 36]))->assertUnprocessable();
        Http::assertNothingSent();
    }
}

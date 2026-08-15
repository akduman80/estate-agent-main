<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Services\EidsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EidsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_verifies_a_listing_with_the_eids_tasinmaz_api(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', '22222222-2222-2222-2222-222222222222');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.verify_path', '/EidsTasinmazAPI');

        Http::fake([
            'ws.gtb.gov.tr:8443/EidsTasinmazAPI' => Http::response([
                'data' => [
                    'Il' => 'Osmaniye',
                    'Ilce' => 'Sumbas',
                    'Mahalle' => 'Gaffarli',
                    'Ada' => '101',
                    'Parsel' => '12',
                    'IlanSuresi' => '2026-12-31T23:59:59',
                ],
                'statusCode' => 200,
                'errors' => null,
            ], 200),
        ]);

        $user = User::factory()->create();
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'EİDS test ilanı',
            'slug' => 'eids-test-ilani',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Sumbas',
        ]));

        $result = app(EidsService::class)->verifyListing($listing);

        $this->assertTrue($result);
        $listing->refresh()->load('eidsVerification');
        $this->assertSame('verified', $listing->eids_status);
        $this->assertSame('Osmaniye', $listing->eidsVerification->city);
        $this->assertSame('Sumbas', $listing->eidsVerification->district);
        $this->assertSame('Gaffarli', $listing->eidsVerification->neighborhood);
        $this->assertSame('101', $listing->eidsVerification->block);
        $this->assertSame('12', $listing->eidsVerification->parcel);
        $this->assertNotNull($listing->eidsVerification->verified_at);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && $request['FirmaKod'] === '11111111-1111-1111-1111-111111111111'
            && $request['KullaniciKodu'] === '22222222-2222-2222-2222-222222222222'
            && $request['TasinmazId'] === 94536593.0
            && $request['IlanNo'] === (string) $listing->id);
    }
}

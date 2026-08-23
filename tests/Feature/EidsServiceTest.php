<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ListingEidsRequestLog;
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
        config()->set('services.eids.tax_no', '0230192423');
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
        $this->assertTrue($listing->is_active);
        $this->assertSame('Osmaniye', $listing->eidsVerification->city);
        $this->assertSame('Sumbas', $listing->eidsVerification->district);
        $this->assertSame('Gaffarli', $listing->eidsVerification->neighborhood);
        $this->assertSame('101', $listing->eidsVerification->block);
        $this->assertSame('12', $listing->eidsVerification->parcel);
        $this->assertNotNull($listing->eidsVerification->verified_at);
        $this->assertDatabaseCount('listing_eids_request_logs', 1);

        $log = ListingEidsRequestLog::query()->firstOrFail();

        $this->assertSame($listing->id, $log->listing_id);
        $this->assertSame('https://ws.gtb.gov.tr:8443/EidsTasinmazAPI', $log->endpoint);
        $this->assertSame('POST', $log->method);
        $this->assertSame('verified', $log->result_status);
        $this->assertSame(200, $log->response_status);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $log->request_payload['FirmaKod']);
        $this->assertSame('sha256:'.hash('sha256', '22222222-2222-2222-2222-222222222222'), $log->request_payload['KullaniciKodu']);
        $this->assertEquals(94536593.0, $log->request_payload['TasinmazId']);
        $this->assertSame((string) $listing->id, $log->request_payload['IlanNo']);
        $this->assertSame('Osmaniye', $log->response_payload['data']['Il']);
        $this->assertNotNull($log->attempted_at);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && $request['FirmaKod'] === '11111111-1111-1111-1111-111111111111'
            && $request['KullaniciKodu'] === '22222222-2222-2222-2222-222222222222'
            && $request['VergiNo'] === '0230192423'
            && $request['TasinmazId'] === 94536593.0
            && $request['IlanNo'] === (string) $listing->id);
    }

    public function test_it_rejects_a_listing_when_user_code_is_missing(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', null);
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.verify_path', '/EidsTasinmazAPI');

        Http::fake();

        $user = User::factory()->create();
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'EİDS eksik kullanici kodu test ilanı',
            'slug' => 'eids-eksik-kullanici-kodu-test-ilani',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Merkez',
        ]));

        $result = app(EidsService::class)->verifyListing($listing);

        $this->assertFalse($result);
        $listing->refresh()->load('eidsVerification');

        $this->assertSame('rejected', $listing->eids_status);
        $this->assertFalse($listing->is_active);
        $this->assertSame('Taşınmaz ID ve EIDS_USER_CODE ayarı zorunludur.', $listing->eidsVerification->error_message);
        Http::assertNothingSent();
    }

    public function test_it_uses_individual_members_own_eids_code_without_platform_tax_number(): void
    {
        config()->set('services.eids.company_code', 'platform-guid');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', 'platform-user-guid');
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.verify_path', '/EidsTasinmazAPI');
        Http::fake(['ws.gtb.gov.tr:8443/EidsTasinmazAPI' => Http::response([
            'data' => ['Il' => 'Osmaniye', 'Ilce' => 'Merkez', 'Mahalle' => 'Alibeyli', 'Ada' => '1', 'Parsel' => '2', 'IlanSuresi' => now()->addMonth()->toIso8601String()],
            'statusCode' => 200, 'errors' => null,
        ])]);

        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'eids_user_code' => 'individual-user-guid',
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id, 'title' => 'Bireysel EİDS', 'slug' => 'bireysel-eids',
            'price' => 1000, 'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'owner',
            'eids_document_no' => '123', 'city_id' => '80', 'district_id' => 'Merkez',
        ]));

        $this->assertTrue(app(EidsService::class)->verifyListing($listing));
        Http::assertSent(fn ($request): bool => $request['KullaniciKodu'] === 'individual-user-guid' && $request['VergiNo'] === null);
        $this->assertSame($user->id, $listing->fresh()->eidsVerification->user_id);
        $this->assertNull($listing->fresh()->eidsVerification->business_profile_id);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ListingEidsRequestLog;
use App\Models\Setting;
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

        $user = User::factory()->create(['eids_user_code' => '22222222-2222-2222-2222-222222222222']);
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
        $this->assertSame($user->id, $log->owner_user_id);
        $this->assertSame('user_account', $log->eids_identity_context);
        $this->assertSame('company', $log->authorization_subject_type);
        $this->assertSame('users.eids_user_code', $log->eids_user_code_source);
        $this->assertSame(hash('sha256', '22222222-2222-2222-2222-222222222222'), $log->eids_user_code_hash);
        $this->assertSame('2423', $log->tax_number_last4);
        $this->assertTrue($log->sent_tax_number);
        $this->assertTrue($log->sent_user_code);
        $this->assertSame('Osmaniye', $log->response_payload['data']['Il']);
        $this->assertNotNull($log->attempted_at);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && $request['FirmaKod'] === '11111111-1111-1111-1111-111111111111'
            && $request['KullaniciKodu'] === '22222222-2222-2222-2222-222222222222'
            && $request['VergiNo'] === '0230192423'
            && $request['TasinmazId'] === 94536593.0
            && $request['IlanNo'] === (string) $listing->id);
    }

    public function test_it_logs_the_message_when_eids_returns_a_message_instead_of_errors(): void
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
                'statusCode' => 400,
                'message' => 'Taşınmaz için ilan yetkisi bulunamadı.',
            ], 400),
        ]);

        $user = User::factory()->create(['eids_user_code' => '22222222-2222-2222-2222-222222222222']);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'EİDS reddedilen ilanı',
            'slug' => 'eids-message-reddedilen-ilani',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Merkez',
        ]));

        $this->assertFalse(app(EidsService::class)->verifyListing($listing));

        $log = ListingEidsRequestLog::query()->firstOrFail();
        $this->assertSame('rejected', $log->result_status);
        $this->assertSame('Taşınmaz için ilan yetkisi bulunamadı.', $log->error_message);
        $this->assertSame('Taşınmaz için ilan yetkisi bulunamadı.', $log->response_payload['message']);
    }

    public function test_it_rejects_a_listing_when_user_code_is_missing(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', 'another-agent-code');
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
        $this->assertSame('İlan sahibinin panelinden EİDS kimlik doğrulamasını tamamlayın.', $listing->eidsVerification->error_message);
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

    public function test_it_keeps_two_agents_codes_and_tax_numbers_separate(): void
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

        foreach ([
            '33333333-3333-4333-8333-333333333333' => '0230192423',
            '44444444-4444-4444-8444-444444444444' => '7900123456',
        ] as $code => $taxNumber) {
            $user = User::factory()->create([
                'role' => UserRole::AGENT,
                'account_status' => AccountStatus::ACTIVE,
                'eids_user_code' => $code,
            ]);
            $profile = $user->businessProfile()->create([
                'office_name' => $code, 'license_number' => $code, 'tax_office' => 'Merkez',
                'tax_number' => $taxNumber, 'tax_number_hash' => hash('sha256', $taxNumber),
                'city' => 'Osmaniye', 'district' => 'Merkez', 'address' => 'Test adresi',
            ]);
            $listing = Listing::withoutEvents(fn () => Listing::create([
                'user_id' => $user->id, 'title' => 'Bireysel EİDS', 'slug' => $code,
                'price' => 1000, 'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'real_estate_office',
                'eids_document_no' => '123', 'city_id' => '80', 'district_id' => 'Merkez',
            ]));

            $this->assertTrue(app(EidsService::class)->verifyListing($listing));
            Http::assertSent(fn ($request): bool => $request['KullaniciKodu'] === $code && $request['VergiNo'] === $taxNumber);
            $this->assertSame($user->id, $listing->fresh()->eidsVerification->user_id);
        }
        Http::assertSentCount(2);
    }

    public function test_it_rejects_agent_company_listing_when_user_code_is_not_a_guid_without_calling_eids(): void
    {
        config()->set('services.eids.company_code', 'platform-guid');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', 'platform-user-guid');
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.verify_path', '/EidsTasinmazAPI');
        Http::fake();

        $user = User::factory()->create([
            'role' => UserRole::AGENT,
            'account_status' => AccountStatus::ACTIVE,
            'eids_user_code' => 'sha256:12b9377cbe7e5c94e8a70d9d23929523d14afa954793130f8a3959c7b849aca8',
        ]);
        $profile = $user->businessProfile()->create([
            'office_name' => 'TOSUN EMLAK',
            'license_number' => '123',
            'tax_office' => 'Merkez',
            'tax_number' => '7900123456',
            'tax_number_hash' => hash('sha256', '7900123456'),
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'address' => 'Test adresi',
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id, 'title' => 'Agent EİDS', 'slug' => 'agent-eids-invalid',
            'price' => 1000, 'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'real_estate_office',
            'business_profile_id' => $profile->id, 'eids_document_no' => '123', 'city_id' => '80', 'district_id' => 'Merkez',
        ]));

        $this->assertFalse(app(EidsService::class)->verifyListing($listing));
        $this->assertSame('rejected', $listing->fresh()->eids_status);
        $this->assertSame(
            'Agent hesabındaki EİDS kullanıcı kodu geçerli GUID formatında değil. Lütfen agent panelinden EİDS kimlik doğrulamasını yenileyin.',
            $listing->fresh()->eidsVerification->error_message,
        );
        $this->assertSame('rejected', ListingEidsRequestLog::firstOrFail()->result_status);
        Http::assertNothingSent();
    }

    public function test_it_records_authorization_rejection_instead_of_connection_failure(): void
    {
        config()->set('services.eids.company_code', 'platform-guid');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', 'platform-user-guid');
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.verify_path', '/EidsTasinmazAPI');
        Http::fake(['*' => Http::response([
            'data' => null, 'statusCode' => 400,
            'errors' => ['Taşınmaza ait yetkilendirme bilgisi bulunmadı.', 'ERR-004'],
        ], 400)]);

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

        $this->assertFalse(app(EidsService::class)->verifyListing($listing));
        $this->assertSame('rejected', $listing->fresh()->eids_status);
        $this->assertSame('Taşınmaza ait yetkilendirme bilgisi bulunmadı. ERR-004', $listing->fresh()->eidsVerification->error_message);
        $this->assertSame(400, ListingEidsRequestLog::firstOrFail()->response_status);
    }

    public function test_zydemlak_listing_uses_office_code_without_tax_number(): void
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
            'name' => 'Süpervizör',
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'eids_user_code' => 'individual-user-guid',
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id, 'title' => 'Bireysel EİDS', 'slug' => 'bireysel-eids',
            'price' => 1000, 'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'zydemlak',
            'eids_document_no' => '123', 'city_id' => '80', 'district_id' => 'Merkez',
        ]));

        $this->assertTrue(app(EidsService::class)->verifyListing($listing));
        Http::assertSent(fn ($request): bool => $request['KullaniciKodu'] === 'platform-user-guid' && $request['VergiNo'] === null);
        $this->assertSame($user->id, $listing->fresh()->eidsVerification->user_id);
        $this->assertSame(hash('sha256', 'platform-user-guid'), $listing->fresh()->eidsVerification->eids_user_code_hash);

        $log = ListingEidsRequestLog::firstOrFail();
        $this->assertSame($user->id, $log->owner_user_id);
        $this->assertSame('Süpervizör', $log->owner_name);
        $this->assertSame('supervisor', $log->owner_role);
        $this->assertSame('zydemlak', $log->listing_source_type);
        $this->assertSame('person', $log->authorization_subject_type);
        $this->assertSame('zydemlak_office_identity', $log->eids_identity_context);
        $this->assertSame('settings.eids_user_code', $log->eids_user_code_source);
        $this->assertSame(hash('sha256', 'platform-user-guid'), $log->eids_user_code_hash);
        $this->assertNull($log->tax_number_last4);
        $this->assertFalse($log->sent_tax_number);
        $this->assertTrue($log->sent_user_code);
    }

    public function test_platform_listing_prefers_database_eids_settings_over_config(): void
    {
        config()->set('services.eids.company_code', 'config-platform-guid');
        config()->set('services.eids.username', 'config-user');
        config()->set('services.eids.password', 'config-pass');
        config()->set('services.eids.user_code', 'config-platform-user-guid');
        config()->set('services.eids.tax_no', 'config-tax-no');
        config()->set('services.eids.base_url', 'https://config-eids.test');
        config()->set('services.eids.verify_path', '/config-verify');

        Setting::updateOrCreate(['key' => 'eids_company_code'], ['value' => 'db-platform-guid']);
        Setting::updateOrCreate(['key' => 'eids_base_url'], ['value' => 'https://db-eids.test']);
        Setting::updateOrCreate(['key' => 'eids_verify_path'], ['value' => '/db-verify']);
        Setting::putSecret('eids_username', 'db-user');
        Setting::putSecret('eids_password', 'db-pass');
        Setting::putSecret('eids_user_code', 'db-platform-user-guid');
        Setting::putSecret('eids_tax_no', 'db-tax-no');

        Http::fake(['db-eids.test/db-verify' => Http::response([
            'data' => ['Il' => 'Osmaniye', 'Ilce' => 'Merkez', 'Mahalle' => 'Alibeyli'],
            'statusCode' => 200, 'errors' => null,
        ])]);

        $user = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id, 'title' => 'DB EİDS', 'slug' => 'db-eids',
            'price' => 1000, 'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'zydemlak',
            'eids_document_no' => '123', 'city_id' => '80', 'district_id' => 'Merkez',
        ]));

        $this->assertTrue(app(EidsService::class)->verifyListing($listing));
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization')
            && $request['FirmaKod'] === 'db-platform-guid'
            && $request['KullaniciKodu'] === 'db-platform-user-guid'
            && $request['VergiNo'] === null);
    }
}

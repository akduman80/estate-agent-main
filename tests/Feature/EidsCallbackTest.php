<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\ListingEidsRequestLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EidsCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_to_the_eids_sso_url(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.sso_url', 'https://eids.ticaret.gov.tr/oturum');
        $admin = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get('/eids/start');

        $response->assertRedirect('https://eids.ticaret.gov.tr/oturum?firmaKodu=11111111-1111-1111-1111-111111111111');
    }

    public function test_it_stores_platform_user_code_only_for_platform_flow(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.gsm_no', '905303607769');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.sso_url', 'https://eids.ticaret.gov.tr/oturum');
        config()->set('services.eids.user_code_path', '/EidsApi/Kullanici/GetKullaniciKodu');
        $admin = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
        ]);

        Http::fake([
            'ws.gtb.gov.tr:8443/EidsApi/Kullanici/GetKullaniciKodu' => Http::response([
                'ad' => 'Ilhan',
                'soyad' => 'Akduman',
                'kullaniciKodu' => '22222222-2222-2222-2222-222222222222',
                'hataMesaji' => null,
                'hataKodu' => null,
            ], 200),
        ]);

        $this->actingAs($admin)->get('/eids/start')->assertRedirect();
        $response = $this->actingAs($admin)->get('/eids/callback?yetkiKodu=AUTH-CODE-123&durum=Basarili');

        $response->assertOk();
        $response->assertSee('ZYDEMLAK platform EİDS kullanıcı kodu güvenli biçimde veritabanına kaydedildi.');
        $this->assertSame('22222222-2222-2222-2222-222222222222', Setting::getSecret('eids_user_code'));

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && $request['yetkiKodu'] === 'AUTH-CODE-123'
            && $request['vergiNo'] === '0230192423'
            && $request['gsmNo'] === '905303607769');
    }

    public function test_account_eids_flow_stores_code_on_authenticated_user(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.sso_url', 'https://eids.ticaret.gov.tr/oturum');
        config()->set('services.eids.user_code_path', '/EidsApi/Kullanici/GetKullaniciKodu');
        $agent = User::factory()->create([
            'role' => UserRole::AGENT,
            'account_status' => AccountStatus::ACTIVE,
            'phone' => '05303607769',
            'phone_verified_at' => now(),
        ]);
        $agent->businessProfile()->create([
            'office_name' => 'ZİYADE EMLAK',
            'license_number' => '123',
            'tax_office' => 'Osmaniye',
            'tax_number' => '0230192423',
            'tax_number_hash' => hash('sha256', '0230192423'),
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'address' => 'Test adres',
            'approval_status' => 'approved',
        ]);

        Http::fake([
            'ws.gtb.gov.tr:8443/EidsApi/Kullanici/GetKullaniciKodu' => Http::response([
                'kullaniciKodu' => '33333333-3333-3333-3333-333333333333',
                'hataMesaji' => null,
                'hataKodu' => null,
            ], 200),
        ]);

        $this->actingAs($agent)->get('/hesabim/eids/baslat')->assertRedirect();
        $response = $this->actingAs($agent)->get('/eids/callback?yetkiKodu=AUTH-CODE-456&durum=Basarili');

        $response->assertOk();
        $response->assertSee('EİDS kimlik doğrulamanız hesabınıza güvenli biçimde kaydedildi.');
        $this->assertSame('33333333-3333-3333-3333-333333333333', $agent->fresh()->eids_user_code);
        $this->assertNotNull($agent->fresh()->eids_identity_verified_at);
        $log = ListingEidsRequestLog::query()->sole();
        $this->assertSame($agent->id, $log->owner_user_id);
        $this->assertNull($log->listing_id);
        $this->assertSame('agent_company', $log->eids_identity_context);
        $this->assertSame('ZİYADE EMLAK', $log->business_office_name);
        $this->assertSame(['yetkiKodu' => 'AUTH-CODE-456', 'vergiNo' => '0230192423', 'gsmNo' => '05303607769'], $log->request_payload);
        $this->assertSame('33333333-3333-3333-3333-333333333333', $log->response_payload['kullaniciKodu']);
        $this->assertSame(200, $log->response_status);
        $this->assertSame('verified', $log->result_status);
        $this->assertNull(Setting::getSecret('eids_user_code'));

        Http::assertSent(fn ($request) => $request['yetkiKodu'] === 'AUTH-CODE-456'
            && $request['vergiNo'] === '0230192423'
            && $request['gsmNo'] === '05303607769');
    }

    public function test_identity_errors_and_invalid_codes_are_not_saved_as_verified(): void
    {
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        Setting::putSecret('eids_user_code', '22222222-2222-2222-2222-222222222222');
        $agent = User::factory()->create([
            'role' => UserRole::AGENT,
            'phone' => '05303607769',
        ]);
        $payloads = [
            ['kullaniciKodu' => '00000000-0000-0000-0000-000000000000', 'hataKodu' => 'TB-0003', 'hataMesaji' => 'Belirtilen firmayı temsil yetkisi bulunmamaktadır!'],
            ['kullaniciKodu' => '00000000-0000-0000-0000-000000000000'],
            ['kullaniciKodu' => 'not-a-guid'],
            ['kullaniciKodu' => '33333333-3333-3333-3333-333333333333', 'hataKodu' => 'TB-0003'],
            ['kullaniciKodu' => '33333333-3333-3333-3333-333333333333', 'hataMesaji' => 'Temsil yetkisi yok'],
        ];
        $sequence = Http::sequence();
        foreach ($payloads as $payload) {
            $sequence->push($payload, 200);
        }
        Http::fake(['*' => $sequence]);
        foreach ($payloads as $payload) {
            $this->actingAs($agent)->get('/eids/callback?yetkiKodu=AUTH&durum=Basarili')->assertStatus(422);
            $this->assertNull($agent->fresh()->eids_user_code);
            $this->assertNull($agent->fresh()->eids_identity_verified_at);
            $log = ListingEidsRequestLog::query()->latest('id')->firstOrFail();
            $this->assertSame('rejected', $log->result_status);
            $this->assertSame($payload, $log->response_payload);
            $this->assertNotNull($log->error_message);
            $this->assertSame('22222222-2222-2222-2222-222222222222', Setting::getSecret('eids_user_code'));
        }
    }

    public function test_failed_renewal_preserves_existing_valid_account_and_platform_codes(): void
    {
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.gsm_no', '905303607769');
        $existingCode = '22222222-2222-2222-2222-222222222222';
        Setting::putSecret('eids_user_code', $existingCode);
        $verifiedAt = now()->startOfSecond();
        $user = User::factory()->create([
            'role' => UserRole::SUPERVISOR, 'phone' => '05303607769',
            'eids_user_code' => $existingCode, 'eids_identity_verified_at' => $verifiedAt,
        ]);
        Http::fake(['*' => Http::response([
            'kullaniciKodu' => '00000000-0000-0000-0000-000000000000',
            'hataKodu' => 'TB-0003', 'hataMesaji' => 'Belirtilen firmayı temsil yetkisi bulunmamaktadır!',
        ], 200)]);
        $this->actingAs($user)->get('/eids/callback?yetkiKodu=AUTH')->assertStatus(422)->assertSee('TB-0003');
        $this->assertSame($existingCode, $user->fresh()->eids_user_code);
        $this->assertTrue($verifiedAt->equalTo($user->fresh()->eids_identity_verified_at));
        $this->withSession(['eids_platform_identity' => true])->get('/eids/callback?yetkiKodu=AUTH')->assertStatus(422);
        $this->assertSame($existingCode, Setting::getSecret('eids_user_code'));
    }

    public function test_individual_identity_flow_uses_no_platform_tax_number(): void
    {
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.tax_no', '0230192423');
        $code = '44444444-4444-4444-4444-444444444444';
        $user = User::factory()->create(['role' => UserRole::INDIVIDUAL, 'phone' => '05303607769']);
        Http::fake(['*' => Http::response(['KullaniciKodu' => $code, 'HataKodu' => null, 'HataMesaji' => null], 200)]);
        $this->actingAs($user)->get('/eids/callback?yetkiKodu=AUTH')->assertOk();
        $this->assertSame($code, $user->fresh()->eids_user_code);
        Http::assertSent(fn ($request) => $request['vergiNo'] === null && $request['gsmNo'] === $user->phone);
        $this->assertNull(Setting::getSecret('eids_user_code'));
    }

    public function test_callback_without_matching_flow_does_not_store_platform_code(): void
    {
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.gsm_no', '905303607769');

        $response = $this->get('/eids/callback?yetkiKodu=AUTH-CODE-789&durum=Basarili');

        $response->assertStatus(409);
        $response->assertSee('EİDS işlemi başlatılan oturumla eşleşmedi.');
        $response->assertSee('Hesabıma giriş yap ve EİDS doğrulamasını yeniden başlat');
        $log = ListingEidsRequestLog::query()->sole();
        $this->assertSame('error', $log->result_status);
        $this->assertSame('callback_session_check', $log->response_payload['stage']);
        $this->assertSame('AUTH-CODE-789', $log->request_payload['yetkiKodu']);
        $this->assertNull($log->response_status);
        $this->assertNull($log->owner_user_id);
        $this->assertNull(Setting::getSecret('eids_user_code'));
        Http::assertNothingSent();
    }
}

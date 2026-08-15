<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EidsCallbackTest extends TestCase
{
    public function test_it_redirects_to_the_eids_sso_url(): void
    {
        config()->set('services.eids.company_code', '11111111-1111-1111-1111-111111111111');
        config()->set('services.eids.sso_url', 'https://eids.ticaret.gov.tr/oturum');

        $response = $this->get('/eids/start');

        $response->assertRedirect('https://eids.ticaret.gov.tr/oturum?firmaKodu=11111111-1111-1111-1111-111111111111');
    }

    public function test_it_requests_the_user_code_from_the_eids_callback(): void
    {
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.tax_no', '0230192423');
        config()->set('services.eids.gsm_no', '905303607769');
        config()->set('services.eids.base_url', 'https://ws.gtb.gov.tr:8443');
        config()->set('services.eids.user_code_path', '/EidsApi/Kullanici/GetKullaniciKodu');

        Http::fake([
            'ws.gtb.gov.tr:8443/EidsApi/Kullanici/GetKullaniciKodu' => Http::response([
                'ad' => 'Ilhan',
                'soyad' => 'Akduman',
                'kullaniciKodu' => '22222222-2222-2222-2222-222222222222',
                'hataMesaji' => null,
                'hataKodu' => null,
            ], 200),
        ]);

        $response = $this->get('/eids/callback?yetkiKodu=AUTH-CODE-123&durum=Basarili');

        $response->assertOk();
        $response->assertSee('EIDS_USER_CODE=22222222-2222-2222-2222-222222222222');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && $request['yetkiKodu'] === 'AUTH-CODE-123'
            && $request['vergiNo'] === '0230192423'
            && $request['gsmNo'] === '905303607769');
    }
}

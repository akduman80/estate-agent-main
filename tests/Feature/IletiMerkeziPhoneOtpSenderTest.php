<?php

namespace Tests\Feature;

use App\Contracts\PhoneOtpSender;
use App\Models\Setting;
use App\Services\IletiMerkeziPhoneOtpSender;
use App\Services\LogPhoneOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class IletiMerkeziPhoneOtpSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_log_sender_until_all_required_settings_exist(): void
    {
        $this->assertInstanceOf(LogPhoneOtpSender::class, app(PhoneOtpSender::class));

        $this->configureProvider();

        $this->assertInstanceOf(IletiMerkeziPhoneOtpSender::class, app(PhoneOtpSender::class));
    }

    public function test_it_sends_otp_with_encrypted_credentials_and_normalized_phone(): void
    {
        Http::fake([
            'api.iletimerkezi.com/*' => Http::response([
                'response' => [
                    'status' => ['code' => '200', 'message' => 'İşlem başarılı'],
                    'order' => ['id' => '12345'],
                ],
            ]),
        ]);

        $this->configureProvider();

        app(PhoneOtpSender::class)->send('+90 (555) 111 22 33', '123456');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.iletimerkezi.com/v1/send-sms/json'
                && $request['request']['authentication']['key'] === 'test-api-key'
                && $request['request']['authentication']['hash'] === 'test-api-hash'
                && $request['request']['order']['sender'] === 'ILHAN AKDMN'
                && $request['request']['order']['iys'] === '0'
                && $request['request']['order']['message']['receipents']['number'] === ['5551112233']
                && str_contains($request['request']['order']['message']['text'], '123456');
        });

        $rawKey = (string) Setting::query()->where('key', 'sms_api_key')->value('value');
        $rawHash = (string) Setting::query()->where('key', 'sms_api_hash')->value('value');

        $this->assertStringNotContainsString('test-api-key', $rawKey);
        $this->assertStringNotContainsString('test-api-hash', $rawHash);
    }

    public function test_it_rejects_an_invalid_turkish_mobile_number_without_sending_request(): void
    {
        Http::fake();
        $this->configureProvider();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('geçerli bir Türkiye cep telefonu');

        try {
            app(PhoneOtpSender::class)->send('2125551122', '123456');
        } finally {
            Http::assertNothingSent();
        }
    }

    private function configureProvider(): void
    {
        Setting::putSecret('sms_api_key', 'test-api-key');
        Setting::putSecret('sms_api_hash', 'test-api-hash');

        foreach ([
            'sms_sender' => 'ILHAN AKDMN',
            'sms_iys_enabled' => '0',
            'sms_iys_list' => 'BIREYSEL',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Setting::clearCache($key);
        }
    }
}

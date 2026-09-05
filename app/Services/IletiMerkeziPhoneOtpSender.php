<?php

namespace App\Services;

use App\Contracts\PhoneOtpSender;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IletiMerkeziPhoneOtpSender implements PhoneOtpSender
{
    private const ENDPOINT = 'https://api.iletimerkezi.com/v1/send-sms/json';

    public static function isConfigured(): bool
    {
        return filled(Setting::getSecret('sms_api_key'))
            && filled(Setting::getSecret('sms_api_hash'))
            && filled(Setting::get('sms_sender'))
            && in_array((string) Setting::get('sms_iys_enabled'), ['0', '1'], true)
            && ((string) Setting::get('sms_iys_enabled') === '0' || filled(Setting::get('sms_iys_list')));
    }

    public function send(string $phone, string $code): void
    {
        if (! self::isConfigured()) {
            throw new RuntimeException('İleti Merkezi SMS ayarları eksik.');
        }

        $iysEnabled = (string) Setting::get('sms_iys_enabled');

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->post(self::ENDPOINT, [
                    'request' => [
                        'authentication' => [
                            'key' => Setting::getSecret('sms_api_key'),
                            'hash' => Setting::getSecret('sms_api_hash'),
                        ],
                        'order' => [
                            'sender' => trim((string) Setting::get('sms_sender')),
                            'sendDateTime' => [],
                            'iys' => $iysEnabled,
                            'iysList' => $iysEnabled === '1'
                                ? (string) Setting::get('sms_iys_list')
                                : 'BIREYSEL',
                            'message' => [
                                'text' => "ZYDEMLAK dogrulama kodunuz: {$code}. Bu kodu kimseyle paylasmayin.",
                                'receipents' => [
                                    'number' => [$this->normalizePhone($phone)],
                                ],
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('İleti Merkezi servisine bağlanılamadı.', previous: $exception);
        }

        $providerCode = (string) $response->json('response.status.code', '');

        if (! $response->successful() || $providerCode !== '200') {
            $providerMessage = trim((string) $response->json('response.status.message', ''));
            $safeMessage = $providerMessage !== '' ? " ({$providerCode}: {$providerMessage})" : '';

            throw new RuntimeException("İleti Merkezi SMS gönderimini reddetti{$safeMessage}.");
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^5\d{9}$/', $digits)) {
            throw new RuntimeException('SMS gönderimi için geçerli bir Türkiye cep telefonu numarası gerekiyor.');
        }

        return $digits;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EidsIdentityService
{
    public function ssoUrl(): string
    {
        $url = config('services.eids.sso_url');
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query([
            'firmaKodu' => config('services.eids.company_code'),
        ]);
    }

    public function requestUserCode(string $authorizationCode, ?string $gsmNo = null): array
    {
        $response = Http::withBasicAuth(config('services.eids.username'), config('services.eids.password'))
            ->acceptJson()
            ->asJson()
            ->timeout(config('services.eids.timeout', 20))
            ->post($this->userCodeUrl(), [
                'yetkiKodu' => $authorizationCode,
                'vergiNo' => config('services.eids.tax_no'),
                'gsmNo' => $gsmNo ?: config('services.eids.gsm_no'),
            ])
            ->throw();

        return $this->normalizeUserCodeResponse($response);
    }

    public function isConfiguredForSso(): bool
    {
        return filled(config('services.eids.company_code'));
    }

    public function isConfiguredForUserCode(): bool
    {
        return filled(config('services.eids.username'))
            && filled(config('services.eids.password'))
            && filled(config('services.eids.gsm_no'));
    }

    private function userCodeUrl(): string
    {
        return rtrim(config('services.eids.base_url'), '/').'/'.ltrim(config('services.eids.user_code_path'), '/');
    }

    private function normalizeUserCodeResponse(Response $response): array
    {
        $payload = $response->json() ?? [];

        return [
            'payload' => $payload,
            'user_code' => data_get($payload, 'kullaniciKodu') ?: data_get($payload, 'KullaniciKodu'),
            'error_message' => data_get($payload, 'hataMesaji') ?: data_get($payload, 'islemSonucMesaji'),
            'error_code' => data_get($payload, 'hataKodu') ?: data_get($payload, 'islemSonucKodu'),
        ];
    }
}

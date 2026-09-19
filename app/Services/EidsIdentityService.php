<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EidsIdentityService
{
    public function __construct(private readonly EidsSettingsService $settings) {}

    public function ssoUrl(): string
    {
        $url = $this->settings->ssoUrl();
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query([
            'firmaKodu' => $this->settings->companyCode(),
        ]);
    }

    public function requestUserCode(
        string $authorizationCode,
        ?string $gsmNo = null,
        ?string $taxNumber = null,
        bool $usePlatformTaxNumber = true,
    ): array {
        $response = Http::withBasicAuth($this->settings->username(), $this->settings->password())
            ->acceptJson()
            ->asJson()
            ->timeout($this->settings->timeout())
            ->post($this->userCodeUrl(), [
                'yetkiKodu' => $authorizationCode,
                'vergiNo' => $usePlatformTaxNumber ? ($taxNumber ?? $this->settings->platformTaxNumber()) : $taxNumber,
                'gsmNo' => $gsmNo ?: $this->settings->platformGsmNumber(),
            ])
            ->throw();

        return $this->normalizeUserCodeResponse($response);
    }

    public function isConfiguredForSso(): bool
    {
        return filled($this->settings->companyCode());
    }

    public function isConfiguredForUserCode(?string $gsmNo = null): bool
    {
        return filled($this->settings->username())
            && filled($this->settings->password())
            && filled($gsmNo ?: $this->settings->platformGsmNumber());
    }

    private function userCodeUrl(): string
    {
        return $this->settings->userCodeUrl();
    }

    private function normalizeUserCodeResponse(Response $response): array
    {
        $payload = $response->json() ?? [];

        return [
            'payload' => $payload,
            'user_code' => data_get($payload, 'kullaniciKodu')
                ?: data_get($payload, 'KullaniciKodu')
                ?: data_get($payload, 'kullaniciKod')
                ?: data_get($payload, 'KullaniciKod')
                ?: data_get($payload, 'userCode')
                ?: data_get($payload, 'UserCode'),
            'error_message' => data_get($payload, 'hataMesaji') ?: data_get($payload, 'islemSonucMesaji'),
            'error_code' => data_get($payload, 'hataKodu') ?: data_get($payload, 'islemSonucKodu'),
        ];
    }
}

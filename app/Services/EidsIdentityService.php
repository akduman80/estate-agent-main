<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ListingEidsRequestLog;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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
        ?User $accountUser = null,
    ): array {
        $startedAt = microtime(true);
        $attemptedAt = now();
        $endpoint = $this->userCodeUrl();
        $payload = [
            'yetkiKodu' => $authorizationCode,
            'vergiNo' => $usePlatformTaxNumber ? ($taxNumber ?? $this->settings->platformTaxNumber()) : $taxNumber,
            'gsmNo' => $gsmNo ?: $this->settings->platformGsmNumber(),
        ];
        $response = null;
        $result = null;
        $errorMessage = null;
        try {
            $response = Http::withBasicAuth($this->settings->username(), $this->settings->password())
                ->acceptJson()
                ->asJson()
                ->timeout($this->settings->timeout())
                ->post($endpoint, $payload);
            $response->throw();

            $result = $this->normalizeUserCodeResponse($response);

            return $result;
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
            throw $exception;
        } finally {
            $accountUser?->loadMissing('businessProfile');
            ListingEidsRequestLog::create([
                'owner_user_id' => $accountUser?->id,
                'owner_name' => $accountUser?->name,
                'owner_role' => $accountUser?->role?->value,
                'business_profile_id' => $accountUser?->businessProfile?->id,
                'business_office_name' => $accountUser?->businessProfile?->office_name,
                'authorization_subject_type' => filled($payload['vergiNo']) ? 'company' : 'person',
                'eids_identity_context' => $accountUser === null ? 'zydemlak_platform_company' : ($accountUser->role === UserRole::AGENT ? 'agent_company' : 'user_account'),
                'eids_user_code_source' => $accountUser === null ? 'settings.eids_user_code' : 'users.eids_user_code',
                'eids_user_code_hash' => filled($result['user_code'] ?? null) ? hash('sha256', $result['user_code']) : null,
                'tax_number_last4' => filled($payload['vergiNo']) ? substr($payload['vergiNo'], -4) : null,
                'sent_tax_number' => filled($payload['vergiNo']),
                'sent_user_code' => false,
                'endpoint' => $endpoint,
                'method' => 'POST',
                'request_payload' => $payload,
                'response_status' => $response?->status(),
                'response_payload' => $response ? ($response->json() ?? ['body' => $response->body()]) : null,
                'result_status' => $errorMessage !== null ? 'error' : (filled($result['user_code'] ?? null) ? 'verified' : 'rejected'),
                'error_message' => $errorMessage ?? (blank($result['user_code'] ?? null) ? ($result['error_message'] ?? 'EİDS kullanıcı kodu boş döndü.') : null),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'attempted_at' => $attemptedAt,
            ]);
        }
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
        $userCode = data_get($payload, 'kullaniciKodu') ?: data_get($payload, 'KullaniciKodu');
        $errorCode = data_get($payload, 'hataKodu') ?: data_get($payload, 'HataKodu');
        $errorMessage = data_get($payload, 'hataMesaji') ?: data_get($payload, 'HataMesaji');
        $hasError = filled($errorMessage) || (filled($errorCode) && (string) $errorCode !== '0');
        $validCode = is_string($userCode) && Str::isUuid($userCode)
            && $userCode !== '00000000-0000-0000-0000-000000000000';

        if ($hasError || ! $validCode) {
            $userCode = null;
            $errorMessage = $errorMessage ?: 'EİDS geçerli bir kullanıcı kodu döndürmedi. Kimlik doğrulamasını yeniden başlatın.';
            if (filled($errorCode)) {
                $errorMessage .= ' ('.$errorCode.')';
            }
        }

        return [
            'payload' => $payload,
            'user_code' => $userCode,
            'error_message' => $errorMessage ?: data_get($payload, 'islemSonucMesaji'),
            'error_code' => $errorCode ?: data_get($payload, 'islemSonucKodu'),
        ];
    }
}

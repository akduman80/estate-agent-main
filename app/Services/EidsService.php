<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ListingEidsRequestLog;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EidsService
{
    public function __construct(private readonly EidsSettingsService $settings) {}

    /**
     * İlanı EİDS taşınmaz yetki servisine gönderip doğrular.
     */
    public function verifyListing(Listing $listing): bool
    {
        $attemptedAt = now();
        $startedAt = microtime(true);
        $endpoint = $this->verifyUrl();
        $requestPayload = $this->requestPayload($listing);

        if (! $this->isConfigured()) {
            $this->markAsPending($listing, 'EİDS ayarları eksik. EIDS_COMPANY_CODE, EIDS_USERNAME ve EIDS_PASSWORD değerlerini .env dosyasında tanımlayın.');
            $this->logRequest($listing, $endpoint, $requestPayload, 'pending', 'EİDS ayarları eksik.', null, $startedAt, $attemptedAt);

            return false;
        }

        if (blank($listing->eids_document_no)) {
            $message = 'Taşınmaz ID zorunludur.';
            $this->markAsRejected($listing, [$message]);
            $this->logRequest($listing, $endpoint, $requestPayload, 'rejected', $message, null, $startedAt, $attemptedAt);

            return false;
        }

        if (blank(data_get($requestPayload, 'KullaniciKodu'))) {
            $message = 'İlan sahibinin panelinden EİDS kimlik doğrulamasını tamamlayın.';
            $this->markAsRejected($listing, [$message]);
            $this->logRequest($listing, $endpoint, $requestPayload, 'rejected', $message, null, $startedAt, $attemptedAt);

            return false;
        }

        if ($this->isAgentCompanyListing($listing) && ! $this->isGuid(data_get($requestPayload, 'KullaniciKodu'))) {
            $message = 'Agent hesabındaki EİDS kullanıcı kodu geçerli GUID formatında değil. Lütfen agent panelinden EİDS kimlik doğrulamasını yenileyin.';
            $this->markAsRejected($listing, [$message]);
            $this->logRequest($listing, $endpoint, $requestPayload, 'rejected', $message, null, $startedAt, $attemptedAt);

            return false;
        }

        try {
            $response = Http::withBasicAuth($this->settings->username(), $this->settings->password())
                ->acceptJson()
                ->asJson()
                ->timeout($this->settings->timeout())
                ->post($endpoint, $requestPayload)
                ->throw();
        } catch (RequestException $exception) {
            $response = $exception->response;
            if ($response->status() === 400) {
                $payload = $response->json() ?? [];
                $errors = $this->errorsFromPayload($payload, 'EİDS yetkilendirmesi doğrulanamadı.');
                $this->markAsRejected($listing, $errors, $payload);
                $this->logRequest($listing, $endpoint, $requestPayload, 'rejected', implode(' ', $errors), $response, $startedAt, $attemptedAt);

                return false;
            }

            $message = 'EİDS sorgusu başarısız oldu (HTTP '.$response->status().'). Lütfen daha sonra tekrar deneyin.';
            $this->markAsPending($listing, $message);
            $this->logRequest($listing, $endpoint, $requestPayload, 'pending', $message, $response, $startedAt, $attemptedAt);

            return false;
        } catch (ConnectionException $exception) {
            Log::warning('EİDS doğrulama isteği başarısız.', [
                'listing_id' => $listing->id,
                'message' => $exception->getMessage(),
            ]);

            $this->markAsPending($listing, 'EİDS servisine ulaşılamadı: '.$exception->getMessage());
            $this->logRequest($listing, $endpoint, $requestPayload, 'pending', $exception->getMessage(), $exception instanceof RequestException ? $exception->response : null, $startedAt, $attemptedAt);

            return false;
        } catch (Throwable $exception) {
            Log::error('EİDS doğrulama sırasında beklenmeyen hata.', [
                'listing_id' => $listing->id,
                'message' => $exception->getMessage(),
            ]);

            $this->markAsPending($listing, 'EİDS doğrulama sırasında hata oluştu: '.$exception->getMessage());
            $this->logRequest($listing, $endpoint, $requestPayload, 'error', $exception->getMessage(), null, $startedAt, $attemptedAt);

            return false;
        }

        $payload = $response->json();
        $statusCode = (int) data_get($payload, 'statusCode', $response->status());

        if ($statusCode !== 200 || blank(data_get($payload, 'data'))) {
            $errors = $this->errorsFromPayload($payload, 'EİDS doğrulaması reddedildi.');
            $this->markAsRejected($listing, $errors, $payload);
            $this->logRequest($listing, $endpoint, $requestPayload, 'rejected', implode(' ', $errors), $response, $startedAt, $attemptedAt);

            return false;
        }

        $this->markAsVerified($listing, data_get($payload, 'data', []), $payload);
        $this->logRequest($listing, $endpoint, $requestPayload, 'verified', null, $response, $startedAt, $attemptedAt);

        Log::info('EİDS doğrulama başarılı.', ['listing_id' => $listing->id]);

        return true;
    }

    private function isConfigured(): bool
    {
        return filled($this->settings->companyCode())
            && filled($this->settings->username())
            && filled($this->settings->password());
    }

    private function verifyUrl(): string
    {
        return $this->settings->verifyUrl();
    }

    private function requestPayload(Listing $listing): array
    {
        $listing->loadMissing(['user.businessProfile', 'businessProfile']);
        $userCode = $this->userCodeForListing($listing);
        $taxNumber = match ($listing->source_type) {
            'owner' => null,
            'real_estate_office' => $listing->businessProfile?->tax_number ?: $listing->user?->businessProfile?->tax_number,
            // Supervisor siteye giriş yapan kullanıcıdır; EİDS tarafında ise
            // Ziyade Emlak'ın İlhan Akduman üzerinden alınmış ofis kimliği
            // kullanılır. T.C. kimlik numarası VergiNo olarak gönderilemez.
            'zydemlak' => null,
            default => $this->settings->platformTaxNumber(),
        };

        return [
            'FirmaKod' => $this->settings->companyCode(),
            'KullaniciKodu' => $userCode,
            'VergiNo' => $taxNumber,
            'TasinmazId' => is_numeric($listing->eids_document_no) ? (float) $listing->eids_document_no : $listing->eids_document_no,
            'IlanNo' => (string) $listing->id,
        ];
    }

    private function userCodeForListing(Listing $listing): ?string
    {
        if ($listing->source_type === 'zydemlak' && $listing->user?->role === UserRole::SUPERVISOR) {
            return $this->settings->platformUserCode();
        }

        return $listing->user?->eids_user_code;
    }

    private function isAgentCompanyListing(Listing $listing): bool
    {
        return $listing->source_type === 'real_estate_office'
            && $listing->user?->role === UserRole::AGENT;
    }

    private function isGuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    private function logRequest(
        Listing $listing,
        string $endpoint,
        array $requestPayload,
        string $resultStatus,
        ?string $errorMessage,
        ?Response $response,
        float $startedAt,
        Carbon $attemptedAt,
    ): void {
        ListingEidsRequestLog::create([
            'listing_id' => $listing->id,
            ...$this->identityDiagnostics($listing, $requestPayload),
            'endpoint' => $endpoint,
            'method' => 'POST',
            'request_payload' => $this->sanitizeRequestPayload($requestPayload),
            'response_status' => $response?->status(),
            'response_payload' => $response?->json(),
            'result_status' => $resultStatus,
            'error_message' => $errorMessage,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'attempted_at' => $attemptedAt,
        ]);
    }

    private function identityDiagnostics(Listing $listing, array $requestPayload): array
    {
        $listing->loadMissing(['user.businessProfile', 'businessProfile']);

        $owner = $listing->user;
        $businessProfile = $listing->businessProfile ?: $owner?->businessProfile;
        $taxNumber = filled($requestPayload['VergiNo'] ?? null) ? (string) $requestPayload['VergiNo'] : null;
        $userCode = filled($requestPayload['KullaniciKodu'] ?? null) ? (string) $requestPayload['KullaniciKodu'] : null;
        $identityContext = $this->identityContextForListing($listing);

        return [
            'owner_user_id' => $owner?->id,
            'owner_name' => $owner?->name,
            'owner_role' => $owner?->role instanceof UserRole ? $owner->role->value : $owner?->role,
            'business_profile_id' => $listing->business_profile_id ?: $businessProfile?->id,
            'business_office_name' => $businessProfile?->office_name,
            'listing_source_type' => $listing->source_type,
            'authorization_subject_type' => filled($taxNumber) ? 'company' : 'person',
            'eids_identity_context' => $identityContext,
            'eids_user_code_source' => $this->userCodeSourceForListing($listing, $userCode),
            'eids_user_code_hash' => filled($userCode) ? hash('sha256', $userCode) : null,
            'tax_number_last4' => filled($taxNumber) ? substr($taxNumber, -4) : null,
            'sent_tax_number' => filled($taxNumber),
            'sent_user_code' => filled($userCode),
        ];
    }

    private function identityContextForListing(Listing $listing): string
    {
        if ($listing->source_type === 'zydemlak' && $listing->user?->role === UserRole::SUPERVISOR) {
            return 'zydemlak_office_identity';
        }

        return match ($listing->source_type) {
            'owner' => 'owner_person',
            'real_estate_office' => 'agent_company',
            default => 'user_account',
        };
    }

    private function userCodeSourceForListing(Listing $listing, ?string $userCode): string
    {
        if (blank($userCode)) {
            return 'missing';
        }

        if ($listing->source_type === 'zydemlak' && $listing->user?->role === UserRole::SUPERVISOR) {
            return 'settings.eids_user_code';
        }

        return 'users.eids_user_code';
    }

    private function markAsVerified(Listing $listing, array $data, array $payload): void
    {
        $expiresAt = $this->parseDate(data_get($data, 'IlanSuresi'));
        $listing->forceFill([
            'eids_status' => 'verified',
            'is_active' => ($listing->publication_status ?? 'approved') === 'approved' && (! $expiresAt || $expiresAt->isFuture()),
        ])->saveQuietly();

        $this->clearListingCache();

        $listing->eidsVerification()->updateOrCreate([], [
            'user_id' => $listing->user_id,
            'business_profile_id' => $listing->business_profile_id,
            'eids_user_code_hash' => filled($this->userCodeForListing($listing))
                ? hash('sha256', $this->userCodeForListing($listing))
                : null,
            'error_message' => null,
            'response' => $payload,
            'verified_at' => now(),
            'expires_at' => $expiresAt,
            'city' => data_get($data, 'Il'),
            'district' => data_get($data, 'Ilce'),
            'neighborhood' => data_get($data, 'Mahalle'),
            'block' => data_get($data, 'Ada'),
            'parcel' => data_get($data, 'Parsel'),
        ]);
    }

    private function markAsRejected(Listing $listing, array $errors, ?array $payload = null): void
    {
        $listing->forceFill([
            'eids_status' => 'rejected',
            'is_active' => false,
        ])->saveQuietly();

        $this->clearListingCache();

        $listing->eidsVerification()->updateOrCreate([], [
            'error_message' => implode(' ', array_filter($errors)),
            'response' => $payload,
            'verified_at' => null,
        ]);
    }

    private function markAsPending(Listing $listing, string $message): void
    {
        $listing->forceFill([
            'eids_status' => 'pending',
            'is_active' => false,
        ])->saveQuietly();

        $this->clearListingCache();

        $listing->eidsVerification()->updateOrCreate([], [
            'error_message' => $message,
            'verified_at' => null,
        ]);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function clearListingCache(): void
    {
        Cache::forget('home_recent_listings');
    }

    private function sanitizeRequestPayload(array $payload): array
    {
        if (filled($payload['KullaniciKodu'] ?? null)) {
            $payload['KullaniciKodu'] = 'sha256:'.hash('sha256', (string) $payload['KullaniciKodu']);
        }

        if (filled($payload['VergiNo'] ?? null)) {
            $taxNumber = (string) $payload['VergiNo'];
            $payload['VergiNo'] = str_repeat('*', max(strlen($taxNumber) - 4, 0)).substr($taxNumber, -4);
        }

        return $payload;
    }

    /**
     * EİDS farklı hata sözleşmeleriyle errors, message veya Messages döndürebilir.
     * Ham response ayrıca loglandığı için burada yalnızca kullanıcıya gösterilecek
     * okunabilir nedeni normalize ediyoruz.
     */
    private function errorsFromPayload(array $payload, string $fallback): array
    {
        foreach (['errors', 'Errors', 'message', 'Message', 'messages', 'Messages'] as $key) {
            $value = data_get($payload, $key);

            if (blank($value)) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            $messages = [];

            array_walk_recursive($values, function (mixed $message) use (&$messages): void {
                if (is_scalar($message) && filled((string) $message)) {
                    $messages[] = (string) $message;
                }
            });

            if ($messages !== []) {
                return array_values(array_unique($messages));
            }
        }

        return [$fallback];
    }
}

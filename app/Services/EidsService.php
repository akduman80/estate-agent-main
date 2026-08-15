<?php

namespace App\Services;

use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EidsService
{
    /**
     * İlanı EİDS taşınmaz yetki servisine gönderip doğrular.
     */
    public function verifyListing(Listing $listing): bool
    {
        if (! $this->isConfigured()) {
            $this->markAsPending($listing, 'EİDS ayarları eksik. EIDS_COMPANY_CODE, EIDS_USERNAME ve EIDS_PASSWORD değerlerini .env dosyasında tanımlayın.');

            return false;
        }

        $userCode = config('services.eids.user_code');

        if (blank($listing->eids_document_no) || blank($userCode)) {
            $this->markAsRejected($listing, ['Taşınmaz ID ve EIDS_USER_CODE ayarı zorunludur.']);

            return false;
        }

        try {
            $response = Http::withBasicAuth(config('services.eids.username'), config('services.eids.password'))
                ->acceptJson()
                ->asJson()
                ->timeout(config('services.eids.timeout', 20))
                ->post($this->verifyUrl(), [
                    'FirmaKod' => config('services.eids.company_code'),
                    'KullaniciKodu' => $userCode,
                    'VergiNo' => config('services.eids.tax_no'),
                    'TasinmazId' => is_numeric($listing->eids_document_no) ? (float) $listing->eids_document_no : $listing->eids_document_no,
                    'IlanNo' => (string) $listing->id,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('EİDS doğrulama isteği başarısız.', [
                'listing_id' => $listing->id,
                'message' => $exception->getMessage(),
            ]);

            $this->markAsPending($listing, 'EİDS servisine ulaşılamadı: '.$exception->getMessage());

            return false;
        } catch (Throwable $exception) {
            Log::error('EİDS doğrulama sırasında beklenmeyen hata.', [
                'listing_id' => $listing->id,
                'message' => $exception->getMessage(),
            ]);

            $this->markAsPending($listing, 'EİDS doğrulama sırasında hata oluştu: '.$exception->getMessage());

            return false;
        }

        $payload = $response->json();
        $statusCode = (int) data_get($payload, 'statusCode', $response->status());

        if ($statusCode !== 200 || blank(data_get($payload, 'data'))) {
            $errors = data_get($payload, 'errors', ['EİDS doğrulaması reddedildi.']);
            $this->markAsRejected($listing, is_array($errors) ? $errors : [$errors], $payload);

            return false;
        }

        $this->markAsVerified($listing, data_get($payload, 'data', []), $payload);

        Log::info('EİDS doğrulama başarılı.', ['listing_id' => $listing->id]);

        return true;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.eids.company_code'))
            && filled(config('services.eids.username'))
            && filled(config('services.eids.password'));
    }

    private function verifyUrl(): string
    {
        return rtrim(config('services.eids.base_url'), '/').'/'.ltrim(config('services.eids.verify_path'), '/');
    }

    private function markAsVerified(Listing $listing, array $data, array $payload): void
    {
        $listing->forceFill([
            'eids_status' => 'verified',
        ])->saveQuietly();

        $listing->eidsVerification()->updateOrCreate([], [
            'error_message' => null,
            'response' => $payload,
            'verified_at' => now(),
            'expires_at' => $this->parseDate(data_get($data, 'IlanSuresi')),
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
        ])->saveQuietly();

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
        ])->saveQuietly();

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
}

<?php

namespace App\Observers;

use App\Jobs\ProcessListingImage;
use App\Models\Listing;
use App\Models\ListingChangeLog;
use App\Services\EidsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ListingObserver
{
    /**
     * Handle the Listing "created" event.
     */
    public function created(Listing $listing): void
    {
        // Görsel İşleme (Queue). EİDS sorgusu taslak kaydedildikten sonra kullanıcı tarafından başlatılır.
        $images = request()->input('components.0.snapshot', null); // Livewire v3 payload fallback

        // Veya doğrudan request'ten gelen veriyi alalım
        if (request()->has('images') || ! empty(request()->input('images'))) {
            $images = request()->input('images', []);

            foreach ($images as $index => $tempPath) {
                if (is_string($tempPath)) {
                    $isMain = ($index === 0);
                    ProcessListingImage::dispatch($tempPath, $listing->id, $isMain);
                }
            }
        }
    }

    /**
     * Handle the Listing "updated" event.
     */
    public function updated(Listing $listing): void
    {
        if ($listing->wasChanged(['eids_document_no'])) {
            app(EidsService::class)->verifyListing($listing);
        }

        foreach ($listing->getChanges() as $field => $newValue) {
            if (in_array($field, ['updated_at'], true)) {
                continue;
            }

            ListingChangeLog::create([
                'listing_id' => $listing->id,
                'user_id' => auth()->id(),
                'field' => $field,
                'old_value' => $this->serializeValue($listing->getOriginal($field)),
                'new_value' => $this->serializeValue($newValue),
                'ip_address' => request()?->ip(),
                'changed_at' => now(),
            ]);
        }
    }

    public function updating(Listing $listing): void
    {
        if ($listing->getOriginal('eids_status') === 'verified') {
            $verifiedFields = ['eids_document_no', 'block_number', 'parcel_number', 'city_id', 'district_id', 'neighborhood_id'];
            $changedVerifiedFields = collect($verifiedFields)->filter(fn (string $field): bool => $listing->isDirty($field));

            if ($changedVerifiedFields->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'listing' => 'EİDS ile doğrulanmış resmî alanlar değiştirilemez: '.$changedVerifiedFields->implode(', '),
                ]);
            }
        }

        if (! $listing->getOriginal('published_at')) {
            return;
        }

        $immutable = [
            'user_id', 'business_profile_id', 'source_type', 'eids_document_no',
            'block_number', 'parcel_number', 'city_id', 'district_id', 'neighborhood_id',
            'category', 'type',
        ];

        // Resmî EİDS cevabındaki ilçe ve mahalle de değiştirilemez. 48 saatlik düzeltme
        // yalnızca resmî kaynaktan gelmeyen son alt kategori için geçerlidir.
        if ($listing->published_at?->addHours(48)->isPast()) {
            $immutable[] = 'subcategory';
        }

        $changed = collect($immutable)->filter(fn (string $field): bool => $listing->isDirty($field));

        if ($changed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'listing' => 'Yayınlanmış ilanda değiştirilemeyen alanlar güncellenemez: '.$changed->implode(', '),
            ]);
        }
    }

    public function saved(Listing $listing): void
    {
        $this->clearListingCache();
    }

    /**
     * Handle the Listing "deleted" event.
     */
    public function deleted(Listing $listing): void
    {
        $this->clearListingCache();
    }

    /**
     * Handle the Listing "restored" event.
     */
    public function restored(Listing $listing): void
    {
        //
    }

    /**
     * Handle the Listing "force deleted" event.
     */
    public function forceDeleted(Listing $listing): void
    {
        //
    }

    private function clearListingCache(): void
    {
        // İlanlarla ilgili tüm Redis önbellekleri sıfırlanır
        Cache::forget('home_recent_listings');
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

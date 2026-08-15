<?php

namespace App\Observers;

use App\Jobs\ProcessListingImage;
use App\Models\Listing;
use App\Services\EidsService;
use Illuminate\Support\Facades\Cache;

class ListingObserver
{
    /**
     * Handle the Listing "created" event.
     */
    public function created(Listing $listing): void
    {
        // 1. EİDS Doğrulama
        app(EidsService::class)->verifyListing($listing);

        // 2. Görsel İşleme (Queue)
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
}

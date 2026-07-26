<?php

namespace App\Services;

use App\Models\Listing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EidsService
{
    /**
     * İlanı EİDS servisine gönderip doğrular.
     */
    public function verifyListing(Listing $listing): bool
    {
        // Canlıda Bakanlık Endpoint'ine HTTP isteği atılacak:
        // $response = Http::withHeaders([...])->post('https://eids.gtb.gov.tr/api/v1/verify', [...]);

        // TEST / MOCK ORTAMI:
        // Taşınmaz yetki nosu doluysa başarılı kabul edelim
        if (!empty($listing->eids_document_no)) {
            $listing->update([
                'eids_status' => 'verified',
            ]);

            Log::info("EİDS Doğrulama Başarılı: İlan ID {$listing->id}");
            return true;
        }

        $listing->update([
            'eids_status' => 'rejected',
        ]);

        return false;
    }
}

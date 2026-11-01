<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ListingPhoto;

class ListingPhotoController
{
    public function __invoke(ListingImage $image, ListingPhoto $photos)
    {
        abort_unless(Listing::query()->published()->whereKey($image->listing_id)->exists(), 404);
        return response()->file($photos->cached($image), [
            'Content-Type' => 'image/webp',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}

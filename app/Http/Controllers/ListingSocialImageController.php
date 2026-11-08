<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\ListingSocialPreview;

class ListingSocialImageController extends Controller
{
    public function __invoke(Listing $listing, string $version, ListingSocialPreview $preview)
    {
        abort_unless(Listing::query()->published()->whereKey($listing->getKey())->exists(), 404);
        $image = $preview->image($listing);

        // Old URLs resolve to the current valid photo after deletion or replacement.
        if (! hash_equals($image['version'], $version)) {
            return redirect($image['url'], 302)->header('Cache-Control', 'no-store');
        }

        return response($image['bytes'], 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

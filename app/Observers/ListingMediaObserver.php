<?php

namespace App\Observers;

use App\Models\Listing;
use App\Services\ListingMediaService;
use Illuminate\Support\Facades\DB;

class ListingMediaObserver
{
    public function updated(Listing $listing): void
    {
        if ($listing->wasChanged('video_path')) {
            $oldPath = $listing->getRawOriginal('video_path');
            DB::afterCommit(fn () => app(ListingMediaService::class)->deleteUnusedFile($oldPath));
        }
    }
}

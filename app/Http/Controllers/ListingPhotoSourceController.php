<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ListingImage;
use Illuminate\Support\Facades\Storage;

/** Nginx sends legacy static photo URLs here; videos and other media stay untouched. */
class ListingPhotoSourceController
{
    public function __invoke(string $path)
    {
        $path = 'listings/'.$path;
        abort_unless(preg_match('~^listings/(?!.*\.\.)(?!.*\\\\)[^\x00]+\.(?:jpe?g|png|webp)$~iD', $path), 404);
        $image = ListingImage::query()->where('image_path', $path)->whereHas('listing', fn ($q) => $q->published())->first();
        if ($image) {
            return app(ListingPhotoController::class)($image, app(\App\Services\ListingPhoto::class));
        }
        $user = auth()->user();
        abort_unless($user, 404);
        $ownsUpload = str_starts_with($path, 'listings/images/'.$user->id.'/');
        $ownsListing = ListingImage::query()->where('image_path', $path)->whereHas('listing', fn ($q) => $q
            ->where('user_id', $user->id)->orWhere('created_by_id', $user->id))->exists();
        abort_unless($user->role === UserRole::SUPERVISOR || $ownsUpload || $ownsListing, 404);
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}

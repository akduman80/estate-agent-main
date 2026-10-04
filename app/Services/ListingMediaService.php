<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListingMediaService
{
    public function syncImages(Listing $listing, array $paths): void
    {
        $paths = array_values(array_unique($paths));
        if (count($paths) > config('listings.photos.max_files')) {
            throw ValidationException::withMessages(['data.uploaded_images' => 'En fazla 30 fotoğraf yükleyebilirsiniz.']);
        }

        DB::transaction(function () use ($listing, $paths): void {
            Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            $images = $listing->images()->get();

            foreach ($paths as $path) {
                $existing = $images->contains('image_path', $path);
                if (! is_string($path) || str_contains($path, '..') || (! $existing && (
                    ! str_starts_with($path, 'listings/images/'.auth()->id().'/')
                    || ! Storage::disk('public')->exists($path)
                    || ListingImage::query()->where('image_path', $path)->where('listing_id', '!=', $listing->id)->exists()
                ))) {
                    throw ValidationException::withMessages(['data.uploaded_images' => 'Fotoğraf yüklemesi doğrulanamadı. Fotoğrafı tekrar seçin.']);
                }
            }

            foreach ($images as $image) {
                if (! in_array($image->image_path, $paths, true)) {
                    $path = $image->image_path;
                    $image->delete();
                    DB::afterCommit(fn () => $this->deleteUnusedFile($path));
                }
            }

            foreach ($paths as $index => $path) {
                $image = $images->firstWhere('image_path', $path);
                if ($image) {
                    $image->update(['is_main' => $index === 0, 'sort_order' => $index]);
                } else {
                    $listing->images()->create(['image_path' => $path, 'is_main' => $index === 0, 'sort_order' => $index]);
                }
            }

            $listing->unsetRelation('images');
        });
    }

    public function deleteUnusedFile(?string $path): void
    {
        // Never remove shared seed/default files or paths outside listing uploads.
        if (! $path || ! str_starts_with($path, 'listings/') || str_contains($path, '..')) {
            return;
        }

        if (ListingImage::query()->where('image_path', $path)->exists()
            || Listing::query()->where('video_path', $path)->exists()) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function publicUrl(string $path): string
    {
        // Local previews must use the panel origin, regardless of APP_URL/www/HTTPS.
        return config('filesystems.disks.public.driver') === 'local'
            ? '/storage/'.implode('/', array_map('rawurlencode', explode('/', $path)))
            : Storage::disk('public')->url($path);
    }
}

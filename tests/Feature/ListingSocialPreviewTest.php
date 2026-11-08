<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ListingSocialPreview;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingSocialPreviewTest extends TestCase
{
    private function listing(array $images): Listing
    {
        $listing = new Listing(['title' => 'Test ev', 'slug' => 'test-ev', 'description' => '<p>Ev &amp; bahçe</p><p>Satılık</p>']);
        $listing->id = 4259;
        $listing->setRelation('images', collect(array_map(fn ($attributes) => new ListingImage($attributes), $images)));

        return $listing;
    }

    public function test_cover_fallback_replacement_and_dimensions(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('first.png', file_get_contents(public_path('fe-assets/assets/img/zydemlak-logo.png')));
        $disk->put('cover.png', file_get_contents(public_path('fe-assets/assets/img/zydemlak-logo-transparent.png')));
        $service = app(ListingSocialPreview::class);
        $listing = $this->listing([
            ['image_path' => 'first.png', 'sort_order' => 0],
            ['image_path' => 'cover.png', 'is_main' => true, 'sort_order' => 1],
        ]);
        $cover = $service->image($listing);
        $listing->setRelation('images', collect([$listing->images->last()]));
        $this->assertSame($cover['version'], $service->image($listing)['version']);
        $disk->put('cover.png', $disk->get('first.png'));
        $this->assertNotSame($cover['version'], $service->image($listing)['version']);
        $disk->delete('cover.png');
        $fallback = $service->image($listing);
        $dimensions = getimagesizefromstring($fallback['bytes']);
        $this->assertSame([1200, 630], array_slice($dimensions, 0, 2));
        $this->assertSame('image/jpeg', $dimensions['mime']);
        $this->assertSame('Ev & bahçe Satılık', $service->description($listing));
    }

    public function test_corrupt_cover_uses_next_valid_photo_and_listing_keys_are_isolated(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('broken.jpg', 'broken');
        Storage::disk('public')->put('valid.png', file_get_contents(public_path('fe-assets/assets/img/zydemlak-logo.png')));
        $service = app(ListingSocialPreview::class);
        $listing = $this->listing([
            ['image_path' => 'broken.jpg', 'is_main' => true],
            ['image_path' => 'valid.png'],
        ]);
        $image = $service->image($listing);
        $listing->setRelation('images', collect([$listing->images->last()]));
        $this->assertSame($image['version'], $service->image($listing)['version']);
        $listing->id = 4260;
        $this->assertNotSame($image['version'], $service->image($listing)['version']);
    }
}

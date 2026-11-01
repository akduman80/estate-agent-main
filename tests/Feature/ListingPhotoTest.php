<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingPhotoTest extends TestCase
{
    use RefreshDatabase;

    private array $cacheFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->cacheFiles as $file) {
            @unlink($file);
            @unlink(substr($file, 0, -5).'.lock');
        }
        parent::tearDown();
    }

    public function test_photo_is_baked_cached_and_original_is_preserved(): void
    {
        Storage::fake('public');
        // Local Windows FreeType requires an ASCII path; production uses the normal public path.
        if ($publicPath = getenv('TEST_PHOTO_PUBLIC_PATH')) {
            $this->app->usePublicPath($publicPath);
        }
        $canvas = imagecreatetruecolor(900, 600);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 80, 100, 120));
        ob_start(); imagepng($canvas); $original = ob_get_clean(); imagedestroy($canvas);
        Storage::disk('public')->put('listings/images/1/photo.png', $original);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => User::factory()->create()->id, 'title' => 'Photo test',
            'slug' => 'photo-test', 'price' => 1000, 'type' => 'Satılık',
            'city_id' => 'İstanbul', 'district_id' => 'Kadıköy', 'neighborhood_id' => 'Test',
            'publication_status' => 'approved', 'is_active' => true, 'eids_status' => 'verified',
        ]));
        $image = $listing->images()->create(['image_path' => 'listings/images/1/photo.png', 'is_main' => true]);
        $file = app(ListingPhoto::class)->cached($image);
        $this->cacheFiles[] = $file;
        $this->assertSame([900, 600], array_slice(getimagesize($file), 0, 2));
        $decoded = imagecreatefromwebp($file);
        // The single, faint logo is centered; the outer photograph is preserved.
        $changed = 0;
        for ($y = 180; $y < 420; $y++) {
            for ($x = 234; $x < 666; $x++) {
                $color = imagecolorsforindex($decoded, imagecolorat($decoded, $x, $y));
                if (abs($color['red'] - 80) > 5 || abs($color['green'] - 100) > 5 || abs($color['blue'] - 120) > 5) { $changed++; }
            }
        }
        $this->assertGreaterThan(1000, $changed);
        $corner = imagecolorsforindex($decoded, imagecolorat($decoded, 10, 10));
        $this->assertLessThan(5, abs($corner['red'] - 80));
        imagedestroy($decoded);
        $hash = hash_file('sha256', $file);
        $this->assertSame($file, app(ListingPhoto::class)->cached($image));
        $this->assertSame($hash, hash_file('sha256', $file));
        $this->assertSame($original, Storage::disk('public')->get($image->image_path));
        $this->get(ListingPhoto::url($image))->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get(route('listings.show', $listing->slug))->assertOk()
            ->assertSee('Görseller izinsiz kullanılamaz')
            ->assertSee('draggable="false"', false)
            ->assertSee('/listing-photo/'.$image->id, false)
            ->assertDontSee('/storage/listings/images/1/photo.png', false);
        $this->get('/storage/'.$image->image_path)->assertOk()->assertHeader('Content-Type', 'image/webp');
        config(['cloudflare_r2.enabled' => true, 'app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        $this->get(\App\Storage\R2MediaFilesystem::mediaUrl($image->image_path))->assertOk()->assertHeader('Content-Type', 'image/webp');
        Listing::withoutEvents(fn () => $listing->update(['is_active' => false]));
        $this->get('/storage/'.$image->image_path)->assertNotFound();
        $this->actingAs($listing->user)->get('/storage/'.$image->image_path)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->create())->get('/storage/'.$image->image_path)->assertNotFound();
        auth()->forgetGuards();
        $this->get(ListingPhoto::url($image))->assertNotFound();
        $this->get('/listing-photo/99999999')->assertNotFound();
    }

    public function test_traversal_is_rejected_before_reading_storage(): void
    {
        $image = new ListingImage(['image_path' => '../secret.png']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ListingPhoto::class)->cached($image);
    }
}

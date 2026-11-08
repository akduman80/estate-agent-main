<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Services\ListingSocialPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingSocialResponseTest extends TestCase
{
    use RefreshDatabase;

    private function publishedListing(): Listing
    {
        $this->withoutVite();
        Storage::fake('public');
        config(['app.url' => 'https://www.zydemlak.com']);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Hasanbeyli test ev', 'slug' => 'hasanbeyli-test-ev',
            'description' => '<p>Bahçeli ev &amp; doğa</p>',
            'price' => 3000000, 'type' => 'Satılık', 'category' => 'Müstakil Ev',
            'city_id' => '80', 'district_id' => 'Hasanbeyli',
            'publication_status' => 'approved', 'is_active' => true, 'eids_status' => 'verified',
        ]));
        Storage::disk('public')->put('listings/test-cover.png', UploadedFile::fake()->image('cover.png', 800, 600)->get());
        $listing->images()->create(['image_path' => 'listings/test-cover.png', 'is_main' => true]);

        return $listing;
    }

    public function test_first_html_contains_complete_metadata_and_image_is_public_jpeg(): void
    {
        $listing = $this->publishedListing();
        $image = app(ListingSocialPreview::class)->image($listing);
        foreach (['WhatsApp/2.24', 'facebookexternalhit/1.1', 'TelegramBot', 'Mozilla/5.0'] as $agent) {
            $response = $this->withHeader('User-Agent', $agent)->get(route('listings.show', $listing->slug));
            $response->assertOk()->assertSee('<title>Hasanbeyli test ev | ZİYADE EMLAK</title>', false);
            $head = explode('</head>', $response->getContent())[0];
            $this->assertStringContainsString('<meta property="og:image" content="'.$image['url'].'">', $head);
            $this->assertStringContainsString('<link rel="canonical" href="https://www.zydemlak.com/ilan/hasanbeyli-test-ev">', $head);
            $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $head);
            $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $head);
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('css/listing-share.css?v=', $head);
            $response->assertSeeInOrder(['EİDS doğrulamalı ilan', 'data-listing-share', 'WhatsApp', 'Facebook', 'Telegram', 'Messenger', 'Instagram', 'data-share-copy'], false);
            $response->assertSee('js/listing-share.js?v=', false);
            $response->assertDontSee('id="listing-native-share" hidden', false);
        }
        $jpeg = $this->get($image['url'])->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame([1200, 630], array_slice(getimagesizefromstring($jpeg->getContent()), 0, 2));
        Listing::withoutEvents(fn () => $listing->update(['title' => 'Yeni başlık', 'description' => '<p>Yeni açıklama</p>']));
        $this->get(route('listings.show', $listing->slug))->assertOk()->assertSee('content="Yeni başlık"', false)->assertSee('content="Yeni açıklama"', false);
    }

    public function test_removed_cover_redirects_old_url_and_unpublished_listing_is_not_exposed(): void
    {
        $listing = $this->publishedListing();
        $old = app(ListingSocialPreview::class)->image($listing);
        Storage::disk('public')->delete('listings/test-cover.png');
        $current = app(ListingSocialPreview::class)->image($listing);
        $this->assertNotSame($old['url'], $current['url']);
        $this->get($old['url'])->assertRedirect($current['url']);
        $this->get($current['url'])->assertOk();
        Listing::withoutEvents(fn () => $listing->update(['is_active' => false]));
        $this->get($current['url'])->assertNotFound();
        $this->get(route('listings.show', $listing->slug))->assertNotFound();
    }
}

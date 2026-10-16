<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteAdvertisementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_banners_are_not_rendered_and_records_are_preserved(): void
    {
        Advertisement::create([
            'label' => 'Ortak reklam', 'image_path' => 'site/ads/active-edge.jpg',
            'horizontal_image_path' => 'site/ads/active-horizontal.jpg',
            'placement' => 'left', 'is_active' => true,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
        ]);
        foreach ([['is_active' => false], ['starts_at' => now()->addDay()], ['ends_at' => now()->subDay()]] as $index => $state) {
            Advertisement::create(array_merge([
                'label' => 'Gizli reklam', 'image_path' => "site/ads/hidden-$index.jpg",
                'horizontal_image_path' => "site/ads/hidden-horizontal-$index.jpg",
                'placement' => 'right', 'is_active' => true,
            ], $state));
        }

        foreach (['/', '/ilanlar', '/haberler', '/projeler', '/harita', '/iletisim', '/kayit', '/sifremi-unuttum'] as $url) {
            $response = $this->get($url)->assertOk()
                ->assertDontSee('site/ads/active-edge.jpg', false)
                ->assertDontSee('site/ads/active-horizontal.jpg', false);
            $this->assertSame(0, substr_count($response->getContent(), 'class="home-edge-ads"'), $url);
            foreach (range(0, 2) as $index) {
                $response->assertDontSee("site/ads/hidden-$index.jpg", false)
                    ->assertDontSee("site/ads/hidden-horizontal-$index.jpg", false);
            }
        }
        $this->assertDatabaseCount('advertisements', 4);
    }

    public function test_other_pages_have_no_empty_ad_placeholders(): void
    {
        $this->get('/iletisim')->assertOk()
            ->assertDontSee('home-edge-ad-empty', false)
            ->assertDontSee('home-horizontal-ad-placeholder', false)
            ->assertDontSee('Reklam için iletişim');
    }

    public function test_home_has_one_ordered_ad_before_listings(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-home-print-ad'));
        $first = strpos($html, 'reklam-01-fincan-kupa.jpg');
        $second = strpos($html, 'reklam-02-tisort-sweatshirt.jpg');
        $third = strpos($html, 'reklam-03-sapka-dtf.jpg');
        $this->assertNotFalse($first);
        $this->assertTrue($first < $second && $second < $third);
        $this->assertTrue(strpos($html, 'id="piyasalar"') < $first);
        $this->assertTrue($third < strpos($html, 'Öne Çıkan İlanlar'));
    }

    public function test_panel_record_overrides_slot_and_respects_publication(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        $record = Advertisement::create(['label' => 'Yeni fincan', 'image_path' => 'site/ads/new.jpg', 'placement' => 'home01', 'is_active' => true, 'target_url' => 'https://wa.me/905303607769?text=Yeni']);
        $this->get('/')->assertOk()->assertSee('site/ads/new.jpg', false)->assertSee('text=Yeni', false)->assertDontSee('reklam-01-fincan-kupa.jpg', false);
        foreach ([['is_active' => false], ['is_active' => true, 'starts_at' => now()->addDay()], ['starts_at' => null, 'ends_at' => now()->subDay()]] as $state) {
            $record->update($state);
            $this->get('/')->assertOk()->assertDontSee('site/ads/new.jpg', false)->assertDontSee('reklam-01-fincan-kupa.jpg', false)->assertSee('reklam-02-tisort-sweatshirt.jpg', false);
        }
    }
}

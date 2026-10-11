<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteAdvertisementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_pages_share_active_banners_and_hide_unpublished_ads(): void
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
                ->assertSee('site/ads/active-edge.jpg', false)
                ->assertSee('site/ads/active-horizontal.jpg', false);
            $this->assertSame(1, substr_count($response->getContent(), 'class="home-edge-ads"'), $url);
            foreach (range(0, 2) as $index) {
                $response->assertDontSee("site/ads/hidden-$index.jpg", false)
                    ->assertDontSee("site/ads/hidden-horizontal-$index.jpg", false);
            }
        }
    }

    public function test_empty_ad_slots_keep_the_branded_placeholders_on_other_pages(): void
    {
        $this->get('/iletisim')->assertOk()
            ->assertSee('home-edge-ad-empty', false)
            ->assertSee('home-horizontal-ad-placeholder', false)
            ->assertSee('Reklam için iletişim');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_filter_searches_listing_number_and_location_fields(): void
    {
        $listing = $this->createPublishedListing([
            'title' => 'Bahçeli müstakil ev',
            'city_id' => 'Osmaniye',
            'district_id' => 'Kadirli',
            'neighborhood_id' => 'Şehit Vedat Kocadallı',
        ]);

        $otherListing = $this->createPublishedListing([
            'title' => 'Merkezi kiralık ofis',
            'city_id' => 'Ankara',
            'district_id' => 'Çankaya',
            'neighborhood_id' => 'Kızılay',
        ]);

        foreach (['Kadirli', 'Kocadallı', (string) $listing->getKey()] as $search) {
            $response = $this->get(route('listings.index', ['q' => $search]));

            $response->assertOk();
            $this->assertSame([$listing->getKey()], $response->viewData('listings')->pluck('id')->all());
        }
    }

    public function test_filter_shows_categories_used_by_published_listings(): void
    {
        $listing = $this->createPublishedListing([
            'title' => 'Deniz manzaralı villa',
            'category' => 'Villa',
        ]);

        $response = $this->get(route('listings.index'));

        $response->assertOk();
        $this->assertContains('Villa', $response->viewData('categoryOptions'));

        $this->get(route('listings.index', ['category' => 'Villa']))
            ->assertOk()
            ->assertSee($listing->title);
    }

    private function createPublishedListing(array $attributes = []): Listing
    {
        $user = User::factory()->create();

        return Listing::withoutEvents(fn () => Listing::create(array_merge([
            'user_id' => $user->getKey(),
            'title' => 'Satılık daire',
            'slug' => fake()->unique()->slug(),
            'description' => 'Test ilanı',
            'price' => 1500000,
            'currency' => 'TRY',
            'type' => 'Satılık',
            'category' => 'Konut',
            'city_id' => 'İstanbul',
            'district_id' => 'Kadıköy',
            'neighborhood_id' => 'Fenerbahçe',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'published_at' => now(),
            'is_active' => true,
        ], $attributes)));
    }
}

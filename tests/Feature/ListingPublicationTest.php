<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_detail_is_not_public_until_eids_is_verified(): void
    {
        $user = User::factory()->create();
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'EİDS bekleyen ilan',
            'slug' => 'eids-bekleyen-ilan',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_status' => 'pending',
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Sumbas',
            'is_active' => true,
        ]));

        $this->get(route('listings.show', $listing))->assertNotFound();

        $listing->forceFill(['eids_status' => 'verified'])->saveQuietly();

        $this->get(route('listings.show', $listing))->assertOk();
    }

    public function test_listing_index_only_shows_verified_active_listings(): void
    {
        $user = User::factory()->create();

        Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'Yayındaki doğrulanmış ilan',
            'slug' => 'yayindaki-dogrulanmis-ilan',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_status' => 'verified',
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Sumbas',
            'is_active' => true,
        ]));

        Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'Bekleyen aktif ilan',
            'slug' => 'bekleyen-aktif-ilan',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'square_meters' => 500,
            'eids_status' => 'pending',
            'eids_document_no' => '94536594',
            'city_id' => '80',
            'district_id' => 'Sumbas',
            'is_active' => true,
        ]));

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertViewHas('listings', function ($listings): bool {
                $titles = $listings->getCollection()->pluck('title');

                return $titles->contains('Yayındaki doğrulanmış ilan')
                    && ! $titles->contains('Bekleyen aktif ilan');
            });
    }
}

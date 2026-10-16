<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Listing;
use App\Models\ListingEidsVerification;
use App\Support\ListingEidsCard;
use Tests\TestCase;

class ListingEidsCardTest extends TestCase
{
    private function listing(): Listing
    {
        $listing = new Listing(['source_type' => 'real_estate_office', 'eids_status' => 'verified', 'user_id' => 7, 'business_profile_id' => 2]);
        $listing->id = 12;
        $listing->setRelation('user', null);
        $listing->setRelation('businessProfile', new BusinessProfile(['office_name' => 'ZİYADE EMLAK', 'license_number' => '8000022']));
        $listing->setRelation('eidsVerification', new ListingEidsVerification([
            'listing_id' => 12, 'user_id' => 7, 'business_profile_id' => 2,
            'verified_at' => now()->subMinute(), 'expires_at' => now()->addDay(),
        ]));

        return $listing;
    }

    public function test_business_details_come_from_the_associated_office(): void
    {
        $listing = $this->listing();
        $this->assertSame('ZİYADE EMLAK – Yetki Belge No: 8000022', ListingEidsCard::forListing($listing)['title']);
        $listing->businessProfile->office_name = str_repeat('Başka işletme ', 30);
        $listing->businessProfile->license_number = '12345678901234567890';
        $this->assertSame(trim($listing->businessProfile->office_name).' – Yetki Belge No: 12345678901234567890', ListingEidsCard::forListing($listing)['title']);
        $listing->businessProfile->license_number = ' ';
        $this->assertNull(ListingEidsCard::forListing($listing));
        $listing->setRelation('businessProfile', null);
        $this->assertNull(ListingEidsCard::forListing($listing));
    }

    public function test_individual_card_has_no_business_details(): void
    {
        $listing = $this->listing();
        $listing->source_type = 'owner';
        $listing->business_profile_id = null;
        $listing->eidsVerification->business_profile_id = null;
        $listing->setRelation('businessProfile', null);
        $this->assertSame(['title' => 'Bu Gayrimenkul İçin Eids Onayı Alınmıştır', 'description' => null], ListingEidsCard::forListing($listing));
    }

    public function test_unverified_and_stale_authorizations_never_show_a_card(): void
    {
        foreach (['pending', 'rejected', 'cancelled', 'expired', null] as $status) {
            $listing = $this->listing();
            $listing->eids_status = $status;
            $this->assertNull(ListingEidsCard::forListing($listing));
        }
        foreach (['verified_at' => null, 'expires_at' => now()->subSecond(), 'error_message' => 'İptal', 'user_id' => 9, 'business_profile_id' => 9, 'listing_id' => 9] as $key => $value) {
            $listing = $this->listing();
            $listing->eidsVerification->$key = $value;
            $this->assertNull(ListingEidsCard::forListing($listing));
        }
        $listing = $this->listing();
        $listing->setRelation('eidsVerification', null);
        $this->assertNull(ListingEidsCard::forListing($listing));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ListingQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_types_have_the_expected_default_limits(): void
    {
        $service = app(ListingQuotaService::class);

        $individual = User::factory()->create(['role' => UserRole::INDIVIDUAL]);
        $agent = User::factory()->create(['role' => UserRole::AGENT]);

        $this->assertSame(10, $service->effectiveLimit($individual));
        $this->assertSame(100, $service->effectiveLimit($agent));
    }

    public function test_supervisor_override_replaces_the_membership_default(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'listing_limit_override' => 3,
        ]);

        $this->assertSame(3, app(ListingQuotaService::class)->effectiveLimit($user));
    }

    public function test_only_active_approved_listings_count_toward_the_limit(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'listing_limit_override' => 1,
        ]);

        $this->listing($user, ['publication_status' => 'draft', 'is_active' => false]);
        $this->assertFalse(app(ListingQuotaService::class)->hasReachedLimit($user));

        $this->listing($user, ['slug' => 'active-listing', 'publication_status' => 'approved', 'is_active' => true]);
        $this->assertTrue(app(ListingQuotaService::class)->hasReachedLimit($user));
    }

    public function test_limit_service_returns_the_required_message(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'listing_limit_override' => 0,
        ]);

        try {
            app(ListingQuotaService::class)->assertAvailable($user);
            $this->fail('Limit kontrolü ilan oluşturmayı engellemedi.');
        } catch (ValidationException $exception) {
            $this->assertSame('Aktif ilan limitinize ulaştınız.', $exception->errors()['listing_limit'][0]);
        }
    }

    private function listing(User $user, array $attributes = []): Listing
    {
        return Listing::withoutEvents(fn () => Listing::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Kota Test İlanı',
            'slug' => 'quota-test-listing',
            'price' => 1000000,
            'city_id' => 'Osmaniye',
            'district_id' => 'Merkez',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'is_active' => true,
        ], $attributes)));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\ListListings;
use App\Mail\ApprovalRequestedMail;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingPublicationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ListingApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_attempt_checks_eids_and_keeps_rejected_listing_as_draft(): void
    {
        Mail::fake();
        config()->set('services.eids.company_code', 'company-code');
        config()->set('services.eids.username', 'eids-user');
        config()->set('services.eids.password', 'eids-pass');
        config()->set('services.eids.user_code', '11111111-1111-4111-8111-111111111111');
        config()->set('services.eids.base_url', 'https://eids.test');
        config()->set('services.eids.verify_path', '/verify');
        Http::fake([
            'eids.test/verify' => Http::response([
                'statusCode' => 400,
                'errors' => ['Bu taşınmaz için geçerli ilan yetkisi bulunamadı.'],
            ], 400),
        ]);

        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
            'eids_user_code' => '11111111-1111-4111-8111-111111111111',
            'eids_identity_verified_at' => now(),
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $owner->id,
            'title' => 'EİDS reddedilen ilan', 'slug' => 'eids-reddedilen-ilan', 'price' => 1000000,
            'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'owner',
            'eids_status' => 'pending', 'publication_status' => 'draft', 'eids_document_no' => '12345',
            'city_id' => '80', 'district_id' => 'Merkez',
        ]));

        $this->expectException(ValidationException::class);
        try {
            app(ListingPublicationService::class)->submit($listing->fresh(['eidsVerification']), $owner, true);
        } finally {
            $listing->refresh();
            $this->assertSame('draft', $listing->publication_status);
            $this->assertFalse($listing->is_active);
            $this->assertSame('rejected', $listing->eids_status);
            $this->assertDatabaseHas('listing_eids_verifications', [
                'listing_id' => $listing->id,
                'error_message' => 'Bu taşınmaz için geçerli ilan yetkisi bulunamadı.',
            ]);
            Mail::assertNothingSent();
        }
    }

    public function test_verified_listing_requires_admin_approval_and_locks_official_fields(): void
    {
        Mail::fake();
        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
            'eids_user_code' => '11111111-1111-4111-8111-111111111111',
            'eids_identity_verified_at' => now(),
        ]);
        $admin = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $owner->id,
            'title' => 'Onay Test İlanı', 'slug' => 'onay-test-ilani', 'price' => 1000000,
            'type' => 'Satılık', 'category' => 'Arsa', 'source_type' => 'owner',
            'eids_status' => 'verified', 'publication_status' => 'draft', 'eids_document_no' => '12345',
            'city_id' => '80', 'district_id' => 'Merkez', 'block_number' => '10', 'parcel_number' => '20',
        ]));
        $listing->eidsVerification()->create([
            'user_id' => $owner->id, 'verified_at' => now(), 'expires_at' => now()->addMonth(),
            'city' => 'Osmaniye', 'district' => 'Merkez', 'neighborhood' => 'Alibeyli', 'block' => '10', 'parcel' => '20',
        ]);

        app(ListingPublicationService::class)->submit($listing->fresh(['eidsVerification']), $owner, true);
        $this->assertSame('pending_approval', $listing->fresh()->publication_status);
        $this->assertFalse($listing->fresh()->is_active);
        Mail::assertSent(ApprovalRequestedMail::class, fn (ApprovalRequestedMail $mail): bool => $mail->hasTo('info@zydemlak.com.tr'));

        app(ListingPublicationService::class)->approve($listing->fresh(['eidsVerification']), $admin);
        $listing->refresh();
        $this->assertSame('approved', $listing->publication_status);
        $this->assertTrue($listing->is_active);
        $this->assertNotNull($listing->published_at);
        $this->assertSame('12345', $listing->immutable_snapshot['eids_document_no']);

        $this->expectException(ValidationException::class);
        $listing->update(['parcel_number' => '21']);
    }

    public function test_listing_can_be_unpublished_without_deleting_it(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $owner->id,
            'title' => 'Yayından kaldırılacak ilan',
            'slug' => 'yayindan-kaldirilacak-ilan',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'eids_document_no' => '12345',
            'city_id' => '80',
            'district_id' => 'Merkez',
            'is_active' => true,
        ]));

        app(ListingPublicationService::class)->unpublish($listing, $owner);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'publication_status' => 'unpublished',
            'is_active' => false,
        ]);
        $this->get(route('listings.show', $listing))->assertNotFound();
    }

    public function test_listing_table_exposes_required_listing_actions(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $published = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $owner->id,
            'title' => 'Yayındaki ilan',
            'slug' => 'yayindaki-ilan',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'eids_document_no' => '12345',
            'city_id' => '80',
            'district_id' => 'Merkez',
            'is_active' => true,
        ]));

        $draft = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $owner->id,
            'title' => 'Taslak ilan',
            'slug' => 'taslak-ilan',
            'price' => 900000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'eids_status' => 'pending',
            'publication_status' => 'draft',
            'eids_document_no' => '12346',
            'city_id' => '80',
            'district_id' => 'Merkez',
            'is_active' => false,
        ]));

        Filament::setCurrentPanel(Filament::getPanel('portal'));

        Livewire::actingAs($owner)
            ->test(ListListings::class)
            ->assertTableActionExists('edit', null, $published)
            ->assertTableActionExists('delete', null, $published)
            ->assertTableActionExists('unpublish', null, $published)
            ->assertTableActionEnabled('unpublish', $published)
            ->assertTableActionDisabled('unpublish', $draft);
    }
}

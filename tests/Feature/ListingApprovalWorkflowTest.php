<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Mail\ApprovalRequestedMail;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ListingApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_listing_requires_admin_approval_and_locks_official_fields(): void
    {
        Mail::fake();
        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
            'eids_user_code' => 'member-guid',
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
}

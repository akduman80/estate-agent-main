<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Models\Listing;
use App\Models\Setting;
use App\Models\User;
use App\Services\ListingPublicationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ListingEidsExemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::updateOrCreate(['key' => 'eids_exempt_listing_types'], [
            'value' => ['Yönetici tarafından tanımlanan tip'],
        ]);
        Setting::clearCache('eids_exempt_listing_types');
    }

    public function test_no_type_is_exempt_without_admin_configuration(): void
    {
        Setting::where('key', 'eids_exempt_listing_types')->delete();
        Setting::clearCache('eids_exempt_listing_types');
        $this->assertFalse($this->listing()->eids_exempt);
        $this->assertFalse(Listing::isEidsExemptType('Tapusuz'));
    }

    public function test_explicit_empty_configuration_does_not_exempt_new_listings(): void
    {
        Setting::updateOrCreate(['key' => 'eids_exempt_listing_types'], ['value' => []]);
        Setting::clearCache('eids_exempt_listing_types');
        $this->assertFalse($this->listing()->eids_exempt);
    }

    private function listing(string $type = 'Yönetici tarafından tanımlanan tip'): Listing
    {
        $owner = User::factory()->create([
            'role' => UserRole::INDIVIDUAL, 'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(), 'eids_identity_verified_at' => null,
        ]);

        return Listing::create([
            'user_id' => $owner->id, 'title' => 'Tapusuz ilan', 'slug' => 'tapusuz-'.uniqid(),
            'price' => 10000, 'type' => $type, 'category' => 'Daire', 'source_type' => 'owner',
            'eids_status' => 'pending', 'publication_status' => 'draft',
            'city_id' => '80', 'district_id' => 'Merkez', 'is_active' => false,
        ]);
    }

    public function test_form_allows_tapusuz_listing_without_property_id_and_requires_it_for_normal_type(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Setting::updateOrCreate(['key' => 'options_type'], ['value' => ['Satılık', 'Yönetici tarafından tanımlanan tip']]);
        Setting::clearCache('options_type');
        $form = Livewire::test(CreateListing::class)
            ->fillForm([
                'user_id' => $admin->id, 'title' => 'Tapusuz form testi', 'category' => 'Arsa',
                'type' => 'Yönetici tarafından tanımlanan tip', 'price' => 10000, 'square_meters' => 55,
                'city_id' => 'İstanbul', 'district_id' => 'Kadıköy',
            ]);
        $form->assertFormFieldIsHidden('eids_document_no')
            ->call('create')->assertHasNoFormErrors();
        $this->assertTrue(Listing::query()->sole()->eids_exempt);
        Livewire::test(CreateListing::class)
            ->fillForm(['type' => 'Satılık'])
            ->assertFormFieldIsVisible('eids_document_no')
            ->call('create')->assertHasFormErrors(['eids_document_no' => 'required']);
    }

    public function test_exempt_listing_publishes_with_admin_approval_without_eids_or_property_id(): void
    {
        Mail::fake();
        Http::fake();
        $listing = $this->listing();
        $admin = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $service = app(ListingPublicationService::class);
        $this->assertTrue($listing->eids_exempt);
        $this->assertNull($listing->eids_document_no);
        $service->submit($listing, $listing->user, true);
        $this->assertFalse(Listing::published()->whereKey($listing->id)->exists());
        $service->approve($listing, $admin);
        $this->assertTrue(Listing::published()->whereKey($listing->id)->exists());
        $this->assertSame('pending', $listing->eids_status);
        $this->assertFalse($listing->is_verified);
        Http::assertNothingSent();
        $this->get(route('listings.show', $listing))->assertOk()->assertSee('Tapusuz İlan')->assertDontSee('EİDS Doğrulandı');
        $service->unpublish($listing, $listing->user);
        $this->get(route('listings.show', $listing))->assertNotFound();
    }

    public function test_saved_exemption_survives_label_settings_changes_and_resets_when_type_changes(): void
    {
        $listing = $this->listing();
        Setting::updateOrCreate(['key' => 'eids_exempt_listing_types'], ['value' => ['Yeni tapusuz tip']]);
        Setting::clearCache('eids_exempt_listing_types');
        $listing->update(['title' => 'Yeni başlık']);
        $this->assertTrue($listing->fresh()->eids_exempt);
        $listing->update(['type' => 'Satılık']);
        $this->assertFalse($listing->fresh()->eids_exempt);
        $listing->update(['type' => 'Yeni tapusuz tip']);
        $this->assertTrue($listing->fresh()->eids_exempt);
    }

    public function test_normal_listing_cannot_gain_exemption_by_sending_a_flag(): void
    {
        $listing = $this->listing('Satılık');
        $listing->forceFill(['eids_exempt' => true])->save();
        $this->assertFalse($listing->fresh()->eids_exempt);
        $this->expectException(ValidationException::class);
        app(ListingPublicationService::class)->approve($listing, User::factory()->create(['role' => UserRole::SUPERVISOR]));
    }

    public function test_exemption_cannot_bypass_terms_or_be_changed_after_publication(): void
    {
        Mail::fake();
        $listing = $this->listing();
        $service = app(ListingPublicationService::class);
        try {
            $service->submit($listing, $listing->user, false);
            $this->fail('Terms must be accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('terms', $exception->errors());
        }
        $service->submit($listing, $listing->user, true);
        $service->approve($listing, User::factory()->create(['role' => UserRole::SUPERVISOR]));
        $this->expectException(ValidationException::class);
        $listing->update(['type' => 'Satılık']);
    }
}

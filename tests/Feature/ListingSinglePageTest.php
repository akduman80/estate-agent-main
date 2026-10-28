<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Portal\Resources\ListingResource\Pages\EditListing;
use App\Mail\ApprovalRequestedMail;
use App\Models\Listing;
use App\Models\ListingFormDraft;
use App\Models\User;
use App\Services\EidsService;
use App\Services\ListingFormDraftService;
use App\Support\ListingFormData;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ListingSinglePageTest extends TestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate:fresh');
        Storage::fake('public');
        $this->owner = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($this->owner);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function fields(array $overrides = []): array
    {
        return [...['user_id' => $this->owner->id, 'title' => 'Tek sayfa test ilanı', 'description' => 'Güncel açıklama', 'category' => 'Daire', 'type' => 'Satılık', 'price' => '3000000', 'currency' => 'TRY', 'square_meters' => '120', 'net_square_meters' => '100', 'eids_document_no' => '12345', 'city_id' => 'İstanbul', 'district_id' => 'Kadıköy'], ...$overrides];
    }

    public function test_incomplete_draft_is_encrypted_and_resumes_without_creating_a_listing(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm(['title' => 'Yarım kalan ilan', 'private_note' => 'Gizli portföy 987']);
        $page->call('saveDraft')->assertHasNoErrors();
        $this->assertSame(0, Listing::count());
        $draft = ListingFormDraft::sole();
        $this->assertSame('Yarım kalan ilan', $draft->payload['title']);
        $this->assertStringNotContainsString('Gizli portföy', DB::table('listing_form_drafts')->value('payload'));
        $this->assertArrayNotHasKey('eids_status', $draft->payload);
        Livewire::test(CreateListing::class)->assertSet('data.title', 'Yarım kalan ilan')->assertSet('data.private_note', 'Gizli portföy 987');
    }

    public function test_draft_detects_conflicting_tabs_and_preserves_the_newer_saved_payload(): void
    {
        $first = Livewire::test(CreateListing::class)->fillForm(['title' => 'İlk sürüm'])->call('saveDraft');
        $second = Livewire::test(CreateListing::class);
        $first->set('data.title', 'Yeni sürüm')->call('saveDraft')->assertHasNoErrors();
        $second->set('data.title', 'Eski sekme')->call('saveDraft')->assertHasErrors(['draft'])->assertSet('data.title', 'Eski sekme');
        $this->assertSame('Yeni sürüm', ListingFormDraft::sole()->payload['title']);
    }

    public function test_draft_media_and_order_survive_reload_then_create(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm([...$this->fields(), 'uploaded_images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')]])->call('saveDraft')->assertHasNoErrors();
        $draft = ListingFormDraft::sole();
        $paths = array_values($draft->payload['uploaded_images']);
        $this->assertCount(2, $paths);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $resume = Livewire::test(CreateListing::class)->call('create')->assertHasNoFormErrors();
        $listing = $resume->instance()->getRecord();
        $this->assertSame($paths, $listing->images()->pluck('image_path')->all());
        $this->assertTrue((bool) $listing->images()->first()->is_main);
    }

    public function test_repeated_create_uses_one_listing_and_stays_on_the_same_form(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields())->call('create')->assertHasNoFormErrors()->assertNoRedirect();
        $id = $page->instance()->getRecord()->id;
        $page->set('data.title', 'Düzeltilen başlık')->call('create')->assertHasNoFormErrors();
        $this->assertSame(1, Listing::count());
        $this->assertSame('Düzeltilen başlık', Listing::findOrFail($id)->title);
        $this->assertSame('draft', Listing::findOrFail($id)->publication_status);
    }

    public function test_private_note_and_hidden_phone_and_coordinates_never_enter_public_output(): void
    {
        $this->owner->update(['phone' => '05551239876']);
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields(['private_note' => 'Çok özel not 76342', 'form_details' => ['hide_phone' => true, 'hide_location' => true, 'latitude' => 40.1234567, 'longitude' => 29.2345678, 'bathrooms' => 2, 'address' => 'Özel açık adres 987']]))->call('create')->assertHasNoFormErrors();
        $listing = $page->instance()->getRecord();
        $listing->forceFill(['eids_status' => 'verified', 'publication_status' => 'approved', 'is_active' => true])->saveQuietly();
        $response = $this->get(route('listings.show', $listing->slug))->assertOk()->assertSee('Banyo sayısı')->assertDontSee('Çok özel not 76342')->assertDontSee('05551239876')->assertDontSee('40.1234567')->assertDontSee('29.2345678')->assertDontSee('Özel açık adres 987');
        $this->assertArrayNotHasKey('private_note', $listing->toArray());
        $this->assertArrayNotHasKey('form_details', $listing->toArray());
        $this->assertArrayNotHasKey('phone', $listing->fresh()->load('user')->toArray()['user']);
        $this->assertStringNotContainsString('Çok özel not', DB::table('listings')->where('id', $listing->id)->value('private_note'));
        Livewire::test(EditListing::class, ['record' => $listing->id])->assertSet('data.private_note', 'Çok özel not 76342');
    }

    public function test_foreign_draft_paths_and_foreign_draft_updates_are_forbidden(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm(['title' => 'Özel taslak'])->call('saveDraft');
        $draft = ListingFormDraft::sole();
        $other = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($other);
        $this->expectException(HttpException::class);
        app(ListingFormDraftService::class)->save($draft->id, $draft->version, ['title' => 'Yetkisiz']);
    }

    public function test_category_switch_keeps_common_data_and_discards_incompatible_fields(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields(['room_count' => '3+1', 'heating_type' => 'Klima']))
            ->call('selectQuickCategory', 'Arsa', 'Satılık')->call('create')->assertHasNoFormErrors();
        $listing = $page->instance()->getRecord();
        $this->assertSame('Arsa', $listing->category);
        $this->assertSame('Tek sayfa test ilanı', $listing->title);
        $this->assertNull($listing->room_count);
        $this->assertNull($listing->heating_type);
    }

    public function test_publishing_requires_explicit_terms_without_creating_a_listing(): void
    {
        Livewire::test(CreateListing::class)->fillForm($this->fields())->call('submitWorkspace', false)->assertHasErrors(['terms']);
        $this->assertSame(0, Listing::count());
    }

    public function test_invalid_tour_and_inconsistent_area_are_rejected(): void
    {
        try {
            ListingFormData::validate($this->fields(['form_details' => ['virtual_tour' => 'https://my.matterport.com.evil.example/x']]));
            $this->fail('Invalid host accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form_details.virtual_tour', $exception->errors());
        }
        $this->expectException(ValidationException::class);
        ListingFormData::validate($this->fields(['net_square_meters' => '130']));
    }

    public function test_turkish_decimal_input_is_preserved_exactly(): void
    {
        $this->assertSame('3000000.25', ListingFormData::decimal('3.000.000,25'));
        $this->assertSame('120.50', ListingFormData::decimal('120,50'));
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields(['price' => '3.000.000,25']))->call('create')->assertHasNoFormErrors();
        $this->assertSame('3000000.25', $page->instance()->getRecord()->price);
    }

    public function test_preview_uses_current_unsaved_values_and_omits_private_note(): void
    {
        Livewire::test(CreateListing::class)->fillForm($this->fields(['title' => 'Önizlenen başlık', 'private_note' => 'Saklı 5789']))
            ->mountAction('preview')->assertActionMounted('preview')->assertSee('Önizlenen başlık')->assertDontSee('Saklı 5789');
        $this->assertSame(0, Listing::count());
    }

    public function test_same_page_submission_uses_existing_verification_service_and_queues_approval_once(): void
    {
        $this->withoutExceptionHandling();
        config()->set('app.debug', true);
        Mail::fake();
        $this->mock(EidsService::class)->shouldReceive('verifyListing')->once()->andReturnUsing(function (Listing $listing): bool {
            $listing->forceFill(['eids_status' => 'verified'])->saveQuietly();

            return true;
        });
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields())->call('submitWorkspace', true)->assertStatus(200)->assertHasNoErrors()->assertNoRedirect();
        $listing = Listing::sole();
        $this->assertSame('pending_approval', $listing->publication_status);
        $this->assertFalse($listing->is_active);
        $page->call('submitWorkspace', true)->assertHasNoErrors();
        $this->assertSame(1, Listing::count());
        Mail::assertSent(ApprovalRequestedMail::class, 1);
    }

    public function test_verification_failure_preserves_draft_and_current_form_values(): void
    {
        $this->withoutExceptionHandling();
        config()->set('app.debug', true);
        Mail::fake();
        $this->mock(EidsService::class)->shouldReceive('verifyListing')->once()->andReturn(false);
        Livewire::test(CreateListing::class)->fillForm($this->fields())->call('submitWorkspace', true)->assertHasErrors(['eids'])->assertSet('data.title', 'Tek sayfa test ilanı');
        $this->assertSame('draft', Listing::sole()->publication_status);
        $this->assertFalse(Listing::sole()->is_active);
        Mail::assertNothingSent();
    }

    public function test_all_reference_main_categories_can_be_created_without_a_wizard(): void
    {
        foreach ([['Daire', 'Satılık'], ['Daire', 'Kiralık'], ['Müstakil Ev', 'Satılık'], ['Arsa', 'Satılık'], ['İşyeri', 'Kiralık'], ['Bina', 'Satılık'], ['Devre Mülk', 'Satılık'], ['Turistik Tesis', 'Kiralık']] as [$category,$type]) {
            $page = Livewire::test(CreateListing::class)->fillForm($this->fields(['category' => $category, 'type' => $type]))->call('create')->assertHasNoErrors();
            $this->assertSame($category, $page->instance()->getRecord()->category);
            $this->assertSame($type, $page->instance()->getRecord()->type);
        }
        $this->assertSame(8, Listing::count());
    }

    public function test_feature_opt_out_and_keyboard_cover_choice_are_persisted(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields([
            'facades' => ['Kuzey'], 'form_details' => ['facade_declined' => true, 'features' => ['interior' => ['declined' => true, 'values' => ['Seçilmeyen özellik']]]],
            'uploaded_images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ]));
        $keys = array_keys($page->instance()->data['uploaded_images']);
        $page->call('moveListingPhoto', (string) end($keys), 0)->call('create')->assertHasNoErrors();
        $listing = $page->instance()->getRecord();
        $this->assertSame([], $listing->selected_facades);
        $this->assertSame([], $listing->form_details['features']['interior']['values']);
        $this->assertTrue($listing->form_details['features']['interior']['declined']);
        $this->assertCount(2, $listing->images()->get());
        $this->assertTrue((bool) $listing->images()->first()->is_main);
    }

    public function test_additive_migration_rollback_and_reapply_preserve_notes_and_drafts(): void
    {
        Livewire::test(CreateListing::class)->fillForm(['title' => 'Korunan taslak', 'private_note' => 'Kalıcı özel not'])->call('saveDraft');
        $migration = require database_path('migrations/2026_10_28_000001_add_listing_form_details_and_drafts.php');
        $migration->down();
        $migration->up();
        $this->assertSame('Kalıcı özel not', ListingFormDraft::sole()->payload['private_note']);
    }

    public function test_legacy_published_housing_keeps_locked_category_and_existing_values_when_saved(): void
    {
        $page = Livewire::test(CreateListing::class)->fillForm($this->fields(['room_count' => '3+1', 'heating_type' => 'Klima']))->call('create')->assertHasNoErrors();
        $listing = $page->instance()->getRecord();
        $listing->forceFill(['eids_status' => 'verified', 'published_at' => now()->subDays(5), 'publication_status' => 'approved', 'is_active' => true])->saveQuietly();
        Livewire::test(EditListing::class, ['record' => $listing->id])->set('data.title', 'Eski ilan yeni başlık')->call('save')->assertHasNoErrors();
        $this->assertSame('Daire', $listing->fresh()->category);
        $this->assertSame('3+1', $listing->fresh()->room_count);
        $this->assertSame('Klima', $listing->fresh()->heating_type);
    }
}

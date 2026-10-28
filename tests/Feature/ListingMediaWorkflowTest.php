<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Portal\Resources\ListingResource\Pages\EditListing;
use App\Models\Listing;
use App\Models\Setting;
use App\Models\User;
use App\Rules\ListingVideo;
use App\Services\ListingMediaService;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ListingMediaWorkflowTest extends TestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate:fresh');
        Storage::fake('public');
        $this->owner = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($this->owner);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function formData(string $category = 'Daire'): array
    {
        return [
            'user_id' => $this->owner->id, 'title' => 'Medya ve konut testi',
            'category' => $category, 'type' => 'Satılık', 'price' => '1234',
            'square_meters' => '100', 'eids_document_no' => '12345',
            'city_id' => 'İstanbul', 'district_id' => 'Kadıköy',
            'block_number' => '0012', 'parcel_number' => '0',
            'facades' => ['Kuzey', 'Güney', 'Doğu'],
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];
    }

    private function listing(array $overrides = []): Listing
    {
        return Listing::create([
            ...$this->formData(), 'slug' => 'media-'.uniqid(),
            'eids_status' => 'pending', 'publication_status' => 'draft', 'is_active' => false,
            ...$overrides,
        ]);
    }

    private function video(string $fixture): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($fixture, file_get_contents(base_path('tests/Fixtures/listing-media/'.$fixture)))->mimeType('video/mp4');
    }

    private function requireFfprobe(): void
    {
        $process = new Process([config('listings.video.ffprobe_binary'), '-version']);
        try {
            $process->mustRun();
        } catch (\Throwable) {
            $this->markTestSkipped('ffprobe is required for real video fixtures.');
        }
    }

    public function test_both_panels_create_and_edit_housing_without_replacing_the_listing(): void
    {
        foreach (['admin', 'portal'] as $panel) {
            Filament::setCurrentPanel(Filament::getPanel($panel));
            foreach (['Daire', 'Müstakil Ev'] as $category) {
                $page = Livewire::test(CreateListing::class)
                    ->fillForm([...$this->formData($category), 'uploaded_images' => [
                        UploadedFile::fake()->image('first.jpg'), UploadedFile::fake()->image('second.jpg'),
                    ]])
                    ->call('create')->assertHasNoFormErrors();
                $listing = $page->instance()->getRecord();
                $this->assertSame('0012', $listing->fresh()->block_number);
                $this->assertSame('0', $listing->fresh()->parcel_number);
                $this->assertSame(['Kuzey', 'Güney', 'Doğu'], $listing->fresh()->selected_facades);
                $images = $listing->images()->get();
                $this->assertCount(2, $images);
                $beforeCount = Listing::count();

                $edit = Livewire::test(EditListing::class, ['record' => $listing->id])
                    ->assertFormSet(['block_number' => '0012', 'parcel_number' => '0', 'facades' => ['Kuzey', 'Güney', 'Doğu']]);
                $this->assertSame($images->pluck('image_path')->all(), array_values($edit->instance()->data['uploaded_images']));
                $files = $edit->instance()->data['uploaded_images'];
                unset($files[array_key_first($files)]);
                $edit->set('data.uploaded_images', $files)
                    ->set('data.uploaded_images.new-image', UploadedFile::fake()->image('third.jpg'))
                    ->set('data.facades', ['Güney', 'Batı'])
                    ->call('save')->assertHasNoFormErrors();

                $this->assertSame($beforeCount, Listing::count());
                $this->assertSame(['Güney', 'Batı'], $listing->fresh()->selected_facades);
                $this->assertDatabaseHas('listing_images', ['id' => $images[1]->id, 'is_main' => true, 'sort_order' => 0]);
                $this->assertDatabaseMissing('listing_images', ['id' => $images[0]->id]);
                Storage::disk('public')->assertMissing($images[0]->image_path);
                Storage::disk('public')->assertExists($images[1]->image_path);
                $this->assertCount(2, $listing->images()->get());
                $this->assertSame($this->formData()['video_url'], $listing->fresh()->video_url);

                Livewire::test(EditListing::class, ['record' => $listing->id])
                    ->set('data.uploaded_images', [])->set('data.facades', [])
                    ->call('save')->assertHasNoFormErrors();
                $this->assertCount(0, $listing->images()->get());
                $this->assertSame([], $listing->fresh()->selected_facades);
            }
        }
    }

    public function test_legacy_facade_and_missing_media_survive_unrelated_saves(): void
    {
        $listing = $this->listing(['facades' => null, 'facade' => 'Kuzeydoğu']);
        $image = $listing->images()->create(['image_path' => 'seed-listings/missing.jpg']);
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->assertFormSet(['facades' => ['Kuzeydoğu']])
            ->set('data.title', 'Başlık değişti')->call('save')->assertHasNoFormErrors();
        $this->assertSame('Kuzeydoğu', $listing->fresh()->facade);
        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->set('data.facades', [])->call('save')->assertHasNoFormErrors();
        Livewire::test(EditListing::class, ['record' => $listing->id])->assertFormSet(['facades' => []]);
    }

    public function test_photo_and_video_paths_from_another_listing_are_rejected(): void
    {
        $first = $this->listing();
        $second = $this->listing();
        $path = 'listings/images/'.$this->owner->id.'/other.jpg';
        Storage::disk('public')->put($path, 'image');
        $second->images()->create(['image_path' => $path]);
        Livewire::test(EditListing::class, ['record' => $first->id])
            ->set('data.uploaded_images', ['foreign' => $path])
            ->call('save')->assertHasFormErrors(['uploaded_images']);
        Livewire::test(EditListing::class, ['record' => $first->id])
            ->set('data.video_path', ['foreign' => 'listings/videos/1/foreign.mp4'])
            ->call('save')->assertHasFormErrors(['video_path']);
        Storage::disk('public')->assertExists($path);
    }

    public function test_photo_limits_are_enforced_on_the_server(): void
    {
        Livewire::test(CreateListing::class)
            ->fillForm([...$this->formData(), 'uploaded_images' => array_map(
                fn ($i) => UploadedFile::fake()->image("photo-$i.jpg"), range(1, 36),
            )])->call('create')->assertHasFormErrors(['uploaded_images' => 'max']);
        Livewire::test(CreateListing::class)
            ->fillForm([...$this->formData(), 'uploaded_images' => [UploadedFile::fake()->image('big.jpg')->size(15361)]])
            ->call('create')->assertHasFormErrors(['uploaded_images']);
        $this->assertSame(0, Listing::count());
    }

    public function test_thirty_five_photos_at_the_existing_size_limit_are_accepted(): void
    {
        $page = Livewire::test(CreateListing::class)
            ->fillForm([...$this->formData(), 'uploaded_images' => array_map(
                fn ($i) => UploadedFile::fake()->image("photo-$i.jpg")->size(15360), range(1, 35),
            )])->call('create')->assertHasNoFormErrors();
        $this->assertCount(35, $page->instance()->getRecord()->images()->get());
    }

    public function test_existing_konut_category_supports_the_same_fields_without_changing_settings(): void
    {
        Setting::updateOrCreate(['key' => 'options_category'], ['value' => ['Konut', 'Arsa', 'İşyeri']]);
        Setting::clearCache('options_category');
        $page = Livewire::test(CreateListing::class)->fillForm($this->formData('Konut'))
            ->assertFormFieldIsVisible('block_number')->assertFormFieldIsVisible('parcel_number')
            ->assertFormFieldIsVisible('facades')->call('create')->assertHasNoFormErrors();
        $listing = $page->instance()->getRecord();
        $this->assertSame('0012', $listing->block_number);
        $this->assertSame(['Kuzey', 'Güney', 'Doğu'], $listing->selected_facades);
        $this->assertSame(['Konut', 'Arsa', 'İşyeri'], Setting::get('options_category'));
    }

    public function test_unknown_facades_are_rejected_and_verified_official_fields_stay_locked(): void
    {
        Livewire::test(CreateListing::class)
            ->fillForm([...$this->formData(), 'facades' => ['Unknown']])
            ->call('create')->assertHasFormErrors(['facades']);
        $listing = $this->listing(['eids_status' => 'verified']);
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->assertFormFieldIsDisabled('block_number')->assertFormFieldIsDisabled('parcel_number')
            ->set('data.block_number', '999')->set('data.facades', ['Batı'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('0012', $listing->fresh()->block_number);
        $this->assertSame(['Batı'], $listing->fresh()->selected_facades);
    }

    public function test_real_video_duration_boundary_and_unsupported_codec(): void
    {
        $this->requireFfprobe();
        $this->assertFalse(Validator::make(['video' => $this->video('60-seconds.mp4')], ['video' => [new ListingVideo]])->fails());
        $validator = Validator::make(['video' => $this->video('over-60-seconds.mp4')], ['video' => [new ListingVideo]]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('60 saniye', $validator->errors()->first('video'));
        $this->assertTrue(Validator::make(['video' => $this->video('unsupported-codec.mp4')], ['video' => [new ListingVideo]])->fails());
    }

    public function test_video_metadata_failure_is_rejected(): void
    {
        config()->set('listings.video.ffprobe_binary', '/nonexistent/ffprobe');
        $validator = Validator::make(['video' => $this->video('60-seconds.mp4')], ['video' => [new ListingVideo]]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('doğrulanamadı', $validator->errors()->first('video'));
    }

    public function test_video_can_be_uploaded_replaced_and_removed_without_losing_youtube_or_photos(): void
    {
        $this->requireFfprobe();
        $page = Livewire::test(CreateListing::class)
            ->fillForm([...$this->formData(), 'video_path' => $this->video('60-seconds.mp4'),
                'uploaded_images' => [UploadedFile::fake()->image('photo.jpg')]])
            ->call('create')->assertHasNoFormErrors();
        $listing = $page->instance()->getRecord();
        $oldPath = $listing->video_path;
        Storage::disk('public')->assertExists($oldPath);
        $image = $listing->images()->sole();
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->set('data.video_path', [])
            ->set('data.video_path.new', $this->video('60-seconds.mp4'))
            ->call('save')->assertHasNoFormErrors();
        $newPath = $listing->fresh()->video_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->set('data.video_path', [])->call('save')->assertHasNoFormErrors();
        $this->assertNull($listing->fresh()->video_path);
        Storage::disk('public')->assertMissing($newPath);
        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        $this->assertSame($this->formData()['video_url'], $listing->fresh()->video_url);
    }

    public function test_rejected_video_does_not_replace_existing_video(): void
    {
        $this->requireFfprobe();
        $listing = $this->listing(['video_path' => 'listings/videos/1/original.mp4']);
        Storage::disk('public')->put($listing->video_path, 'original');
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->set('data.video_path', [])
            ->set('data.video_path.new', $this->video('over-60-seconds.mp4'))
            ->call('save')->assertHasFormErrors(['video_path']);
        $this->assertSame('listings/videos/1/original.mp4', $listing->fresh()->video_path);
        Storage::disk('public')->assertExists($listing->video_path);
        config()->set('listings.video.max_size_kb', 1);
        Livewire::test(EditListing::class, ['record' => $listing->id])
            ->set('data.video_path', [])
            ->set('data.video_path.new', $this->video('60-seconds.mp4'))
            ->call('save')->assertHasFormErrors(['video_path']);
    }

    public function test_media_cleanup_waits_for_commit_and_preserves_shared_files(): void
    {
        $first = $this->listing();
        $second = $this->listing();
        $path = 'listings/shared.jpg';
        Storage::disk('public')->put($path, 'image');
        $first->images()->create(['image_path' => $path]);
        $second->images()->create(['image_path' => $path]);
        DB::beginTransaction();
        app(ListingMediaService::class)->syncImages($first, []);
        DB::rollBack();
        $this->assertCount(1, $first->images()->get());
        Storage::disk('public')->assertExists($path);
        app(ListingMediaService::class)->syncImages($first, []);
        Storage::disk('public')->assertExists($path);
        app(ListingMediaService::class)->syncImages($second, []);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_detail_keeps_legacy_and_new_values_and_both_video_sources(): void
    {
        $listing = $this->listing(['eids_status' => 'verified', 'publication_status' => 'approved', 'is_active' => true,
            'video_path' => 'listings/videos/1/video.mp4']);
        $this->get(route('listings.show', $listing->slug))->assertOk()
            ->assertSee('0012')->assertSee('Parsel')->assertSee('Kuzey / Güney / Doğu')
            ->assertSee('/storage/listings/videos/1/video.mp4', false)->assertSee('controls playsinline', false)
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $listing->update(['facades' => null, 'facade' => 'Güney']);
        $this->get(route('listings.show', $listing->slug))->assertOk()->assertSee('Güney');
        config()->set('app.url', 'https://wrong-host.example');
        $this->assertSame('/storage/listings/test.jpg', ListingMediaService::publicUrl('listings/test.jpg'));
    }

    public function test_migration_rollback_and_reapply_preserve_new_and_legacy_data(): void
    {
        $listing = $this->listing(['facade' => 'Kuzey', 'video_path' => 'listings/videos/1/video.mp4']);
        $migration = require database_path('migrations/2026_10_04_000002_add_listing_facades_and_video_path.php');
        $migration->down();
        $migration->up();
        $this->assertSame(['Kuzey', 'Güney', 'Doğu'], $listing->fresh()->selected_facades);
        $this->assertSame('Kuzey', $listing->fresh()->facade);
        $this->assertSame('listings/videos/1/video.mp4', $listing->fresh()->video_path);
        $this->assertSame('0012', $listing->fresh()->block_number);
    }
}

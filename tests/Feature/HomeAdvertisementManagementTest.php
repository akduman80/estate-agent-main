<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Resources\AdvertisementResource\Pages\CreateAdvertisement;
use App\Filament\Resources\AdvertisementResource\Pages\EditAdvertisement;
use App\Filament\Resources\AdvertisementResource\Pages\ListAdvertisements;
use App\Models\Advertisement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HomeAdvertisementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs(User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_preparation_backs_up_only_legacy_records_and_is_idempotent(): void
    {
        $legacy = Advertisement::create(['label' => 'Eski sol', 'placement' => 'left', 'image_path' => 'site/ads/old.jpg', 'is_active' => true]);
        Storage::disk('public')->put('site/ads/old.jpg', 'preserved');
        $current = Advertisement::create(['label' => 'Mevcut güncel reklam', 'placement' => 'home01', 'image_path' => 'site/ads/current.jpg', 'target_url' => 'https://example.com/current', 'is_active' => false]);
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        $this->assertDatabaseMissing('advertisements', ['id' => $legacy->id]);
        $this->assertDatabaseHas('advertisements', ['id' => $current->id, 'is_active' => false, 'target_url' => 'https://example.com/current']);
        Storage::disk('public')->assertExists('site/ads/old.jpg');
        $backup = collect(Storage::disk('local')->files('advertisements'))->first(fn ($path) => str_contains($path, 'legacy-'));
        $this->assertNotNull($backup);
        $this->assertSame($legacy->id, json_decode(Storage::disk('local')->get($backup), true)['records'][0]['id']);
        $this->assertDatabaseCount('advertisements', 3);
        Advertisement::where('placement', 'home03')->delete();
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        $this->assertDatabaseCount('advertisements', 2);
    }

    public function test_imported_originals_are_identical_and_managed_by_panel(): void
    {
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        $ads = Advertisement::orderBy('placement')->get();
        $this->assertCount(3, $ads);
        foreach ($ads as $ad) {
            $this->assertSame(file_get_contents(public_path('fe-assets/assets/img/advertisements/'.basename($ad->image_path))), Storage::disk('public')->get($ad->image_path));
            $this->assertStringContainsString('https://wa.me/905303607769?text=', $ad->target_url);
        }
        Livewire::test(ListAdvertisements::class)->assertSuccessful()->assertSee('Reklamlar nasıl kullanılır?')->assertCanSeeTableRecords($ads)->assertDontSee('Eski sağ (kapalı)')->assertDontSee('Sabit gösterim');
    }

    public function test_authorized_list_initializes_records_and_deleted_ads_do_not_return(): void
    {
        Advertisement::create(['label' => 'Eski sağ', 'placement' => 'right', 'image_path' => 'site/ads/legacy.jpg']);
        // Public rendering preserves the existing carousel without writing to DB.
        $this->get('/')->assertOk()->assertSee('reklam-01-fincan-kupa.jpg', false);
        $this->assertDatabaseCount('advertisements', 1);
        Livewire::test(ListAdvertisements::class)->assertSuccessful();
        $this->assertDatabaseCount('advertisements', 3);
        $this->assertDatabaseMissing('advertisements', ['placement' => 'right']);
        Advertisement::query()->delete();
        $this->get('/')->assertOk()->assertDontSee('data-home-print-ad', false)->assertDontSee('reklam-01-fincan-kupa.jpg', false);
    }

    public function test_admin_upload_create_edit_unpublish_and_publish_are_used_by_site(): void
    {
        $file = UploadedFile::fake()->image('new-banner.jpg', 1600, 240);
        Livewire::test(CreateAdvertisement::class)->fillForm([
            'label' => 'Panelden eklenen reklam', 'placement' => 'home04',
            'image_path' => [$file], 'target_url' => 'https://example.com/campaign', 'is_active' => true,
        ])->call('create')->assertHasNoFormErrors();
        $ad = Advertisement::where('placement', 'home04')->firstOrFail();
        Storage::disk('public')->assertExists($ad->image_path);
        $this->get('/')->assertOk()->assertSee($ad->image_path, false)->assertSee('https://example.com/campaign', false);
        Livewire::test(EditAdvertisement::class, ['record' => $ad->getRouteKey()])->fillForm(['label' => 'Güncellenen reklam', 'placement' => 'home05', 'target_url' => 'https://example.com/updated', 'is_active' => false])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertDontSee($ad->image_path, false)->assertDontSee('data-home-print-ad', false);
        Livewire::test(ListAdvertisements::class)->call('updateTableColumnState', 'is_active', (string) $ad->id, true)->assertHasNoErrors();
        $this->get('/')->assertOk()->assertSee('Güncellenen reklam')->assertSee('https://example.com/updated', false);
    }

    public function test_wrong_dimensions_duplicate_position_and_unsafe_url_are_rejected(): void
    {
        $this->artisan('advertisements:prepare-home')->assertSuccessful();
        Livewire::test(CreateAdvertisement::class)->fillForm([
            'label' => 'Geçersiz reklam', 'placement' => 'home01',
            'image_path' => [UploadedFile::fake()->image('wrong.jpg', 800, 600)],
            'target_url' => 'javascript:alert(1)',
        ])->call('create')->assertHasFormErrors(['image_path', 'placement' => 'unique', 'target_url']);
        $this->assertDatabaseCount('advertisements', 3);
    }

    public function test_publication_dates_and_more_than_three_slides_use_order(): void
    {
        foreach (['home04', 'home02', 'home01', 'home03'] as $placement) {
            Advertisement::create(['label' => $placement, 'placement' => $placement, 'image_path' => 'site/ads/'.$placement.'.jpg', 'target_url' => 'https://example.com/'.$placement, 'is_active' => true]);
        }
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-home-print-ad'));
        $this->assertTrue(strpos($html, 'site/ads/home01.jpg') < strpos($html, 'site/ads/home02.jpg'));
        $this->assertTrue(strpos($html, 'site/ads/home03.jpg') < strpos($html, 'site/ads/home04.jpg'));
        Advertisement::where('placement', 'home02')->update(['starts_at' => now()->addDay()]);
        Advertisement::where('placement', 'home03')->update(['ends_at' => now()->subDay()]);
        $this->get('/')->assertOk()->assertDontSee('site/ads/home02.jpg', false)->assertDontSee('site/ads/home03.jpg', false)->assertSee('site/ads/home04.jpg', false);
    }

    public function test_file_type_size_required_fields_and_invalid_date_range_are_rejected(): void
    {
        foreach ([
            UploadedFile::fake()->image('large.jpg', 1600, 240)->size(8193),
            UploadedFile::fake()->createWithContent('wrong.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="240"></svg>'),
        ] as $file) {
            Livewire::test(CreateAdvertisement::class)->fillForm([
                'label' => 'Geçersiz dosya', 'placement' => 'home04', 'image_path' => [$file],
                'target_url' => 'https://example.com/campaign',
            ])->call('create')->assertHasFormErrors(['image_path']);
        }
        Livewire::test(CreateAdvertisement::class)->fillForm([
            'label' => '', 'placement' => null, 'image_path' => [], 'target_url' => '',
        ])->call('create')->assertHasFormErrors(['label' => 'required', 'placement' => 'required', 'image_path' => 'required', 'target_url' => 'required']);
        Livewire::test(CreateAdvertisement::class)->fillForm([
            'label' => 'Geçersiz tarih', 'placement' => 'home04',
            'image_path' => [UploadedFile::fake()->image('dates.jpg', 1600, 240)],
            'target_url' => 'https://example.com/campaign',
            'starts_at' => '2026-10-20 12:00:00', 'ends_at' => '2026-10-19 12:00:00',
        ])->call('create')->assertHasFormErrors(['ends_at' => 'after']);
        $this->assertDatabaseCount('advertisements', 0);
    }
}

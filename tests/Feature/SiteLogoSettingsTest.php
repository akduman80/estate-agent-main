<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteLogoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundled_transparent_logo_fits_the_reverse_proxy_upload_limit(): void
    {
        $this->assertLessThan(
            1024 * 1024,
            filesize(public_path('fe-assets/assets/img/zydemlak-logo-transparent.png')),
        );
    }

    public function test_supervisor_can_upload_a_logo_that_is_used_by_the_public_header(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $source = imagecreatetruecolor(800, 436);
        imagefill($source, 0, 0, imagecolorallocate($source, 248, 248, 248));
        imagefilledrectangle($source, 250, 100, 550, 336, imagecolorallocate($source, 15, 62, 110));
        ob_start();
        imagepng($source);
        $logoContents = ob_get_clean();
        imagedestroy($source);

        Livewire::actingAs($supervisor)
            ->test(ManageSettings::class)
            ->set('data.site_logo', [UploadedFile::fake()->createWithContent('new-logo.png', $logoContents)])
            ->assertHasNoFormErrors();

        $logoPath = Setting::get('site_logo');

        $this->assertIsString($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->get('/')
            ->assertOk()
            ->assertSee('/storage/site/processed/', false);

        $processedLogo = collect(Storage::disk('public')->allFiles('site/processed'))->first();

        $this->assertNotNull($processedLogo);
        $image = imagecreatefromstring(Storage::disk('public')->get($processedLogo));
        $cornerAlpha = (imagecolorat($image, 0, 0) >> 24) & 0x7F;

        $this->assertGreaterThan(0, $cornerAlpha);
        imagedestroy($image);
    }
}

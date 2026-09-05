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

    public function test_supervisor_can_upload_a_logo_that_is_used_by_the_public_header(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($supervisor)
            ->test(ManageSettings::class)
            ->set('data.site_logo', [UploadedFile::fake()->image('new-logo.png', 800, 436)])
            ->call('save')
            ->assertHasNoFormErrors();

        $logoPath = Setting::get('site_logo');

        $this->assertIsString($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->get('/')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($logoPath), false);
    }
}

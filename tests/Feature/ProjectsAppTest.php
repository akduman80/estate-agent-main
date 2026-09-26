<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_use_the_projects_app_with_the_same_account(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get('/projeler-app/projects')
            ->assertOk()
            ->assertSee('ZYDEMLAK Projeler')
            ->assertSee('/pwa/projects.webmanifest', false)
            ->assertSee('zydProjectMobileToolsLoaded', false)
            ->assertSee('padding-left: .75rem', false)
            ->assertSee('overflow-x: hidden', false)
            ->assertDontSee("Projeler App'ini Telefona Yükle");
    }

    public function test_non_supervisor_cannot_open_the_projects_app(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::AGENT,
            'account_status' => AccountStatus::ACTIVE,
        ]);

        $this->actingAs($agent)
            ->get('/projeler-app/projects')
            ->assertForbidden();
    }

    public function test_admin_projects_page_contains_the_projects_app_install_action(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get('/admin/projects')
            ->assertOk()
            ->assertSee("Projeler App'ini Telefona Yükle")
            ->assertSee('/projeler-app/projects?install=1', false);
    }

    public function test_projects_app_edit_page_loads_the_location_picker_assets(): void
    {
        $supervisor = $this->supervisor();
        $project = Project::create([
            'title' => 'Mobil Harita Projesi',
            'slug' => 'mobil-harita-projesi',
            'developer_name' => 'ZYD Yapı',
            'status' => 'construction',
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'neighborhood' => 'Rahime Hatun',
            'latitude' => 37.0742,
            'longitude' => 36.2478,
        ]);

        $this->actingAs($supervisor)
            ->get("/projeler-app/projects/{$project->getKey()}/edit")
            ->assertOk()
            ->assertSee('data-project-location-picker', false)
            ->assertSee('leaflet@1.9.4/dist/leaflet.js', false)
            ->assertSee('window.projectLocationPicker', false)
            ->assertSee('Haritayı tam ekran yap')
            ->assertSee('Kamerayı aç')
            ->assertSee('Video çek')
            ->assertSee('zyd-camera-button', false)
            ->assertSee('data-expanded', false);
    }

    private function supervisor(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
        ]);
    }
}

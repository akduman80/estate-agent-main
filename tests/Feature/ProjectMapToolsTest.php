<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Models\User;
use App\Support\ProjectMapTransfer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMapToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_regions_use_native_reference_ids_and_reject_path_injection(): void
    {
        Http::fake();
        $this->getJson(route('projects.map.regions', ['level' => 'province']))->assertOk()
            ->assertJsonCount(81, 'regions')->assertJsonFragment(['id' => '102', 'name' => 'Osmaniye']);
        $this->getJson(route('projects.map.regions', ['level' => 'district', 'parent' => '102']))->assertOk()
            ->assertJsonFragment(['id' => '1016', 'name' => 'Merkez']);
        $this->getJson(route('projects.map.regions', ['level' => 'neighborhood', 'parent' => '1016']))->assertOk()
            ->assertJsonFragment(['id' => '128441', 'name' => 'Haraz']);
        $this->getJson(route('projects.map.regions', ['level' => 'district', 'parent' => '../attacker']))->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_unknown_region_is_reported_without_fake_results(): void
    {
        $this->getJson(route('projects.map.regions', ['level' => 'district', 'parent' => '999999999999']))
            ->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_identifier_search_uses_the_selected_neighborhood_and_original_geometry(): void
    {
        Http::fake(['cbsapi.tkgm.gov.tr/*' => Http::response([
            'properties' => ['adaNo' => '12', 'parselNo' => '3', 'ilAd' => 'Osmaniye'],
            'geometry' => ['type' => 'Polygon', 'coordinates' => [[[36, 37], [36.01, 37], [36.01, 37.01], [36, 37]]]],
        ])]);
        $this->getJson(route('projects.parcel.lookup', ['neighborhood_id' => '1234', 'block' => '12', 'parcel' => '3']))
            ->assertOk()->assertJsonPath('parcel.block', '12')->assertJsonPath('geometry.type', 'Polygon');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/parsel/1234/12/3'));
    }

    public function test_transfer_requires_existing_panel_authority_and_valid_coordinates(): void
    {
        $this->postJson(route('projects.map.transfer'), $this->location())->assertUnauthorized();
        $user = User::factory()->create(['role' => UserRole::INDIVIDUAL, 'account_status' => AccountStatus::ACTIVE]);
        $this->actingAs($user)->postJson(route('projects.map.transfer'), $this->location())->assertForbidden();
        $this->postJson(route('projects.map.transfer'), [...$this->location(), 'latitude' => 91])->assertUnprocessable();
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_transfer_opens_a_prefilled_project_create_form_without_saving(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERVISOR, 'account_status' => AccountStatus::ACTIVE]);
        $this->actingAs($user)->postJson(route('projects.map.transfer'), $this->location())->assertOk()
            ->assertJsonPath('url', url('/projeler-app/projects/create'));
        Filament::setCurrentPanel(Filament::getPanel('projects'));
        Livewire::test(CreateProject::class)->assertSet('data.latitude', 37.074)->assertSet('data.city', 'Osmaniye');
        $this->assertDatabaseCount('projects', 0);
        $this->assertFalse(session()->has('project-map-transfer.project'));
    }

    public function test_listing_transfer_preserves_other_form_fields_and_is_consumed_once(): void
    {
        session()->put('project-map-transfer.listing', [...$this->location(), 'target' => 'listing', 'block' => '12', 'parcel' => '3']);
        $state = ProjectMapTransfer::apply(['title' => 'Mevcut başlık', 'price' => 1000, 'form_details' => ['bathrooms' => 2]], 'listing');
        $this->assertSame('Mevcut başlık', $state['title']);
        $this->assertSame(1000, $state['price']);
        $this->assertSame(2, $state['form_details']['bathrooms']);
        $this->assertSame(37.074, $state['form_details']['latitude']);
        $this->assertSame('12', $state['block_number']);
        $this->assertSame($state, ProjectMapTransfer::apply($state, 'listing'));
    }

    public function test_listing_create_receives_selected_coordinates_without_creating_a_listing(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERVISOR, 'account_status' => AccountStatus::ACTIVE]);
        $this->actingAs($user)->postJson(route('projects.map.transfer'), [...$this->location(), 'target' => 'listing'])->assertOk();
        Filament::setCurrentPanel(Filament::getPanel('portal'));
        Livewire::test(CreateListing::class)->assertSet('data.latitude', 37.074)->assertSet('data.city_id', 'Osmaniye');
        $this->assertDatabaseCount('listings', 0);
    }

    public function test_projects_page_uses_the_unified_map_and_preserves_location_filters(): void
    {
        $this->get('/projeler?city=Osmaniye')->assertOk()->assertSee('Projeler Haritası')
            ->assertSee('data-layer="ilan"', false)->assertSee('data-layer="proje"', false)
            ->assertSee('projects-explorer.js')->assertSee('Proje konum filtreleri')->assertDontSee('ZYDEMLAK Ultra Pro');
    }

    private function location(): array
    {
        return ['target' => 'project', 'latitude' => 37.074, 'longitude' => 36.246, 'city' => 'Osmaniye', 'district' => 'Merkez', 'address' => 'Osmaniye Merkez'];
    }
}

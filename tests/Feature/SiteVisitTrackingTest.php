<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Portal\Widgets\RecentListings;
use App\Filament\Widgets\PopularSitePages;
use App\Filament\Widgets\SiteTrafficOverview;
use App\Models\Listing;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\TurkeyAddressService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteVisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_pages_are_tracked_without_plain_ip_storage(): void
    {
        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get(route('home', ['utm_source' => 'newsletter']))
            ->assertOk();

        $visit = SiteVisit::query()->firstOrFail();

        $this->assertSame('/', $visit->path);
        $this->assertSame('home', $visit->route_name);
        $this->assertSame('newsletter', $visit->utm_source);
        $this->assertSame('Mobil', $visit->device_type);
        $this->assertSame('Safari', $visit->browser);
        $this->assertNotSame('203.0.113.10', $visit->visitor_hash);
        $this->assertSame(64, strlen($visit->visitor_hash));
    }

    public function test_untracked_routes_do_not_create_visits(): void
    {
        $city = array_key_first(TurkeyAddressService::getCities());

        $this->get(route('locations.districts', ['city' => $city]))->assertOk();

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_supervisor_can_see_site_traffic_widgets(): void
    {
        $supervisor = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        SiteVisit::query()->create([
            'path' => '/',
            'route_name' => 'home',
            'method' => 'GET',
            'status_code' => 200,
            'device_type' => 'Masaüstü',
            'browser' => 'Chrome',
            'visitor_hash' => str_repeat('a', 64),
            'visited_at' => now(),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($supervisor)
            ->test(SiteTrafficOverview::class)
            ->assertSee('Bugünkü Ziyaret')
            ->assertSee('Son 7 Gün');

        Livewire::actingAs($supervisor)
            ->test(PopularSitePages::class)
            ->assertSee('Popüler Site Sayfaları')
            ->assertSee('/');
    }

    public function test_portal_user_can_see_listing_unique_visit_count(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INDIVIDUAL,
            'account_status' => AccountStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::withoutEvents(fn () => Listing::create([
            'user_id' => $user->id,
            'title' => 'Panel ziyaret sayacı ilanı',
            'slug' => 'panel-ziyaret-sayaci-ilani',
            'price' => 1000000,
            'type' => 'Satılık',
            'category' => 'Arsa',
            'eids_status' => 'verified',
            'publication_status' => 'approved',
            'eids_document_no' => '94536593',
            'city_id' => '80',
            'district_id' => 'Sumbas',
            'is_active' => true,
        ]));

        foreach (['a', 'b', 'b'] as $hash) {
            SiteVisit::query()->create([
                'path' => '/ilan/'.$listing->slug,
                'route_name' => 'listings.show',
                'method' => 'GET',
                'status_code' => 200,
                'visitor_hash' => str_repeat($hash, 64),
                'visited_at' => now(),
            ]);
        }

        Filament::setCurrentPanel(Filament::getPanel('portal'));

        Livewire::actingAs($user)
            ->test(RecentListings::class)
            ->assertSee('Görüntülenme')
            ->assertSee('2');
    }
}

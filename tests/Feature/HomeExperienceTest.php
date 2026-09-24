<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Services\MarketDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HomeExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_projects_are_public_and_drafts_are_hidden(): void
    {
        $published = Project::create([
            'title' => 'Zyde Park Evleri',
            'slug' => 'zyde-park-evleri',
            'developer_name' => 'ZYD Yapı',
            'is_published' => true,
            'is_featured' => true,
        ]);
        $draft = Project::create([
            'title' => 'Gizli Proje',
            'slug' => 'gizli-proje',
            'developer_name' => 'ZYD Yapı',
        ]);

        $this->get(route('projects.index'))->assertOk()->assertSee($published->title)->assertDontSee($draft->title);
        $this->get(route('projects.show', $published))->assertOk();
        $this->get(route('projects.show', $draft))->assertNotFound();
    }

    public function test_project_map_only_contains_published_projects_with_coordinates(): void
    {
        config()->set('services.google_maps.browser_key', 'browser-key');

        $visible = Project::create([
            'title' => 'Haritada Görünen Proje',
            'slug' => 'haritada-gorunen-proje',
            'developer_name' => 'ZYD',
            'latitude' => 37.07,
            'longitude' => 36.25,
            'is_published' => true,
            'published_at' => now(),
        ]);
        Project::create([
            'title' => 'Taslak Harita Projesi',
            'slug' => 'taslak-harita-projesi',
            'developer_name' => 'ZYD',
            'latitude' => 37.08,
            'longitude' => 36.26,
            'is_published' => false,
        ]);
        Project::create([
            'title' => 'Konumsuz Yayın',
            'slug' => 'konumsuz-yayin',
            'developer_name' => 'ZYD',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('id="project-map"', false)
            ->assertSee($visible->title)
            ->assertDontSee('Taslak Harita Projesi');
    }

    public function test_authenticated_user_can_save_featured_listing_count(): void
    {
        $user = User::factory()->create(['featured_listing_count' => 3]);

        $this->actingAs($user)->patchJson(route('account.display-preference'), [
            'featured_listing_count' => 6,
        ])->assertOk()->assertJson(['saved' => true]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'featured_listing_count' => 6]);
        $this->actingAs($user)->patchJson(route('account.display-preference'), [
            'featured_listing_count' => 4,
        ])->assertUnprocessable();
    }

    public function test_market_service_combines_tcmb_rates_with_gold_price(): void
    {
        config()->set('services.market_data.enabled', true);
        Cache::flush();
        Http::fake([
            'www.tcmb.gov.tr/*' => Http::response(<<<'XML'
                <Tarih_Date>
                    <Currency CurrencyCode="USD"><ForexSelling>40.0000</ForexSelling></Currency>
                    <Currency CurrencyCode="EUR"><ForexSelling>44.0000</ForexSelling></Currency>
                </Tarih_Date>
                XML, 200),
            'api.gold-api.com/*' => Http::response(['price' => 3100], 200),
        ]);

        $data = app(MarketDataService::class)->current();

        $this->assertSame(40.0, $data['usd']['value']);
        $this->assertSame(44.0, $data['eur']['value']);
        $this->assertEqualsWithDelta(3986.69, $data['gold']['value'], 0.01);
    }

    public function test_authenticated_public_layout_contains_phone_bottom_navigation(): void
    {
        $user = User::factory()->create(['role' => UserRole::INDIVIDUAL]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('phone-bottom-nav', false)
            ->assertSee(route('projects.index'), false);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\User;
use App\Services\MarketDataService;
use Database\Seeders\ProjectSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_projects_can_be_filtered_by_neighborhood(): void
    {
        Project::create([
            'title' => 'Rahime Hatun Projesi',
            'slug' => 'rahime-hatun-projesi',
            'developer_name' => 'ZYD',
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'neighborhood' => 'Rahime Hatun Mahallesi',
            'is_published' => true,
        ]);
        Project::create([
            'title' => 'Kurtuluş Projesi',
            'slug' => 'kurtulus-projesi',
            'developer_name' => 'ZYD',
            'city' => 'Osmaniye',
            'district' => 'Merkez',
            'neighborhood' => 'Kurtuluş Mahallesi',
            'is_published' => true,
        ]);

        $this->get(route('projects.index', ['neighborhood' => 'Rahime Hatun Mahallesi']))
            ->assertOk()
            ->assertSee('Rahime Hatun Projesi')
            ->assertDontSee('Kurtuluş Projesi');
    }

    public function test_project_index_exposes_all_project_photos_to_cards_and_map_popups(): void
    {
        $project = Project::create([
            'title' => 'Fotoğraflı Harita Projesi',
            'slug' => 'fotografli-harita-projesi',
            'developer_name' => 'ZYD',
            'cover_image' => 'projects/cover.jpg',
            'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
            'latitude' => 37.07,
            'longitude' => 36.25,
            'is_published' => true,
        ]);
        ProjectImage::create(['project_id' => $project->id, 'image_path' => 'projects/gallery/one.jpg', 'sort_order' => 0]);
        ProjectImage::create(['project_id' => $project->id, 'image_path' => 'projects/gallery/two.jpg', 'sort_order' => 1]);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('data-project-card-gallery', false)
            ->assertSee('data-project-card-next', false)
            ->assertSee('/storage/projects/gallery/one.jpg', false)
            ->assertSee('/storage/projects/gallery/two.jpg', false)
            ->assertSee('1 / 4')
            ->assertSee('data-project-card-video', false)
            ->assertSee('youtube_id', false)
            ->assertSee('aqz-KE-bpKQ', false);
    }

    public function test_project_detail_renders_multiple_photos_as_a_slider(): void
    {
        $project = Project::create([
            'title' => 'Galerili Proje',
            'slug' => 'galerili-proje',
            'developer_name' => 'ZYD',
            'cover_image' => 'projects/cover.jpg',
            'is_published' => true,
        ]);
        ProjectImage::create(['project_id' => $project->id, 'image_path' => 'projects/one.jpg', 'sort_order' => 0]);
        ProjectImage::create(['project_id' => $project->id, 'image_path' => 'projects/two.jpg', 'sort_order' => 1]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-project-slider', false)
            ->assertSee('data-project-next', false)
            ->assertSee('1 / 3');
    }

    public function test_project_seeder_creates_projects_with_six_photos_and_a_video(): void
    {
        $this->seed(ProjectSeeder::class);

        $projects = Project::with('images')->get();

        $this->assertCount(8, $projects);

        foreach ($projects as $project) {
            $photoPaths = collect([$project->cover_image])
                ->concat($project->images->pluck('image_path'));

            $this->assertCount(5, $project->images);
            $this->assertCount(6, $photoPaths);
            $this->assertCount(6, $photoPaths->unique());
            $this->assertSame('aqz-KE-bpKQ', $project->youtube_video_id);
            $this->assertNull($project->video_path);
        }
    }

    public function test_uploaded_project_video_is_the_last_slider_item(): void
    {
        $project = Project::create([
            'title' => 'Videolu Proje',
            'slug' => 'videolu-proje',
            'developer_name' => 'ZYD',
            'cover_image' => 'projects/cover.jpg',
            'video_path' => 'projects/videos/tanitim.mp4',
            'is_published' => true,
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('<video data-project-slide', false)
            ->assertSee('/storage/projects/videos/tanitim.mp4', false)
            ->assertSee('Proje videosunu göster')
            ->assertSee('1 / 2');
    }

    public function test_youtube_project_video_uses_privacy_enhanced_embed(): void
    {
        $project = Project::create([
            'title' => 'YouTube Projesi',
            'slug' => 'youtube-projesi',
            'developer_name' => 'ZYD',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_published' => true,
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-video-src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0"', false)
            ->assertSee('Proje videosunu göster');
    }

    public function test_project_form_contains_a_multiple_gallery_upload(): void
    {
        Storage::fake('public');
        $this->assertSame(
            ['required', 'file', 'max:'.config('projects.video.max_size_kb')],
            config('livewire.temporary_file_upload.rules'),
        );
        $user = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateProject::class)
            ->assertFormFieldExists('gallery_images', function ($field): bool {
                return $field->isMultiple() && $field->getMaxFiles() === 20;
            })
            ->assertFormFieldExists('video_path', function ($field): bool {
                return $field->getMaxSize() === config('projects.video.max_size_kb')
                    && $field->getAcceptedFileTypes() === config('projects.video.mime_types');
            })
            ->assertFormFieldExists('video_url')
            ->fillForm([
                'title' => 'Çok Fotoğraflı Proje',
                'slug' => 'cok-fotografli-proje',
                'developer_name' => 'ZYD Yapı',
                'status' => 'construction',
                'gallery_images' => [
                    UploadedFile::fake()->image('salon.jpg'),
                    UploadedFile::fake()->image('bahce.jpg'),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('slug', 'cok-fotografli-proje')->firstOrFail();
        $this->assertSame(2, $project->images()->count());
        $this->assertSame([0, 1], $project->images()->pluck('sort_order')->all());
    }

    public function test_project_form_rejects_invalid_or_multiple_video_sources(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'role' => UserRole::SUPERVISOR,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $baseForm = [
            'title' => 'Video Kaynak Testi',
            'slug' => 'video-kaynak-testi',
            'developer_name' => 'ZYD Yapı',
            'status' => 'construction',
        ];

        Livewire::test(CreateProject::class)
            ->fillForm([...$baseForm, 'video_url' => 'https://example.com/video'])
            ->call('create')
            ->assertHasFormErrors(['video_url']);

        Livewire::test(CreateProject::class)
            ->fillForm([
                ...$baseForm,
                'video_path' => UploadedFile::fake()->create('tanitim.mp4', 100, 'video/mp4'),
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->call('create')
            ->assertHasFormErrors(['video_path', 'video_url']);
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

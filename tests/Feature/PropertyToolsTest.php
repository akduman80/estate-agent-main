<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Filament\Pages\PropertyModeration;
use App\Filament\Resources\PropertyLikeResource;
use App\Filament\Resources\RentPeriodRateResource;
use App\Livewire\BlogList;
use App\Livewire\PropertyDiscussion;
use App\Livewire\RentCalculator;
use App\Models\Listing;
use App\Models\Post;
use App\Models\PropertyComment;
use App\Models\RentPeriodRate;
use App\Models\Setting;
use App\Models\User;
use App\Services\PhotoListingSuggestion;
use App\Services\PropertyInteractions;
use App\Services\RentIncreaseCalculator;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PropertyToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00'));
        Http::preventStrayRequests();
    }

    private function member(array $values = []): User
    {
        return User::factory()->create(array_merge(['role' => UserRole::INDIVIDUAL, 'account_status' => AccountStatus::ACTIVE], $values));
    }

    private function listing(User $owner, array $values = []): Listing
    {
        return Listing::withoutEvents(fn () => Listing::create(array_merge(['user_id' => $owner->id, 'title' => 'Harita ve etkileşim test ilanı', 'slug' => (string) Str::uuid(), 'price' => 1500000, 'currency' => 'TRY', 'type' => 'Satılık', 'category' => 'Arsa', 'city_id' => 'Osmaniye', 'district_id' => 'Kadirli', 'latitude' => 37.1, 'longitude' => 36.2, 'is_active' => true, 'publication_status' => 'approved', 'eids_status' => 'verified'], $values)));
    }

    public function test_voice_search_reuses_turkish_filters_without_external_service(): void
    {
        $this->postJson(route('search.voice'), ['text' => 'Kadirli’de kiralık işyeri göster'])->assertOk()->assertJsonPath('filters.district', 'Kadirli')->assertJsonPath('filters.type', 'Kiralık')->assertJsonPath('filters.category', 'İşyeri');
        $this->postJson(route('search.voice'), ['text' => '3 milyon liraya kadar satılık daire ara'])->assertOk()->assertJsonPath('filters.max_price', 3000000)->assertJsonPath('filters.category', 'Daire');
        $this->postJson(route('search.voice'), ['text' => '500 metrekareden büyük tarla ara'])->assertOk()->assertJsonPath('filters.min_area', 500);
        Http::assertNothingSent();
    }

    public function test_map_omits_hidden_unpublished_and_missing_coordinates_and_filters_area(): void
    {
        $owner = $this->member();
        $visible = $this->listing($owner, ['square_meters' => 750]);
        $this->listing($owner, ['form_details' => ['hide_location' => true]]);
        $this->listing($owner, ['latitude' => null, 'longitude' => null]);
        $this->listing($owner, ['publication_status' => 'draft']);
        $params = ['south' => 35, 'north' => 40, 'west' => 34, 'east' => 38, 'city' => 'Osmaniye', 'min_area' => 500];
        $this->getJson(route('tools.map.records', $params))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
        $params['min_area'] = 1000;
        $this->getJson(route('tools.map.records', $params))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('tools.map.records', ['south' => 999]))->assertUnprocessable();
    }

    public function test_map_loads_licensed_leaflet_assets_from_the_application(): void
    {
        $this->get(route('tools.map'))
            ->assertOk()
            ->assertSee('vendor/leaflet/leaflet.css')
            ->assertSee('vendor/leaflet/leaflet.js')
            ->assertSee('vendor/leaflet-markercluster/leaflet.markercluster.js')
            ->assertDontSee('unpkg.com/leaflet');
    }

    public function test_like_is_idempotent_persistent_and_distinct_from_favourites(): void
    {
        $owner = $this->member();
        $listing = $this->listing($owner);
        $this->actingAs($owner);
        $service = app(PropertyInteractions::class);
        $service->setLike('listing', $listing->id, true);
        $service->setLike('listing', $listing->id, true);
        $this->assertDatabaseCount('property_likes', 1);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->assertViewHas('liked', true)->assertViewHas('likeCount', 1);
        $service->setLike('listing', $listing->id, false);
        $service->setLike('listing', $listing->id, false);
        $this->assertDatabaseCount('property_likes', 0);
    }

    public function test_guest_like_redirects_and_blocked_or_suspended_users_cannot_comment(): void
    {
        $owner = $this->member();
        $listing = $this->listing($owner);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->call('like', true)->assertRedirect(route('login'));
        $this->actingAs($owner);
        DB::table('property_interaction_preferences')->insert(['user_id' => $owner->id, 'blocked' => true]);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->set('body', 'Uygun bir soru metni')->call('submit')->assertForbidden();
        $this->assertDatabaseCount('property_comments', 0);
    }

    public function test_pending_question_is_not_public_and_only_owner_can_answer_after_approval(): void
    {
        $owner = $this->member();
        $asker = $this->member();
        $stranger = $this->member();
        $admin = $this->member(['role' => UserRole::SUPERVISOR]);
        $listing = $this->listing($owner);
        $service = app(PropertyInteractions::class);
        $this->actingAs($asker);
        $uuid = (string) Str::uuid();
        $question = $service->submit('listing', $listing->id, 'Yola cephesi var mı?', $uuid);
        $same = $service->submit('listing', $listing->id, 'Yola cephesi var mı?', $uuid);
        $this->assertSame($question->id, $same->id);
        $this->assertDatabaseCount('property_comments', 1);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->assertDontSee('Yola cephesi var mı?');
        $this->actingAs($admin);
        $service->moderate($question, 'approved', 'Uygun soru');
        $this->assertDatabaseHas('property_notifications', ['user_id' => $owner->id, 'comment_id' => $question->id]);
        $this->actingAs($stranger);
        try {
            $service->submit('listing', $listing->id, 'Evet, yola cephesi var.', (string) Str::uuid(), $question->id);
            $this->fail('Unauthorized answer accepted');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->actingAs($owner);
        $answer = $service->submit('listing', $listing->id, 'Yol durumunu belgelerden teyit ediniz.', (string) Str::uuid(), $question->id);
        $this->assertSame('İlan Sahibi', $answer->author_label);
        $this->actingAs($admin);
        $service->moderate($answer, 'approved', 'Uygun yanıt');
        $this->assertDatabaseHas('property_notifications', ['user_id' => $asker->id, 'comment_id' => $answer->id]);
        $this->assertDatabaseCount('property_moderation_logs', 2);
    }

    public function test_edit_returns_to_review_and_own_deletion_retains_approved_replies(): void
    {
        $owner = $this->member();
        $listing = $this->listing($owner);
        $this->actingAs($owner);
        $service = app(PropertyInteractions::class);
        $root = $service->submit('listing', $listing->id, 'İlanın durumu hakkında bilgi alabilir miyim?', (string) Str::uuid());
        $root->update(['status' => 'approved']);
        $reply = $service->submit('listing', $listing->id, 'İlan halen değerlendirmeye açıktır.', (string) Str::uuid(), $root->id);
        $reply->update(['status' => 'approved']);
        $service->edit($reply, 'Yeni bilgi için iletişime geçebilirsiniz.');
        $this->assertSame('pending', $reply->fresh()->status);
        $reply->update(['status' => 'approved']);
        $service->removeOwn($root);
        $this->assertSame('deleted', $root->fresh()->status);
        $this->assertSame('approved', $reply->fresh()->status);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->assertSee('Yeni bilgi için iletişime geçebilirsiniz.');
    }

    public function test_xss_phone_identity_link_and_threat_are_rejected(): void
    {
        $service = app(PropertyInteractions::class);
        foreach (['<img src=x onerror=alert(1)>', 'Bana 0555 111 22 33 numarasından ulaşın.', 'Kimlik 10000000146', 'https://fraud.example', 'Seni öldürürüm'] as $body) {
            try {
                $service->body($body);
                $this->fail('Unsafe body accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_current_rent_math_and_lower_contract_rate_use_cents(): void
    {
        $service = app(RentIncreaseCalculator::class);
        $data = ['rent' => '10000.01', 'kind' => 'home', 'renewal_date' => '2026-11-10', 'contract_rate' => ''];
        $result = $service->calculate($data);
        $this->assertEquals(3123, $result['increase']);
        $this->assertSame(13123.01, $result['new_rent']);
        $data['contract_rate'] = '10';
        $result = $service->calculate($data);
        $this->assertSame(11000.01, $result['new_rent']);
        $this->assertSame(12000, $result['annual_difference']);
    }

    public function test_historic_residential_cap_uses_exact_boundary_and_not_workplace(): void
    {
        $service = app(RentIncreaseCalculator::class);
        $data = ['rent' => '10000', 'kind' => 'home', 'renewal_date' => '2024-07-01', 'contract_rate' => ''];
        $this->assertEquals(25, $service->calculate($data)['cap']);
        $data['renewal_date'] = '2024-07-02';
        $this->assertEquals(65.07, $service->calculate($data)['cap']);
        $data['renewal_date'] = '2024-07-01';
        $data['kind'] = 'workplace';
        $this->assertEquals(65.07, $service->calculate($data)['cap']);
    }

    public function test_official_historical_rent_archive_has_each_period_and_bulletin_date(): void
    {
        $this->assertDatabaseCount('rent_period_rates', 95);
        $this->assertDatabaseHas('rent_period_rates', [
            'period' => '2019-01-01', 'rate' => '16.33', 'published_on' => '2019-01-03',
            'source_url' => 'https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2018-30848&dil=1',
        ]);
        $this->assertDatabaseHas('rent_period_rates', [
            'period' => '2022-05-01', 'rate' => '34.46', 'published_on' => '2022-05-05',
        ]);
        $this->assertDatabaseHas('rent_period_rates', [
            'period' => '2026-11-01', 'rate' => '31.23', 'published_on' => '2026-11-03',
        ]);

        $result = app(RentIncreaseCalculator::class)->calculate([
            'rent' => '10000', 'kind' => 'home', 'renewal_date' => '2022-05-12', 'contract_rate' => '',
        ]);
        $this->assertEquals(34.46, $result['cpi']);
        $this->assertEquals(34.46, $result['applied']);
        $this->assertSame('2022-05-05', $result['published_on']);

        $existingRateId = DB::table('rent_period_rates')->where('period', '2024-07-01')->value('id');
        DB::table('rent_rate_logs')->where('rate_id', $existingRateId)->delete();
        $migration = require database_path('migrations/2026_11_10_000003_backfill_historical_rent_rates.php');
        $migration->up();
        $this->assertDatabaseHas('rent_rate_logs', ['rate_id' => $existingRateId]);
    }

    public function test_future_and_special_rent_cases_do_not_produce_guesses(): void
    {
        Livewire::test(RentCalculator::class)->set('rent', '10000')->set('renewal_date', '2026-12-01')->call('calculate')->assertHasErrors('renewal_date')->assertSet('result', null);
        Livewire::test(RentCalculator::class)->set('rent', '10000')->set('renewal_date', '2026-10-01')->call('calculate')->assertHasNoErrors()->assertSet('result.cpi', 31.49);
        Livewire::test(RentCalculator::class)->set('rent', '10000')->set('special', true)->call('calculate')->assertHasErrors('special');
    }

    public function test_photo_analysis_requires_consent_and_keeps_existing_files(): void
    {
        Storage::fake('public');
        $member = $this->member();
        $this->actingAs($member);
        try {
            app(PhotoListingSuggestion::class)->suggest('Arsa', ['listings/images/'.$member->id.'/photo.jpg'], false);
            $this->fail('No consent accepted');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        Http::assertNothingSent();
    }

    public function test_property_pages_render_and_moderation_requires_supervisor(): void
    {
        $this->get(route('tools.parcel'))->assertOk()->assertSee('https://parselsorgu.tkgm.gov.tr/', false);
        $this->get(route('tools.map'))->assertOk();
        $this->get(route('tools.rent'))->assertOk();
        $member = $this->member();
        $this->actingAs($member);
        $this->get('/admin/property-moderation')->assertForbidden();
        $this->assertFalse(RentPeriodRateResource::canViewAny());
        $this->assertFalse(PropertyLikeResource::canViewAny());
    }

    public function test_photo_analysis_sends_only_thumbnails_and_never_saves_listing(): void
    {
        $member = $this->member();
        $this->actingAs($member);
        Storage::fake('public');
        $path = 'listings/images/'.$member->id.'/photo.jpg';
        $file = UploadedFile::fake()->image('photo.jpg', 900, 600);
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        config()->set('assistant.gemini.key', 'test-key');
        config()->set('assistant.gemini.model', 'gemini-test');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['status' => 'completed', 'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => '{"title":"Görsellerle tanıtılan arsa","description":"Açık alan görünümü bulunmaktadır. Kullanıcı teyidi gereklidir."}']]]]])]);
        $before = hash('sha256', Storage::disk('public')->get($path));
        $result = app(PhotoListingSuggestion::class)->suggest('Arsa', [$path], true);
        $this->assertSame('Görsellerle tanıtılan arsa', $result['title']);
        $this->assertDatabaseCount('listings', 0);
        $this->assertSame($before, hash('sha256', Storage::disk('public')->get($path)));
        Http::assertSent(function ($request) {
            $image = $request['input'][0]['content'][1];
            $size = getimagesizefromstring(base64_decode($image['data']));

            return $request['store'] === false && $image['mime_type'] === 'image/jpeg' && max($size[0], $size[1]) <= 640;
        });
    }

    public function test_admin_page_renders_and_moderation_setting_is_audited(): void
    {
        $admin = $this->member(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(PropertyModeration::class)->assertOk()->call('setPremoderation', false);
        $this->assertFalse(Setting::get('property_premoderation'));
        $this->assertDatabaseHas('property_moderation_logs', ['user_id' => $admin->id, 'action' => 'premoderation']);
    }

    public function test_rate_source_rejects_future_period_and_non_official_hosts(): void
    {
        $admin = $this->member(['role' => UserRole::SUPERVISOR]);
        $this->actingAs($admin);
        $this->expectException(ValidationException::class);
        RentPeriodRate::create(['period' => '2026-12-01', 'rate' => '40', 'source_url' => 'https://example.com/rate', 'published_on' => '2026-11-10']);
    }

    public function test_pdf_download_recomputes_instead_of_trusting_client_result(): void
    {
        Livewire::test(RentCalculator::class)->set('rent', '10000')->set('renewal_date', '2026-11-10')->set('result', ['new_rent' => 1])->call('download')->assertFileDownloaded('kira-artisi-2026-11-10.pdf')->assertSet('result.new_rent', 13123);
    }

    public function test_news_comments_replies_notifications_and_real_like_ordering(): void
    {
        $member = $this->member();
        $other = $this->member();
        $admin = $this->member(['role' => UserRole::SUPERVISOR]);
        $post = Post::withoutEvents(fn () => Post::create(['user_id' => $admin->id, 'title' => 'Emlak haberi', 'slug' => 'etkilesim-haberi', 'content' => 'Haber içeriği', 'status' => 'published', 'published_at' => now()->subDay()]));
        $second = Post::withoutEvents(fn () => Post::create(['user_id' => $admin->id, 'title' => 'İkinci haber', 'slug' => 'ikinci-etkilesim-haberi', 'content' => 'İçerik', 'status' => 'published', 'published_at' => now()->subDays(2)]));
        $service = app(PropertyInteractions::class);
        $this->actingAs($member);
        $service->setLike('post', $second->id, true);
        Livewire::test(BlogList::class)->set('sort', 'likes')->assertViewHas('posts', fn ($posts) => $posts->first()->id === $second->id);
        $comment = $service->submit('post', $post->id, 'Bu haber için teşekkür ederim.', (string) Str::uuid());
        $this->actingAs($admin);
        $service->moderate($comment, 'approved', 'Uygun yorum');
        $this->actingAs($other);
        $reply = $service->submit('post', $post->id, 'Ben de bu konuda bilgi arıyordum.', (string) Str::uuid(), $comment->id);
        $this->actingAs($admin);
        $service->moderate($reply, 'approved', 'Uygun yanıt');
        $this->assertDatabaseHas('property_notifications', ['user_id' => $member->id, 'comment_id' => $reply->id]);
        auth()->logout();
        $this->get(route('blogs.show', $post))->assertOk()->assertSee('Bu haber için teşekkür ederim.')->assertSee('Ben de bu konuda bilgi arıyordum.');
    }

    public function test_reporting_is_idempotent_and_notification_opt_out_is_respected(): void
    {
        $owner = $this->member();
        $member = $this->member();
        $admin = $this->member(['role' => UserRole::SUPERVISOR]);
        $listing = $this->listing($owner);
        $service = app(PropertyInteractions::class);
        $this->actingAs($member);
        $comment = $service->submit('listing', $listing->id, 'İlanın yol durumu nedir?', (string) Str::uuid());
        DB::table('property_interaction_preferences')->insert(['user_id' => $owner->id, 'notifications' => false]);
        $this->actingAs($admin);
        $service->moderate($comment, 'approved', 'Uygun soru');
        $this->assertDatabaseCount('property_notifications', 0);
        $this->actingAs($owner);
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->call('report', $comment->id)->call('report', $comment->id);
        $this->assertDatabaseCount('property_reports', 1);
    }

    public function test_reply_threads_are_bounded_and_paginated(): void
    {
        $member = $this->member();
        $listing = $this->listing($member);
        $this->actingAs($member);
        $root = app(PropertyInteractions::class)->submit('listing', $listing->id, 'Soru metni hakkında bilgi', (string) Str::uuid());
        $root->update(['status' => 'approved']);
        for ($i = 1; $i <= 13; $i++) {
            PropertyComment::create(['user_id' => $member->id, 'listing_id' => $listing->id, 'parent_id' => $root->id, 'kind' => 'answer', 'body' => 'Yanıt '.$i, 'status' => 'approved', 'request_id' => (string) Str::uuid()]);
        }
        Livewire::test(PropertyDiscussion::class, ['targetType' => 'listing', 'targetId' => $listing->id])->assertViewHas('comments', fn ($comments) => $comments->first()->replies->count() === 3)->call('showReplies', $root->id)->assertViewHas('threadReplies', fn ($replies) => $replies->total() === 13 && $replies->count() === 10)->call('setPage', 2, 'replyPage')->assertViewHas('threadReplies', fn ($replies) => $replies->count() === 3);
    }
}

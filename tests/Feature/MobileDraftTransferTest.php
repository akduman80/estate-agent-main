<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\MobileDraftImport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileDraftTransferTest extends TestCase
{
    use RefreshDatabase;
    private string $token = 'test-device-draft-key-123456789012345678901234567890';

    private function owner(UserRole $role = UserRole::SUPERVISOR): User
    {
        $user = User::factory()->create(['role' => $role, 'account_status' => AccountStatus::ACTIVE]);
        DB::table('mobile_draft_tokens')->insert(['user_id' => $user->id, 'name' => 'test',
            'token_hash' => hash('sha256', $this->token), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        return $user;
    }
    private function record(string $kind = 'ilan'): array
    {
        return ['id' => '32fba927-cde6-47ea-a44f-9a923cf96e3c', 'kind' => $kind, 'adSoyad' => 'Seçili isim',
            'detay' => 'Seçili açıklama', 'tasinmazNo' => '00123456', 'place' => [
                'lat' => 37.0742, 'lng' => 36.2478, 'il' => 'Osmaniye', 'ilce' => 'Merkez',
                'mahalle' => 'Rahime Hatun', 'tamAdres' => 'Seçili açık adres', 'ada' => '12', 'parsel' => '34'],
            'ekBilgiler' => [['ad' => 'Ödeme', 'deger' => 'Peşin']]];
    }
    private function send(array $record, ?string $revision = null, array $files = [])
    {
        return $this->post('/api/v1/mobile-drafts', array_merge(['record' => json_encode($record),
            'expected_revision' => $revision], $files), ['Authorization' => 'Bearer '.$this->token, 'Accept' => 'application/json']);
    }
    public function test_selected_listing_creates_only_an_unpublished_draft_and_preserves_eids(): void
    {
        $owner = $this->owner();
        $response = $this->send($this->record())->assertOk()->assertJsonPath('status', 'draft');
        $listing = Listing::firstOrFail();
        $this->assertSame($owner->id, $listing->user_id);
        $this->assertSame('pending', $listing->eids_status);
        $this->assertSame('draft', $listing->publication_status);
        $this->assertFalse($listing->is_active);
        $this->assertSame('00123456', $listing->eids_document_no);
        $this->assertEquals(37.0742, $listing->latitude);
        $this->assertNull($listing->verified_at);
        $this->get(route('listings.show', $listing))->assertNotFound();
        $this->assertArrayNotHasKey('telefon', MobileDraftImport::first()->payload);
    }
    public function test_retry_and_update_use_the_same_listing_and_unselected_information_is_removed(): void
    {
        $this->owner();
        $r = $this->record();
        $first = $this->send($r)->assertOk();
        $this->send($r)->assertOk()->assertJsonPath('id', $first->json('id'));
        unset($r['tasinmazNo'], $r['ekBilgiler']);
        $r['detay'] = 'Güncel açıklama';
        $this->send($r, $first->json('revision'))->assertOk()->assertJsonPath('id', $first->json('id'));
        $this->assertDatabaseCount('listings', 1);
        $this->assertDatabaseCount('mobile_draft_imports', 1);
        $this->assertSame('', Listing::first()->eids_document_no);
        $this->assertSame('Güncel açıklama', Listing::first()->description);
    }
    public function test_only_media_checked_by_the_client_is_stored_and_replaced(): void
    {
        Storage::fake('public'); Storage::fake('local'); $this->owner();
        $first = $this->send($this->record(), null, ['photos' => [UploadedFile::fake()->image('selected.jpg')]])->assertOk();
        $this->assertDatabaseCount('listing_images', 1);
        $old = Listing::first()->images->first()->image_path;
        $r = $this->record(); $r['detay'] = 'Fotoğrafsız';
        $this->send($r, $first->json('revision'))->assertOk();
        $this->assertDatabaseCount('listing_images', 0);
        Storage::disk('public')->assertMissing($old);
    }
    public function test_unselected_address_and_identity_can_be_completed_later_on_website(): void
    {
        $this->owner();
        $this->send(['id' => 'minimal', 'kind' => 'ilan', 'detay' => 'Sadece seçilen açıklama', 'place' => []])->assertOk();
        $l = Listing::first();
        $this->assertNull($l->latitude);
        $this->assertSame('', $l->eids_document_no);
        $this->assertArrayNotHasKey('adSoyad', MobileDraftImport::first()->payload);
    }
    public function test_existing_legacy_project_is_reused_and_update_waits_for_web_review(): void
    {
        $user = $this->owner(); $r = $this->record('proje');
        $p = Project::create(['title' => 'Eski proje', 'slug' => 'eski', 'developer_name' => 'Eski firma',
            'is_published' => true, 'mobile_project_uuid' => $r['id']]);
        $this->send($r)->assertOk();
        $this->assertDatabaseCount('projects', 1);
        $import = MobileDraftImport::first();
        $this->assertEquals($p->id, $import->project_id);
        $this->assertTrue($import->pending_update);
        $this->assertSame('Eski proje', $p->fresh()->title);
        $this->actingAs($user);
        app(\App\Http\Controllers\MobileDraftController::class)->applyUpdate($import);
        $this->assertTrue($p->fresh()->is_published);
        $this->assertSame('Seçili isim', $p->fresh()->developer_name);
        $this->assertDatabaseCount('projects', 1);
    }
    public function test_unauthorized_access_is_rejected_and_eids_flags_cannot_be_injected(): void
    {
        $this->postJson('/api/v1/mobile-drafts', [])->assertUnauthorized();
        $this->owner(); $r = $this->record();
        $r['eids_status'] = 'verified'; $r['is_active'] = true; $r['publication_status'] = 'approved';
        $this->send($r)->assertOk();
        $this->assertSame('pending', Listing::first()->eids_status);
        $this->assertFalse(Listing::first()->is_active);
        $this->assertArrayNotHasKey('eids_status', MobileDraftImport::first()->payload);
    }
    public function test_old_publish_endpoint_is_retired(): void
    {
        $this->postJson('/api/v1/projects/publish', ['local_project_id' => 1])->assertStatus(410);
        $this->assertDatabaseCount('projects', 0);
    }
    public function test_verified_listing_updates_same_record_without_changing_eids(): void
    {
        $user = $this->owner();
        $first = $this->send($this->record())->assertOk();
        $listing = Listing::first();
        $listing->forceFill(['eids_status' => 'verified'])->saveQuietly();
        $r = $this->record(); $r['detay'] = 'Yeni açıklama'; $r['tasinmazNo'] = '999999';
        $revision = $this->withToken($this->token)->getJson('/api/v1/mobile-drafts')->json('records.0.revision');
        $this->send($r, $revision)->assertOk();
        $this->assertSame('Seçili açıklama', $listing->fresh()->description);
        $this->actingAs($user);
        app(\App\Http\Controllers\MobileDraftController::class)->applyUpdate(MobileDraftImport::first());
        $this->assertSame('Yeni açıklama', $listing->fresh()->description);
        $this->assertSame('00123456', $listing->fresh()->eids_document_no);
        $this->assertSame('verified', $listing->fresh()->eids_status);
        $this->assertDatabaseCount('listings', 1);
    }
    public function test_pc_edit_rejects_stale_transfer_and_generated_extras_do_not_duplicate(): void
    {
        $this->owner(); $r = $this->record('proje'); $r['telefon'] = '05551234567';
        $first = $this->send($r)->assertOk();
        $project = Project::first();
        $project->forceFill(['latitude' => 37.1, 'longitude' => 36.3, 'updated_at' => now()->addMinute()])->saveQuietly();
        $r['detay'] = 'Telefonda değişti';
        $this->send($r, $first->json('revision'))->assertStatus(409);
        $response = $this->withToken($this->token)->getJson('/api/v1/mobile-drafts')->assertOk();
        $this->assertEquals(37.1, $response->json('records.0.record.place.lat'));
        $this->assertCount(1, $response->json('records.0.record.ekBilgiler'));
        $this->assertDatabaseCount('projects', 1);
    }
    public function test_expired_device_key_is_rejected(): void
    {
        $this->owner(); DB::table('mobile_draft_tokens')->update(['expires_at' => now()->subMinute()]);
        $this->send($this->record())->assertUnauthorized();
        $this->assertDatabaseCount('listings', 0);
    }
    public function test_public_shared_map_does_not_expose_draft_listings_or_projects(): void
    {
        $this->owner(); $this->send($this->record('proje'))->assertOk();
        $this->send($this->record())->assertOk();
        $this->assertSame([], \App\Services\SharedMapRecords::records());
        $this->assertCount(2, \App\Services\SharedMapRecords::records(true));
        $this->get('/harita')->assertOk()->assertSee('zyd-map/zyd-project-map.js', false);
    }
}

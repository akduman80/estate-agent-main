<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileProjectPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_publish_accepts_legacy_publish_token_config(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');

        $this->postJson(route('api.projects.publish'), [
            'local_project_id' => 123,
            'contractor_name' => 'Selim Karadag',
            'address' => 'Osmaniye / Merkez / Rahime Hatun',
        ], [
            'X-ZYD-Publish-Token' => 'test-token',
        ])->assertCreated()
            ->assertJsonPath('message', 'Proje zydemlak.com üzerinde yayınlandı ve panelden yönetilebilir.');

        $this->assertDatabaseHas('projects', [
            'title' => 'Selim Karadag Merkez Rahime Hatun',
            'developer_name' => 'Selim Karadag',
            'mobile_local_project_id' => 123,
            'is_published' => true,
        ]);
    }

    public function test_repeated_mobile_publication_updates_the_same_project(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $uuid = 'df7d28b9-3d6d-4fb7-89cc-2deae485b33f';
        $publicationId = null;

        foreach (['Rahime Hatun', 'Kurtuluş', 'Eyüpsultan'] as $index => $neighborhood) {
            $response = $this->postJson(route('api.projects.publish'), [
                'local_project_id' => 456,
                'project_uuid' => $uuid,
                'remote_publication_id' => $publicationId,
                'action' => $index === 0 ? 'publish' : 'update',
                'contractor_name' => 'ZYD Test Projesi',
                'address' => 'Osmaniye / Merkez / '.$neighborhood,
                'latitude' => 37.0700000 + ($index / 100),
                'longitude' => 36.2500000 + ($index / 100),
            ], [
                'Authorization' => 'Bearer test-token',
            ])->assertSuccessful();

            $publicationId ??= $response->json('id');
            $response->assertJsonPath('id', $publicationId);
        }

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', [
            'id' => $publicationId,
            'mobile_project_uuid' => $uuid,
            'mobile_local_project_id' => null,
            'neighborhood' => 'Eyüpsultan',
            'latitude' => 37.09,
            'longitude' => 36.27,
        ]);
    }

    public function test_legacy_retry_without_a_saved_server_response_is_idempotent(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $payload = [
            'local_project_id' => 654,
            'contractor_name' => 'Eski Uygulama',
            'address' => 'Osmaniye / Merkez / Alibeyli',
        ];

        $firstId = $this->postJson(route('api.projects.publish'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertCreated()->json('id');

        $this->postJson(route('api.projects.publish'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertOk()->assertJsonPath('id', $firstId);

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_legacy_remote_publication_id_claims_and_updates_an_existing_project(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $project = Project::create([
            'title' => 'Eski Mobil Proje',
            'slug' => 'eski-mobil-proje-19',
            'developer_name' => 'Mobil uygulama',
            'is_published' => true,
        ]);

        $this->postJson(route('api.projects.publish'), [
            'local_project_id' => 19,
            'remote_publication_id' => $project->id,
            'contractor_name' => 'Güncel Firma',
            'address' => 'Osmaniye / Merkez / Haraz',
        ], [
            'Authorization' => 'Bearer test-token',
        ])->assertOk()->assertJsonPath('id', $project->id);

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'mobile_local_project_id' => 19,
            'developer_name' => 'Güncel Firma',
        ]);
    }

    public function test_mobile_publication_replaces_the_project_photo_gallery(): void
    {
        Storage::fake('public');
        config()->set('services.zyd_mobile.publish_token', 'test-token');

        $created = $this->post(route('api.projects.publish'), [
            'local_project_id' => 77,
            'project_uuid' => 'f57704a9-08ec-461d-b353-8e0a020ef889',
            'selected_photo_count' => 2,
            'replace_photos' => 1,
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.png'),
            ],
        ], [
            'Authorization' => 'Bearer test-token',
            'Accept' => 'application/json',
        ])->assertCreated();

        $project = Project::with('images')->findOrFail($created->json('id'));
        $oldPaths = $project->images->pluck('image_path')->all();
        $this->assertCount(2, $oldPaths);
        $this->assertSame($oldPaths[0], $project->cover_image);

        $this->post(route('api.projects.publish'), [
            'local_project_id' => 77,
            'project_uuid' => 'f57704a9-08ec-461d-b353-8e0a020ef889',
            'remote_publication_id' => $project->id,
            'action' => 'update',
            'selected_photo_count' => 1,
            'replace_photos' => 1,
            'photos' => [UploadedFile::fake()->image('replacement.webp')],
        ], [
            'Authorization' => 'Bearer test-token',
            'Accept' => 'application/json',
        ])->assertOk();

        $project->refresh()->load('images');
        $this->assertCount(1, $project->images);
        $this->assertSame($project->images->first()->image_path, $project->cover_image);

        foreach ($oldPaths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_mobile_project_can_be_unpublished_and_republished_without_a_duplicate(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $payload = [
            'local_project_id' => 88,
            'project_uuid' => 'b52860cf-ebc1-4412-b1cb-5ac8f1056515',
            'address' => 'Osmaniye / Merkez / Raufbey',
            'latitude' => 37.074,
            'longitude' => 36.247,
        ];

        $created = $this->postJson(route('api.projects.publish'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertCreated();

        $payload['remote_publication_id'] = $created->json('id');
        $payload['action'] = 'unpublish';
        $payload['is_published'] = false;
        $this->postJson(route('api.projects.publish'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertOk()->assertJsonPath('is_published', false);

        $payload['action'] = 'publish';
        $payload['is_published'] = true;
        $this->postJson(route('api.projects.publish'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertOk()->assertJsonPath('is_published', true);

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_conflicting_project_identifiers_are_rejected(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $project = Project::create([
            'mobile_project_uuid' => '56c68265-e2ce-43bd-a129-e724814e306b',
            'mobile_local_project_id' => 10,
            'title' => 'Bağlı Proje',
            'slug' => 'bagli-proje',
            'developer_name' => 'Mobil uygulama',
        ]);

        $this->postJson(route('api.projects.publish'), [
            'local_project_id' => 11,
            'project_uuid' => '56c68265-e2ce-43bd-a129-e724814e306b',
            'remote_publication_id' => $project->id,
        ], [
            'Authorization' => 'Bearer test-token',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('local_project_id');
    }

    public function test_mobile_publish_rejects_missing_token(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');

        $this->postJson(route('api.projects.publish'), [
            'local_project_id' => 123,
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'ZYD Emlak yayın anahtarı geçersiz veya eksik.');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_mobile_publish_reports_missing_server_token_configuration(): void
    {
        config()->set('services.zyd_mobile.publish_token', null);

        $this->postJson(route('api.projects.publish'), [
            'local_project_id' => 123,
        ], [
            'X-ZYD-Publish-Token' => 'test-token',
        ])->assertStatus(503)
            ->assertJsonPath('message', 'Mobil yayın servisi henüz yapılandırılmadı. Sunucuda ZYD_MOBILE_PUBLISH_TOKEN veya ZYD_PUBLISH_TOKEN tanımlanmalı.');
    }
}

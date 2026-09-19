<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'is_published' => true,
        ]);
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

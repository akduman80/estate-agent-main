<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MobileProjectPublishTest extends TestCase
{
    use RefreshDatabase;
    public function test_legacy_direct_publish_is_retired_without_creating_a_project(): void
    {
        config()->set('services.zyd_mobile.publish_token', 'test-token');
        $this->postJson(route('api.projects.publish'), ['local_project_id' => 123], ['X-ZYD-Publish-Token' => 'test-token'])->assertStatus(410);
        $this->assertDatabaseCount('projects', 0);
    }
}

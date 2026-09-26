<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_and_admin_login_expose_their_own_installable_app(): void
    {
        $this->get('/')->assertOk()->assertSee('/pwa/site.webmanifest', false)
            ->assertSee('/pwa/install.js', false)->assertDontSee('/pwa/admin.webmanifest', false);
        $this->get('/admin/login')->assertOk()->assertSee('/pwa/admin.webmanifest', false)
            ->assertSee('/pwa/install.js', false)->assertDontSee('/pwa/site.webmanifest', false);
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_projects_login_exposes_the_projects_installable_app(): void
    {
        $this->get('/projeler-app/login')->assertOk()
            ->assertSee('/pwa/projects.webmanifest', false)
            ->assertSee('/pwa/install.js', false)
            ->assertDontSee('/pwa/admin.webmanifest', false);

        $this->get('/projeler-app')->assertRedirect('/projeler-app/login');
    }

    public function test_manifests_have_distinct_ids_and_valid_local_icons(): void
    {
        $ids = [];
        foreach (['site' => '/', 'admin' => '/admin', 'projects' => '/projeler-app/projects'] as $kind => $start) {
            $manifest = json_decode(file_get_contents(public_path("pwa/$kind.webmanifest")), true, flags: JSON_THROW_ON_ERROR);
            $ids[] = $manifest['id'];
            $this->assertSame($start, $manifest['start_url']);
            $this->assertSame('standalone', $manifest['display']);
            foreach ($manifest['icons'] as $icon) {
                [$width, $height] = getimagesize(public_path(ltrim($icon['src'], '/')));
                $this->assertSame("{$width}x{$height}", $icon['sizes']);
            }
        }
        $this->assertCount(3, array_unique($ids));
    }
}

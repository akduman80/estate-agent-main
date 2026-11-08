<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialShareIconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_and_project_pages_share_the_correct_content_with_icons_only(): void
    {
        $this->withoutVite();
        $post = Post::withoutEvents(fn () => Post::create([
            'user_id' => User::factory()->create()->id, 'title' => 'Haber test',
            'slug' => 'haber-test', 'content' => 'İçerik', 'status' => 'published', 'published_at' => now()->subDay(),
        ]));
        $project = Project::create([
            'title' => 'Proje test', 'slug' => 'proje-test', 'developer_name' => 'Test',
            'is_published' => true, 'published_at' => now()->subDay(),
        ]);
        foreach ([[route('blogs.show', $post->slug), true], [route('projects.show', $project->slug), false]] as [$url, $isNews]) {
            $response = $this->get($url)->assertOk();
            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new DOMXPath($doc);
            $cards = $xpath->query('//*[@data-listing-share]');
            $this->assertSame(1, $cards->length);
            $this->assertSame($url, $cards->item(0)->getAttribute('data-url'));
            $position = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," news-share-position ")]');
            $this->assertSame($isNews ? 1 : 0, $position->length);
            if ($isNews) {
                $category = $xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," bg-orange-100 ")]');
                $this->assertSame(1, $category->length);
                $this->assertGreaterThan($position->item(0)->getLineNo(), $category->item(0)->getLineNo());
            }
            $controls = $xpath->query('//*[contains(@class,"listing-share__platforms")]/*');
            $this->assertSame(9, $controls->length);
            foreach ($controls as $control) {
                $this->assertNotEmpty($control->getAttribute('aria-label'));
                $this->assertSame(1, $xpath->query('.//*[local-name()="svg"]', $control)->length);
                $this->assertSame(0, $xpath->query('./text()[normalize-space()]', $control)->length);
            }
            $this->assertSame(0, $xpath->query('//*[contains(@class,"listing-share__preview")]')->length);
            $this->assertStringContainsString(rawurlencode($url), $response->getContent());
        }
    }
}

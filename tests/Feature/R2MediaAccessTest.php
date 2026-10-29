<?php

namespace Tests\Feature;

use App\Storage\R2MediaFilesystem;
use Aws\{MockHandler, Result};
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Utils;
use Tests\TestCase;

class R2MediaAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32)), 'cloudflare_r2.enabled' => true,
            'cloudflare_r2.account_id' => str_repeat('a', 32), 'filesystems.disks.r2_public' => [
                'key' => 'fake', 'secret' => 'fake', 'bucket' => 'test-media',
                'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com',
            ]]);
    }

    private function mockObject(string $body, array $extra = []): void
    {
        $handler = new MockHandler;
        $handler->append(new Result(array_merge(['Body' => Utils::streamFor($body), 'ContentLength' => strlen($body)], $extra)));
        $this->app->instance(S3Client::class, new S3Client(['version' => 'latest', 'region' => 'auto',
            'credentials' => ['key' => 'fake', 'secret' => 'fake'], 'handler' => $handler]));
    }

    public function test_missing_expired_and_modified_signatures_are_rejected(): void
    {
        $this->get('/media/r2/posts/test.jpg')->assertForbidden();
        $url = R2MediaFilesystem::mediaUrl('posts/test.jpg');
        $this->get(str_replace('test.jpg', 'other.jpg', $url))->assertForbidden();
        $this->travel(61)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_private_paths_are_never_served_even_with_valid_signature(): void
    {
        $this->get(R2MediaFilesystem::mediaUrl('contracts/private.pdf'))->assertNotFound();
    }

    public function test_signed_image_and_video_range_have_correct_body(): void
    {
        $this->mockObject('image-bytes');
        $response = $this->get(R2MediaFilesystem::mediaUrl('posts/test.jpg'));
        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('image-bytes', $response->streamedContent());
        $this->mockObject('part', ['ContentRange' => 'bytes 0-3/20']);
        $response = $this->withHeader('Range', 'bytes=0-3')->get(R2MediaFilesystem::mediaUrl('listings/videos/1/test.mp4'));
        $response->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-3/20');
        $this->assertSame('part', $response->streamedContent());
    }

    public function test_hls_manifest_links_receive_their_own_signatures(): void
    {
        $this->mockObject("#EXTM3U\n144.m3u8\n");
        $response = $this->get(R2MediaFilesystem::mediaUrl('news-video/streams/1/generation/master.m3u8'));
        $response->assertOk();
        $line = explode("\n", $response->getContent())[1];
        $this->assertStringContainsString('144.m3u8?expires=', $line);
        $this->assertStringContainsString('signature=', $line);
        $this->mockObject("#EXTM3U\n144-00000.ts\n");
        $this->get($line)->assertOk();
    }
}

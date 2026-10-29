<?php

namespace Tests\Unit;

use App\Storage\R2MediaAdapter;
use League\Flysystem\{Config, Filesystem};
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class R2MediaAdapterTest extends TestCase
{
    private string $directory;
    private LocalFilesystemAdapter $local;
    private LocalFilesystemAdapter $remote;
    private Filesystem $disk;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/r2-test-'.bin2hex(random_bytes(8));
        $this->local = new LocalFilesystemAdapter($this->directory.'/local');
        $this->remote = new LocalFilesystemAdapter($this->directory.'/remote');
        $this->disk = new Filesystem(new R2MediaAdapter($this->local, $this->remote));
    }

    protected function tearDown(): void
    {
        $this->local->deleteDirectory('');
        $this->remote->deleteDirectory('');
        @rmdir($this->directory.'/local'); @rmdir($this->directory.'/remote'); @rmdir($this->directory);
    }

    public function test_new_media_goes_to_remote_but_existing_local_media_wins(): void
    {
        $old = 'listings/images/1/old.jpg'; $new = 'listings/images/1/new.jpg';
        $this->local->write($old, 'legacy', new Config);
        $this->remote->write($old, 'shadow', new Config);
        $this->disk->write($new, 'new');
        $this->assertSame('legacy', $this->disk->read($old));
        $this->assertFalse($this->local->fileExists($new));
        $this->assertSame('new', $this->remote->read($new));
        $this->disk->write($old, 'updated');
        $this->assertSame('updated', $this->local->read($old));
        $this->assertSame('shadow', $this->remote->read($old));
    }

    public function test_private_and_unrelated_files_never_go_to_remote(): void
    {
        foreach (['contracts/private.pdf', 'documents/test.pdf', 'logos/test.png', 'posts/test.html'] as $path) {
            $this->disk->write($path, 'private');
            $this->assertTrue($this->local->fileExists($path));
            $this->assertFalse($this->remote->fileExists($path));
            $this->assertFalse(R2MediaAdapter::eligible($path));
        }
        $this->assertFalse(R2MediaAdapter::eligible('posts/../private.jpg'));
    }

    public function test_remote_stream_read_list_copy_and_delete(): void
    {
        $path = 'news-video/streams/1/test/144-00000.ts';
        $stream = fopen('php://temp', 'w+'); fwrite($stream, 'video'); rewind($stream);
        $this->disk->writeStream($path, $stream); fclose($stream);
        $this->assertSame(5, $this->disk->fileSize($path));
        $this->assertContains($path, $this->disk->listContents('news-video/streams', true)->map(fn ($a) => $a->path())->toArray());
        $this->disk->copy($path, 'news-video/streams/1/test/144-00001.ts');
        $this->disk->delete($path);
        $this->assertFalse($this->remote->fileExists($path));
        $this->assertTrue($this->remote->fileExists('news-video/streams/1/test/144-00001.ts'));
    }

    public function test_pausing_new_writes_keeps_existing_remote_reads(): void
    {
        $this->remote->write('posts/old.jpg', 'remote', new Config);
        $paused = new Filesystem(new R2MediaAdapter($this->local, $this->remote, false));
        $paused->write('posts/new.jpg', 'local');
        $this->assertSame('remote', $paused->read('posts/old.jpg'));
        $this->assertTrue($this->local->fileExists('posts/new.jpg'));
        $this->assertFalse($this->remote->fileExists('posts/new.jpg'));
    }

    public function test_failed_remote_upload_never_creates_a_local_file_or_exposes_sdk_details(): void
    {
        $failing = new class($this->directory.'/failing') extends LocalFilesystemAdapter {
            public function write(string $path, string $contents, Config $config): void
            {
                throw new \RuntimeException('fake-secret-and-signed-url');
            }
        };
        $disk = new Filesystem(new R2MediaAdapter($this->local, $failing));
        try {
            $disk->write('posts/failed.jpg', 'test');
            $this->fail('Upload should fail');
        } catch (\League\Flysystem\UnableToWriteFile $error) {
            $this->assertStringNotContainsString('fake-secret', $error->getMessage());
            $this->assertNull($error->getPrevious());
        }
        $this->assertFalse($this->local->fileExists('posts/failed.jpg'));
        $failing->deleteDirectory(''); @rmdir($this->directory.'/failing');
    }
}

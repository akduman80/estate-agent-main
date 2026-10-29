<?php

namespace Tests\Unit;

use App\Services\R2Connection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class R2ConnectionTest extends TestCase
{
    private function disk(): array
    {
        return ['key' => 'test-key', 'secret' => 'test-secret', 'bucket' => 'test-media',
            'endpoint' => 'https://'.str_repeat('a', 32).'.r2.cloudflarestorage.com',
            'url' => 'https://media.zydemlak.com'];
    }

    public function test_valid_public_connection_and_scoped_client(): void
    {
        $disk = $this->disk();
        R2Connection::validate($disk, str_repeat('a', 32));
        $client = R2Connection::client($disk);
        $this->assertSame('auto', $client->getRegion());
        $this->assertSame($disk['endpoint'], (string) $client->getEndpoint());
    }

    public function test_private_documents_cannot_use_public_bucket(): void
    {
        $this->expectException(InvalidArgumentException::class);
        R2Connection::validate($this->disk(), str_repeat('a', 32), 'test-media');
    }

    public function test_distinct_private_bucket_does_not_require_public_url(): void
    {
        $disk = $this->disk();
        unset($disk['url']);
        $disk['bucket'] = 'test-private';
        R2Connection::validate($disk, str_repeat('a', 32), 'test-media');
        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidSettings')]
    public function test_unsafe_settings_are_rejected(string $key, mixed $value): void
    {
        $disk = $this->disk();
        $disk[$key] = $value;
        $this->expectException(InvalidArgumentException::class);
        R2Connection::validate($disk, str_repeat('a', 32));
    }

    public static function invalidSettings(): array
    {
        return [
            ['secret', ''], ['key', null], ['bucket', '../other'],
            ['endpoint', 'http://'.str_repeat('a', 32).'.r2.cloudflarestorage.com'],
            ['endpoint', 'https://untrusted.example'],
            ['url', 'http://media.zydemlak.com'], ['url', 'https://user@media.zydemlak.com'],
            ['url', 'https://media.zydemlak.com?token=secret'],
            ['url', 'https://media.zydemlak.com/private'],
        ];
    }
}

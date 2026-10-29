<?php

namespace App\Services;

use Aws\S3\S3Client;
use InvalidArgumentException;

/** Connection preparation only; no existing upload flow uses these disks yet. */
class R2Connection
{
    public static function validate(array $disk, string $accountId, ?string $publicBucket = null): void
    {
        foreach (['key', 'secret', 'bucket', 'endpoint'] as $key) {
            if (! is_string($disk[$key] ?? null) || trim($disk[$key]) === '') {
                throw new InvalidArgumentException('R2 bağlantı ayarları eksik.');
            }
        }
        if (! preg_match('/^[a-f0-9]{32}$/D', $accountId)
            || $disk['endpoint'] !== 'https://'.$accountId.'.r2.cloudflarestorage.com') {
            throw new InvalidArgumentException('R2 hesap kimliği veya HTTPS uç noktası geçersiz.');
        }
        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/D', $disk['bucket'])) {
            throw new InvalidArgumentException('R2 kova adı geçersiz.');
        }
        if ($publicBucket !== null && $disk['bucket'] === $publicBucket) {
            throw new InvalidArgumentException('Özel belgeler için ayrı, herkese kapalı bir kova gerekir.');
        }
        if (isset($disk['url'])) {
            $url = parse_url($disk['url']);
            if (! is_array($url) || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
                || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
                || isset($url['port']) || ! in_array($url['path'] ?? '', ['', '/'], true)) {
                throw new InvalidArgumentException('Medya alan adı geçerli bir HTTPS kök adresi olmalıdır.');
            }
        }
    }

    public static function client(array $disk): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => $disk['endpoint'],
            'credentials' => ['key' => $disk['key'], 'secret' => $disk['secret']],
            'use_path_style_endpoint' => true,
            'http' => ['connect_timeout' => 5, 'timeout' => 30],
            'retries' => 2,
        ]);
    }
}

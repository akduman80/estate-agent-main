<?php

$secretDirectory = storage_path('app/private/r2-secrets');
$secretPath = $secretDirectory.'/r2.env';
$secrets = [];
if (is_file($secretPath)) {
    if (is_link($secretPath) || (fileperms($secretPath) & 0007) !== 0 || ! is_readable($secretPath)) {
        throw new RuntimeException('R2 secret dosyasının güvenli erişimi doğrulanamadı.');
    }
    try {
        $secrets = Dotenv\Dotenv::createArrayBacked($secretDirectory, 'r2.env')->load();
    } catch (Throwable) {
        throw new RuntimeException('R2 secret dosyası okunamadı; değerleri güvenli sunucu terminalinden kontrol edin.');
    }
}

return [
    'enabled' => is_file($secretDirectory.'/enabled'),
    'writes_enabled' => is_file($secretDirectory.'/enabled') && ! is_file($secretDirectory.'/pause-writes'),
    'account_id' => env('R2_ACCOUNT_ID', $secrets['R2_ACCOUNT_ID'] ?? env('CLOUDFLARE_ACCOUNT_ID')),
    'key' => env('R2_ACCESS_KEY_ID', $secrets['R2_ACCESS_KEY_ID'] ?? null),
    'secret' => env('R2_SECRET_ACCESS_KEY', $secrets['R2_SECRET_ACCESS_KEY'] ?? null),
    'bucket' => env('R2_BUCKET_NAME', $secrets['R2_BUCKET_NAME'] ?? null),
    'endpoint' => env('R2_ENDPOINT', $secrets['R2_ENDPOINT'] ?? null),
    'public_base_url' => env('R2_PUBLIC_BASE_URL', $secrets['R2_PUBLIC_BASE_URL'] ?? null),
];

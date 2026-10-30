<?php

$directory = storage_path('app/private/whatsapp-secrets');
$secrets = [];
if (is_file($directory.'/evolution.env')) {
    if (is_link($directory.'/evolution.env') || (fileperms($directory.'/evolution.env') & 0007)) {
        throw new RuntimeException('WhatsApp secret dosyası izinleri güvenli değil.');
    }
    $secrets = Dotenv\Dotenv::createArrayBacked($directory, 'evolution.env')->load();
}
return [
    'url' => env('EVOLUTION_API_URL', $secrets['EVOLUTION_API_URL'] ?? 'http://127.0.0.1:8086'),
    'key' => env('EVOLUTION_API_KEY', $secrets['EVOLUTION_API_KEY'] ?? ''),
    'instance' => env('EVOLUTION_INSTANCE_NAME', $secrets['EVOLUTION_INSTANCE_NAME'] ?? 'zydemlak-bildirim'),
    'enabled' => filter_var(env('WHATSAPP_NOTIFICATIONS_ENABLED', $secrets['WHATSAPP_NOTIFICATIONS_ENABLED'] ?? false), FILTER_VALIDATE_BOOLEAN),
    'number' => env('WHATSAPP_ADMIN_NUMBER', $secrets['WHATSAPP_ADMIN_NUMBER'] ?? ''),
    'site_url' => env('SITE_URL', 'https://www.zydemlak.com'),
];

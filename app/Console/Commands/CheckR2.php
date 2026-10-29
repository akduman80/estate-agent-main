<?php

namespace App\Console\Commands;

use App\Services\R2Connection;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CheckR2 extends Command
{
    protected $signature = 'r2:check {--private : Ayrı özel kovayı denetle} {--write-test : Yalnız benzersiz test nesnesini yaz, oku ve sil}';

    protected $description = 'R2 bağlantısını gizli değerleri göstermeden denetle; varsayılan salt okunur';

    public function handle(): int
    {
        $private = (bool) $this->option('private');
        $disk = config('filesystems.disks.'.($private ? 'r2_private' : 'r2_public'), []);
        $client = null;
        $key = null;
        $success = false;
        try {
            R2Connection::validate($disk, (string) config('cloudflare_r2.account_id'),
                $private ? config('filesystems.disks.r2_public.bucket') : null);
            $client = R2Connection::client($disk);
            $client->headBucket(['Bucket' => $disk['bucket']]);
            $this->info('R2 API ve kova erişimi başarılı.');
            if ($this->option('write-test')) {
                $key = 'zydemlak/genel/medya/connection-check/'.Str::uuid().'.png';
                $body = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZ8AAAAASUVORK5CYII=');
                $client->putObject(['Bucket' => $disk['bucket'], 'Key' => $key, 'Body' => $body, 'ContentType' => 'image/png', 'CacheControl' => 'no-store']);
                $read = $client->getObject(['Bucket' => $disk['bucket'], 'Key' => $key]);
                if (! hash_equals(hash('sha256', $body), hash('sha256', (string) $read['Body']))) {
                    throw new \RuntimeException('Test nesnesi doğrulanamadı.');
                }
                $this->info('Gerçek test görseli yükleme/okuma ve SHA-256 doğrulaması başarılı.');
            }
            $success = true;
        } catch (\Throwable $e) {
            // SDK messages may contain signed URLs or request credentials. Never print/log them.
            $this->error($e instanceof \InvalidArgumentException ? $e->getMessage() : 'R2 denetimi başarısız. Ayarları, kova yetkisini ve ağ erişimini kontrol edin.');
        } finally {
            if ($client && $key) {
                try {
                    $client->deleteObject(['Bucket' => $disk['bucket'], 'Key' => $key]);
                    if ($client->doesObjectExistV2($disk['bucket'], $key)) {
                        throw new \RuntimeException('Test nesnesi silinemedi.');
                    }
                    $this->info('Yalnız bu komutun test nesnesi silindi.');
                } catch (\Throwable) {
                    $this->error('Test nesnesi temizliği doğrulanamadı: '.$key);
                    $success = false;
                }
            }
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}

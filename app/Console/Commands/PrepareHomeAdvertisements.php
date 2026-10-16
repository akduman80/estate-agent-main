<?php

namespace App\Console\Commands;

use App\Models\Advertisement;
use App\Support\HomeAdvertisements;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrepareHomeAdvertisements extends Command
{
    protected $signature = 'advertisements:prepare-home {--if-ready : Skip fresh installations without an environment or advertisement table}';

    protected $description = 'Import the existing home carousel once and back up/remove legacy left/right records.';

    public function handle(): int
    {
        if ($this->option('if-ready') && (! is_file(base_path('.env')) || ! Schema::hasTable('advertisements'))) {
            $this->info('Reklam aktarımı atlandı: yeni kurulum henüz hazır değil.');

            return self::SUCCESS;
        }
        $directory = Storage::disk('local')->path('advertisements');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Reklam yedek klasörü oluşturulamadı.');
        }
        $lock = fopen($directory.'/prepare.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('Reklam hazırlığı başka bir işlemde çalışıyor.');

            return self::FAILURE;
        }

        try {
            $marker = $directory.'/home-imported-v1.json';
            $import = ! is_file($marker);
            $created = 0;
            $deleted = DB::transaction(function () use ($directory, $import, &$created): int {
                $legacy = Advertisement::query()->whereIn('placement', ['left', 'right'])->lockForUpdate()->get();
                if ($legacy->isNotEmpty()) {
                    $backup = $directory.'/legacy-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
                    $json = json_encode(['backed_up_at' => now()->toIso8601String(), 'records' => $legacy->toArray()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    if (file_put_contents($backup, $json, LOCK_EX) === false) {
                        throw new RuntimeException('Eski kayıtlar yedeklenemedi; temizlik iptal edildi.');
                    }
                    @chmod($backup, 0600);
                }
                if ($import) {
                    foreach (HomeAdvertisements::defaults() as $default) {
                        // Existing panel replacements, publication dates and disabled states win.
                        if (Advertisement::query()->where('placement', $default['placement'])->exists()) {
                            continue;
                        }
                        $source = public_path('fe-assets/assets/img/advertisements/'.$default['file']);
                        $path = 'site/ads/home-print/'.$default['file'];
                        $disk = Storage::disk('public');
                        if (! $disk->exists($path) && ! $disk->put($path, file_get_contents($source))) {
                            throw new RuntimeException('Reklam görseli depolamaya aktarılamadı.');
                        }
                        Advertisement::create([
                            'placement' => $default['placement'], 'label' => $default['label'],
                            'image_path' => $path, 'is_active' => true,
                            'rotation_interval_seconds' => HomeAdvertisements::DISPLAY_SECONDS,
                            'target_url' => 'https://wa.me/905303607769?text='.rawurlencode($default['message']),
                        ]);
                        $created++;
                    }
                }

                return Advertisement::query()->whereIn('id', $legacy->modelKeys())->whereIn('placement', ['left', 'right'])->delete();
            });
            if ($import && file_put_contents($marker, json_encode(['imported_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Aktarım işareti kaydedilemedi.');
            }
            $this->info("Ana sayfa reklamları hazır. Yeni kayıt: {$created}; yedeklenen ve temizlenen eski kayıt: {$deleted}. Görsel dosyaları silinmedi.");

            return self::SUCCESS;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

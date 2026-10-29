<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneNewsVideo extends Command
{
    protected $signature = 'news-video:prune {--delete : Gerçekten sil; varsayılan yalnız listeleme} {--older-than=48 : En az 24 saat}';

    protected $description = 'Hiçbir habere bağlı olmayan eski video dosyalarını güvenle listele veya temizle';

    public function handle(): int
    {
        $hours = max(24, (int) $this->option('older-than'));
        $cutoff = now()->subHours($hours)->timestamp;
        $references = [];
        foreach (Post::query()->select(['id', 'news_media'])->cursor() as $post) {
            $m = $post->news_media ?? [];
            foreach (['source', 'captions', 'manifest', 'poster'] as $key) {
                if (empty($m[$key])) {
                    continue;
                }
                $disk = $key === 'source' ? 'local' : ($m['disk'] ?? config('news_video.disk'));
                $references[$disk][] = $key === 'manifest' || $key === 'poster' ? dirname($m[$key]).'/' : $m[$key];
            }
        }
        $count = 0;
        // Other disks may still contain older generations. Never scan unrelated directories.
        foreach (array_unique(['local', config('news_video.disk'), ...array_keys($references)]) as $diskName) {
            $disk = Storage::disk($diskName);
            foreach (['sources', 'streams', 'captions', 'work'] as $area) {
                foreach ($disk->allFiles('news-video/'.$area) as $file) {
                    if ($disk->lastModified($file) >= $cutoff || collect($references[$diskName] ?? [])->contains(fn ($ref) => str_ends_with($ref, '/') ? str_starts_with($file, $ref) : $file === $ref)) {
                        continue;
                    }
                    $this->line($diskName.':'.$file);
                    if ($this->option('delete') && ! $disk->delete($file)) {
                        $this->error('Dosya silinemedi.');

                        return self::FAILURE;
                    }
                    $count++;
                }
            }
        }
        $this->info($count.' sahipsiz dosya; '.($this->option('delete') ? 'temizlendi.' : 'yalnız listelendi.'));

        return self::SUCCESS;
    }
}

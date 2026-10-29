<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class NewsVideo
{
    public static function uploadLimitMb(): int
    {
        $bytes = static function (string $value): int {
            $value = trim($value);

            return (int) ((float) $value * match (strtolower(substr($value, -1))) {
                'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1
            });
        };
        $limits = [config('news_video.max_upload_mb') * 1024 * 1024];
        foreach (['upload_max_filesize', 'post_max_size'] as $name) {
            $limit = $bytes((string) ini_get($name));
            if ($limit > 0) {
                $limits[] = $limit;
            }
        }

        return max(1, (int) floor(min($limits) / (1024 * 1024)));
    }

    public static function youtubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $id = $path === '/watch' ? ($query['v'] ?? '') : (preg_match('~^/(?:embed|shorts)/([\w-]{11})/?$~', $path, $m) ? $m[1] : '');
        } else {
            return null;
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : null;
    }

    public function probe(string $path): array
    {
        $process = new Process([config('news_video.ffprobe'), '-v', 'error', '-protocol_whitelist', 'file,pipe', '-show_streams', '-show_format', '-of', 'json', $path]);
        $process->setTimeout(20)->mustRun();
        $data = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $stream = collect($data['streams'] ?? [])->first(fn ($s) => ($s['codec_type'] ?? '') === 'video' && ! ($s['disposition']['attached_pic'] ?? false));
        $duration = (float) ($data['format']['duration'] ?? 0);
        $width = (int) ($stream['width'] ?? 0);
        $height = (int) ($stream['height'] ?? 0);
        if (! $stream || $width < 2 || $height < 2 || ! is_finite($duration) || $duration <= 0) {
            throw new \RuntimeException('Video akışı veya süresi okunamadı.');
        }
        if ($duration > config('news_video.max_duration') || max($width, $height) > config('news_video.max_resolution') * 16 / 9) {
            throw new \RuntimeException('Video süre veya çözünürlük sınırını aşıyor.');
        }
        $rotation = (int) ($stream['tags']['rotate'] ?? 0);
        foreach ($stream['side_data_list'] ?? [] as $side) {
            $rotation = (int) ($side['rotation'] ?? $rotation);
        }
        if (abs($rotation) % 180 === 90) {
            [$width, $height] = [$height, $width];
        }

        return compact('width', 'height', 'duration');
    }

    public static function profiles(int $width, int $height): array
    {
        return array_filter(config('news_video.profiles'), fn ($bitrate, $quality) => $quality <= min($width, $height)
            && $quality <= config('news_video.max_output_resolution')
            && ($quality !== 4320 || config('news_video.enable_8k')), ARRAY_FILTER_USE_BOTH);
    }

    public static function prepare(Post $post): void
    {
        $old = $post->getOriginal('news_media') ?? [];
        $media = array_merge($old, $post->news_media ?? []);
        $url = $media['youtube_url'] ?? null;
        if ($url && ! self::youtubeId($url)) {
            throw ValidationException::withMessages(['news_media.youtube_url' => 'Geçerli bir HTTPS YouTube bağlantısı girin.']);
        }
        if (($media['source'] ?? null) !== ($old['source'] ?? null)) {
            // Generated state is always reset on replace/remove; never trust form metadata.
            foreach (['manifest', 'poster', 'qualities', 'duration', 'width', 'height', 'error', 'disk'] as $key) {
                unset($media[$key]);
            }
            $media['generation'] = (string) Str::uuid();
            $media['status'] = empty($media['source']) ? null : 'queued';
        }
        $post->news_media = $media ?: null;
    }

    public static function sourcePath(string $path): string
    {
        if (! preg_match('~^news-video/sources/[a-f0-9-]{36}\.(mp4|mov|webm|mkv|m4v)$~D', $path)) {
            throw new \RuntimeException('Geçersiz video dosyası.');
        }

        return Storage::disk('local')->path($path);
    }

    public static function outputUrl(Post $post, string $key): ?string
    {
        $media = $post->news_media ?? [];

        return ($media['status'] ?? '') === 'ready' && ! empty($media[$key])
            ? Storage::disk($media['disk'] ?? config('news_video.disk'))->url($media[$key]) : null;
    }

    public function process(Post $post, string $generation): void
    {
        $media = $post->news_media;
        $source = self::sourcePath($media['source']);
        if (filesize($source) > config('news_video.max_upload_mb') * 1024 * 1024) {
            throw new \RuntimeException('Video dosyası boyut sınırını aşıyor.');
        }
        $meta = $this->probe($source);
        $profiles = self::profiles($meta['width'], $meta['height']);
        if (! $profiles) {
            throw new \RuntimeException('En az 144p kaynak video gerekiyor.');
        }
        $run = (string) Str::uuid();
        $directory = storage_path('app/private/news-video/work/'.$run);
        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Geçici depolama oluşturulamadı.');
        }
        $prefix = 'news-video/streams/'.$post->getKey().'/'.$generation.'/'.$run;
        $diskName = config('news_video.disk');
        $disk = Storage::disk($diskName);
        $uploaded = [];
        $published = false;
        try {
            $master = "#EXTM3U\n#EXT-X-VERSION:3\n";
            foreach ($profiles as $quality => $bitrate) {
                // Even dimensions, original aspect ratio, no upscale. FFmpeg autorotates.
                $scale = $meta['width'] >= $meta['height'] ? 'scale=-2:'.$quality : 'scale='.$quality.':-2';
                $cmd = [config('news_video.ffmpeg'), '-nostdin', '-hide_banner', '-loglevel', 'error', '-y', '-protocol_whitelist', 'file,pipe', '-i', $source,
                    '-map', '0:v:0', '-map', '0:a:0?', '-sn', '-dn', '-vf', $scale.',setsar=1', '-c:v', 'libx264', '-threads', '1', '-preset', 'veryfast', '-pix_fmt', 'yuv420p',
                    '-b:v', $bitrate.'k', '-maxrate', (int) ($bitrate * 1.1).'k', '-bufsize', ($bitrate * 2).'k', '-force_key_frames', 'expr:gte(t,n_forced*4)',
                    '-c:a', 'aac', '-b:a', '96k', '-ac', '2', '-f', 'hls', '-hls_time', '4', '-hls_playlist_type', 'vod', '-hls_segment_filename', $directory.'/'.$quality.'-%05d.ts', $directory.'/'.$quality.'.m3u8'];
                (new Process($cmd))->setTimeout(config('news_video.timeout'))->mustRun();
                $width = $meta['width'] >= $meta['height'] ? (int) (round(($quality * $meta['width'] / $meta['height']) / 2) * 2) : $quality;
                $height = $meta['width'] >= $meta['height'] ? $quality : (int) (round(($quality * $meta['height'] / $meta['width']) / 2) * 2);
                $master .= '#EXT-X-STREAM-INF:BANDWIDTH='.(int) (($bitrate * 1.1 + 96) * 1000).',RESOLUTION='.$width.'x'.$height."\n".$quality.".m3u8\n";
            }
            (new Process([config('news_video.ffmpeg'), '-nostdin', '-loglevel', 'error', '-y', '-protocol_whitelist', 'file,pipe', '-ss', (string) min(1, $meta['duration'] / 2), '-i', $source, '-frames:v', '1', '-vf', 'scale=960:960:force_original_aspect_ratio=decrease', '-threads', '1', $directory.'/poster.jpg']))->setTimeout(60)->mustRun();
            file_put_contents($directory.'/master.m3u8', $master);
            // Publish complete variants first, master last. An incomplete stream is never visible.
            $files = glob($directory.'/*');
            usort($files, fn ($a, $b) => (basename($a) === 'master.m3u8') <=> (basename($b) === 'master.m3u8'));
            foreach ($files as $file) {
                $path = $prefix.'/'.basename($file);
                $stream = fopen($file, 'rb');
                try {
                    $ok = $disk->put($path, $stream, ['visibility' => 'public', 'ContentType' => str_ends_with($path, '.m3u8') ? 'application/vnd.apple.mpegurl' : (str_ends_with($path, '.ts') ? 'video/mp2t' : 'image/jpeg'), 'CacheControl' => 'public, max-age=31536000, immutable']);
                } finally {
                    fclose($stream);
                }
                if (! $ok) {
                    throw new \RuntimeException('Yayın depolamasına yazılamadı.');
                }
                $uploaded[] = $path;
            }
            DB::transaction(function () use ($post, $generation, $prefix, $profiles, $meta, $diskName, &$published): void {
                $fresh = Post::query()->lockForUpdate()->find($post->getKey());
                if (! $fresh || ($fresh->news_media['generation'] ?? '') !== $generation) {
                    return;
                }
                $fresh->news_media = array_merge($fresh->news_media, $meta, ['manifest' => $prefix.'/master.m3u8', 'poster' => $prefix.'/poster.jpg', 'qualities' => array_keys($profiles), 'disk' => $diskName, 'status' => 'ready', 'error' => null]);
                $fresh->saveQuietly();
                $published = true;
            });
        } finally {
            if (! $published && $uploaded) {
                $disk->delete($uploaded);
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}

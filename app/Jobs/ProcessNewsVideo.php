<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\NewsVideo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class ProcessNewsVideo implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 0; // Slot contention must not exhaust conversion retries.

    public int $maxExceptions = 2;

    public bool $failOnTimeout = true;

    public function __construct(public int $postId, public string $generation)
    {
        $this->onConnection('news_video')->onQueue('news-video')->afterCommit();
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('news-video-'.$this->postId))->releaseAfter(30)->expireAfter(3660)];
    }

    public function handle(NewsVideo $service): void
    {
        $post = Post::find($this->postId);
        if (! $post || ($post->news_media['generation'] ?? '') !== $this->generation || ($post->news_media['status'] ?? '') === 'ready') {
            return;
        }
        if (! config('news_video.enabled')) {
            $this->fail(new \RuntimeException('Video işleme henüz etkinleştirilmedi.'));

            return;
        }
        $slot = null;
        for ($i = 0; $i < config('news_video.max_concurrent_jobs'); $i++) {
            $candidate = Cache::lock('news-video-slot-'.$i, 3660);
            if ($candidate->get()) {
                $slot = $candidate;
                break;
            }
        }
        if (! $slot) {
            $this->release(30);

            return;
        }
        try {
            $post = DB::transaction(function (): ?Post {
                $fresh = Post::query()->lockForUpdate()->find($this->postId);
                if (! $fresh || ($fresh->news_media['generation'] ?? '') !== $this->generation || ($fresh->news_media['status'] ?? '') === 'ready') {
                    return null;
                }
                $fresh->news_media = array_merge($fresh->news_media, ['status' => 'processing', 'error' => null]);
                $fresh->saveQuietly();

                return $fresh;
            });
            if (! $post) {
                return;
            }
            $service->process($post, $this->generation);
        } finally {
            $slot->release();
        }
    }

    public function failed(?\Throwable $exception): void
    {
        report($exception ?? new \RuntimeException('News video failed'));
        DB::transaction(function () use ($exception): void {
            $post = Post::query()->lockForUpdate()->find($this->postId);
            if (! $post || ($post->news_media['generation'] ?? '') !== $this->generation || ($post->news_media['status'] ?? '') === 'ready') {
                return;
            }
            $known = ['Video akışı veya süresi okunamadı.', 'Video süre veya çözünürlük sınırını aşıyor.', 'En az 144p kaynak video gerekiyor.', 'Yayın depolamasına yazılamadı.', 'Video dosyası boyut sınırını aşıyor.', 'Geçersiz video dosyası.'];
            $reason = in_array($exception?->getMessage(), $known, true) ? $exception->getMessage()
                : ($exception instanceof ProcessTimedOutException ? 'Video işleme zaman sınırını aştı.'
                : ($exception instanceof ProcessFailedException ? 'FFmpeg/FFprobe video dosyasını işleyemedi. Dosyayı ve sunucu codec desteğini kontrol edin.'
                : 'İşlem tamamlanamadı. Yönetici FFmpeg, depolama ve kuyruk kayıtlarını kontrol etmelidir.'));
            $post->news_media = array_merge($post->news_media, ['status' => 'failed', 'error' => config('news_video.enabled')
                ? $reason.' Güvenli yeniden deneme düğmesini kullanabilirsiniz.'
                : 'Video işleme servisi henüz etkinleştirilmedi. Yönetici kuyruk ve FFmpeg kurulumunu doğrulamalıdır.']);
            $post->saveQuietly();
        });
    }
}

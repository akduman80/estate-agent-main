<?php

namespace App\Models;

use App\Jobs\ProcessNewsVideo;
use App\Services\NewsVideo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $guarded = [];

    protected $casts = [
        'news_media' => 'array',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Post $post) => NewsVideo::prepare($post));
        static::saved(function (Post $post): void {
            if (! $post->wasRecentlyCreated && ! $post->wasChanged('news_media')) {
                return;
            }
            $old = $post->getOriginal('news_media') ?? [];
            // Enqueue only a new source generation; edits to title/captions do not transcode.
            if (($post->news_media['status'] ?? '') === 'queued' && ($post->news_media['generation'] ?? null) !== ($old['generation'] ?? null)) {
                ProcessNewsVideo::dispatch($post->getKey(), $post->news_media['generation']);
            }
        });
    }

    public function getNewsVideoUrlAttribute(): ?string
    {
        return NewsVideo::outputUrl($this, 'manifest');
    }

    public function getNewsPosterUrlAttribute(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image)
            : NewsVideo::outputUrl($this, 'poster');
    }

    public function getNewsVideoPosterUrlAttribute(): ?string
    {
        return NewsVideo::outputUrl($this, 'poster');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

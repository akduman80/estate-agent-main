<?php

namespace App\Filament\Forms;

use App\Enums\UserRole;
use App\Jobs\ProcessNewsVideo;
use App\Models\Post;
use App\Rules\NewsMediaFile;
use App\Services\NewsVideo;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class NewsMediaFields
{
    public static function sanitize(array $data, ?Post $record = null): array
    {
        $record = $record?->fresh();
        $generation = $data['_news_media_generation'] ?? null;
        $originalSource = $data['_news_media_source'] ?? null;
        unset($data['_news_media_generation'], $data['_news_media_source']);
        if ($record && array_key_exists('source', $data['news_media'] ?? [])
            && $generation !== ($record->news_media['generation'] ?? null)
            && ($data['news_media']['source'] ?? null) !== ($record->news_media['source'] ?? null)) {
            if (($data['news_media']['source'] ?? null) === $originalSource) {
                // An unchanged old form cannot overwrite newer media.
                unset($data['news_media']['source']);
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.news_media.source' => 'Video başka bir oturumda değişti. Sayfayı yenileyip tekrar deneyin.',
                ]);
            }
        }
        foreach (['cover_image' => '_news_cover_image', 'captions' => '_news_captions'] as $key => $snapshotKey) {
            $hasSnapshot = array_key_exists($snapshotKey, $data);
            $original = $data[$snapshotKey] ?? null;
            unset($data[$snapshotKey]);
            $current = $key === 'cover_image' ? $record?->cover_image : ($record?->news_media[$key] ?? null);
            $submitted = $key === 'cover_image' ? ($data[$key] ?? null) : ($data['news_media'][$key] ?? null);
            if ($record && $hasSnapshot && $original !== $current && $submitted !== $current) {
                if ($submitted !== $original) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        $key === 'cover_image' ? 'data.cover_image' : 'data.news_media.captions' => 'Medya başka bir oturumda değişti. Sayfayı yenileyip tekrar deneyin.',
                    ]);
                }
                if ($key === 'cover_image') {
                    $data[$key] = $current;
                } else {
                    unset($data['news_media'][$key]);
                }
            }
        }
        $media = array_intersect_key($data['news_media'] ?? [], array_flip(['title', 'description', 'source', 'captions', 'captions_language', 'youtube_url']));
        if (! self::admin()) {
            unset($media['source'], $media['captions']);
        }
        $data['news_media'] = array_merge($record?->news_media ?? [], $media);

        return $data;
    }

    public static function admin(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR && auth()->user()?->canAccessPanel(Filament::getCurrentPanel());
    }

    public static function status(?string $state): string
    {
        return match ($state) {
            'queued' => 'Video yüklendi, işlem sırasında.',
            'processing' => 'Video işleniyor.',
            'ready' => 'Video yayına hazır.',
            'failed' => 'İşlem başarısız.',
            default => 'Video yok',
        };
    }

    public static function schema(): array
    {
        return [
            \Filament\Forms\Components\Hidden::make('_news_media_generation')
                ->afterStateHydrated(fn ($component, ?Post $record) => $component->state($record?->news_media['generation'] ?? null)),
            \Filament\Forms\Components\Hidden::make('_news_media_source')
                ->afterStateHydrated(fn ($component, ?Post $record) => $component->state($record?->news_media['source'] ?? null)),
            \Filament\Forms\Components\Hidden::make('_news_cover_image')
                ->afterStateHydrated(fn ($component, ?Post $record) => $component->state($record?->cover_image)),
            \Filament\Forms\Components\Hidden::make('_news_captions')
                ->afterStateHydrated(fn ($component, ?Post $record) => $component->state($record?->news_media['captions'] ?? null)),
            TextInput::make('news_media.title')->label('Video Başlığı')->maxLength(255),
            Textarea::make('news_media.description')->label('Video Açıklaması')->maxLength(2000),
            NewsMediaUpload::make('news_media.source')->label('+ Video Yükle')
                ->uploadingMessage('Video yükleniyor...')->uploadProgressIndicatorPosition('center')
                ->validationMessages(['mimetypes' => 'MP4, MOV, WebM, MKV veya M4V video seçin.', 'max' => 'Video izin verilen dosya boyutunu aşıyor.'])
                ->disk('local')->visibility('private')->directory('news-video/sources')
                ->acceptedFileTypes(config('news_video.mime_types'))->maxSize(NewsVideo::uploadLimitMb() * 1024)
                ->previewable(false)->openable(false)->downloadable(false)->fetchFileInformation(false)
                ->rules(fn (?Post $record) => [new NewsMediaFile($record?->news_media['source'] ?? null)])
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                    abort_unless(self::admin(), 403);
                    $path = 'news-video/sources/'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                    $stream = fopen($file->getRealPath(), 'rb');
                    try {
                        $ok = Storage::disk('local')->put($path, $stream);
                    } finally {
                        fclose($stream);
                    }
                    if (! $ok) {
                        throw new \RuntimeException('Video kaydedilemedi.');
                    }

                    return $path;
                })
                ->disabled(fn () => ! self::admin())->dehydrated(fn () => self::admin())
                ->helperText('MP4, MOV, WebM, MKV, M4V; mevcut PHP sınırıyla en fazla '.NewsVideo::uploadLimitMb().' MB / '.config('news_video.max_duration').' saniye. İlerleme yükleme sırasında gösterilir. Kaydettiğinizde arka planda işlenir.'),
            TextInput::make('news_media.youtube_url')->label('YouTube Video Bağlantısı Ekle')->url()->maxLength(500)
                ->rules([fn () => function ($attribute, $value, $fail): void {
                    if ($value && ! NewsVideo::youtubeId($value)) {
                        $fail('Geçerli bir HTTPS YouTube video bağlantısı girin.');
                    }
                }]),
            NewsMediaUpload::make('news_media.captions')->mediaKey('captions')->label('Türkçe Altyazı (.vtt)')
                ->uploadingMessage('Altyazı yükleniyor...')
                ->disk(config('news_video.disk'))->directory('news-video/captions')->maxSize(1024)
                ->previewable(false)->fetchFileInformation(false)
                ->rules(fn (?Post $record) => [new NewsMediaFile($record?->news_media['captions'] ?? null, 'captions')])
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                    abort_unless(self::admin(), 403);
                    $path = 'news-video/captions/'.Str::uuid().'.vtt';
                    if (! Storage::disk(config('news_video.disk'))->put($path, file_get_contents($file->getRealPath()), ['visibility' => 'public', 'ContentType' => 'text/vtt'])) {
                        throw new \RuntimeException('Altyazı kaydedilemedi.');
                    }

                    return $path;
                })->disabled(fn () => ! self::admin())->dehydrated(fn () => self::admin()),
            Select::make('news_media.captions_language')->label('Altyazı Dili')->options(config('news_video.caption_languages'))->default('tr')->in(array_keys(config('news_video.caption_languages'))),
            Placeholder::make('video_status')->label('Video İşleme Durumu')
                ->extraAttributes(['wire:poll.10s' => '$refresh'])
                ->content(function (?Post $record): string {
                    $record = $record?->fresh();

                    return self::status($record?->news_media['status'] ?? null).' '.($record?->news_media['error'] ?? '').(! config('news_video.enabled') ? ' Arka plan video servisi henüz etkinleştirilmedi.' : '');
                }),
            Placeholder::make('video_preview')->label('Video Önizleme')
                ->content(fn (?Post $record) => new HtmlString(view('components.news-video', ['post' => $record?->fresh() ?? new Post, 'loadAssets' => true])->render())),
            Actions::make([Actions\Action::make('retryVideo')->label('Video İşlemini Yeniden Dene')
                ->visible(fn (?Post $record) => self::admin() && ($record?->fresh()?->news_media['status'] ?? '') === 'failed')
                ->requiresConfirmation()->action(function (Post $record): void {
                    abort_unless(self::admin(), 403);
                    DB::transaction(function () use ($record): void {
                        $fresh = Post::query()->lockForUpdate()->findOrFail($record->id);
                        if (($fresh->news_media['status'] ?? '') !== 'failed') {
                            return;
                        }
                        $fresh->news_media = array_merge($fresh->news_media, ['status' => 'queued', 'error' => null, 'generation' => (string) Str::uuid()]);
                        $fresh->saveQuietly();
                        ProcessNewsVideo::dispatch($fresh->id, $fresh->news_media['generation']);
                    });
                    Notification::make()->title('Video yeniden işlem sırasına alındı.')->success()->send();
                })]),
        ];
    }
}

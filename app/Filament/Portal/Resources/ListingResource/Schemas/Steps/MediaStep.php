<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use App\Filament\Forms\Components\ListingMediaUpload;
use App\Models\Listing;
use App\Rules\ListingVideo;
use App\Services\ListingFormDraftService;
use App\Services\ListingMediaService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Görsel ve Video')
            ->icon('heroicon-o-photo')
            ->description('Fotoğraflar ve tanıtım videosu')
            ->schema([
                View::make('filament.listing-media-tools'),
                ListingMediaUpload::make('uploaded_images')
                    ->label('İlan Fotoğrafları')
                    ->multiple()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->saveUploadedFileUsing(fn (UploadedFile $file): string => app(ListingFormDraftService::class)->storeImage($file))
                    ->disk('public')
                    ->directory(fn (): string => 'listings/images/'.auth()->id())
                    ->maxFiles(config('listings.photos.max_files'))
                    ->maxSize(config('listings.photos.max_size_kb'))
                    ->maxParallelUploads(2)
                    ->helperText(fn (): string => 'En fazla '.config('listings.photos.max_files').' fotoğraf, fotoğraf başına '.(config('listings.photos.max_size_kb') / 1024).' MB. İlk fotoğraf kapak görselidir.')
                    ->extraAlpineAttributes([
                        'x-init' => '$el.addEventListener("FilePond:init", () => window.zydListingMedia.setup($data.pond, $data))',
                    ])
                    ->getUploadedFileUsing(fn (string $file): ?array => static::fileInformation($file))
                    ->fetchFileInformation(false)
                    ->reorderable()
                    ->dehydrated(false)
                    ->loadStateFromRelationshipsUsing(function (FileUpload $component, Listing $record): void {
                        $component->state($record->images()->pluck('image_path')->all());
                    })
                    ->saveRelationshipsUsing(function (Listing $record, $state): void {
                        if (is_array($state)) {
                            app(ListingMediaService::class)->syncImages($record, $state);
                        }
                    })
                    ->columnSpanFull(),
                ListingMediaUpload::make('video_path')
                    ->label('Video dosyası')
                    ->maxFiles(1)
                    ->disk('public')
                    ->directory(fn (): string => 'listings/videos/'.auth()->id())
                    ->acceptedFileTypes(config('listings.video.mime_types'))
                    ->mimeTypeMap(['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', '3gp' => 'video/3gpp'])
                    ->extraInputAttributes(['accept' => 'video/mp4,video/webm,video/quicktime,video/3gpp,.mp4,.webm,.mov,.3gp'])
                    ->saveUploadedFileUsing(fn (UploadedFile $file): string => app(ListingFormDraftService::class)->storeVideo($file))
                    ->maxSize(config('listings.video.max_size_kb'))
                    ->rules([new ListingVideo])
                    ->getUploadedFileUsing(fn (string $file): ?array => static::fileInformation($file))
                    ->fetchFileInformation(false)
                    ->extraAlpineAttributes([
                        'x-init' => '$el.addEventListener("FilePond:init", () => window.zydListingMedia.setup($data.pond, $data, '.json_encode([
                            'maxSeconds' => config('listings.video.max_duration_seconds'),
                            'maxBytes' => config('listings.video.max_size_kb') * 1024,
                        ]).'))',
                    ])
                    ->helperText(fn (): string => 'En fazla 60 saniye ve '.(config('listings.video.max_size_kb') / 1024).' MB. MP4 (H.264), WebM (VP8/VP9), MOV veya 3GP. MOV/3GP için sunucuda FFmpeg gerekir. YouTube bağlantısını da kullanabilirsiniz.')
                    ->openable()
                    ->dehydrateStateUsing(function ($state, ?Listing $record): ?string {
                        $path = array_values($state ?? [])[0] ?? null;
                        if ($path && $path !== $record?->video_path && (
                            ! str_starts_with($path, 'listings/videos/'.auth()->id().'/')
                            || str_contains($path, '..')
                            || ! Storage::disk('public')->exists($path)
                            || Listing::query()->where('video_path', $path)->exists()
                        )) {
                            throw ValidationException::withMessages(['data.video_path' => 'Video yüklemesi doğrulanamadı. Videoyu tekrar seçin.']);
                        }

                        return $path;
                    }),
                TextInput::make('video_url')
                    ->label('YouTube Video Linki')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->url(),
                TextInput::make('form_details.virtual_tour')
                    ->label('Sanal tur bağlantısı (isteğe bağlı)')
                    ->helperText('HTTPS Matterport veya Immoviewer bağlantısı. Boş bırakarak tursuz devam edebilirsiniz.')
                    ->url()->maxLength(2048),
            ]);
    }

    private static function fileInformation(string $file): ?array
    {
        $disk = Storage::disk('public');
        $exists = $disk->exists($file);

        return [
            'name' => basename($file),
            'size' => $exists ? $disk->size($file) : 0,
            'type' => $exists ? $disk->mimeType($file) : 'application/octet-stream',
            'url' => ListingMediaService::publicUrl($file),
        ];
    }
}

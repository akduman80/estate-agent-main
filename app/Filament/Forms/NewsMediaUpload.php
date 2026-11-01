<?php

namespace App\Filament\Forms;

use App\Rules\NewsMediaFile;
use App\Filament\Forms\Components\PublicMediaUpload as FileUpload;

class NewsMediaUpload extends FileUpload
{
    protected string $mediaKey = 'source';

    protected function setUp(): void
    {
        parent::setUp();
        $this->view('filament.forms.news-media-upload');
        // Private retained files need metadata, never a public fetch URL. With a
        // known MIME and previews off, FilePond restores a metadata-only item.
        $this->getUploadedFileUsing(static function (self $component, string $file): array {
            $record = $component->getRecord();
            if ($file !== ($record?->news_media[$component->mediaKey] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $component->getStatePath() => 'Kayıtlı dosya bilgisi doğrulanamadı. Sayfayı yeniden açın.',
                ]);
            }
            try {
                $size = $component->getDisk()->size($file);
                $type = $component->getDisk()->mimeType($file);
                if (! $size || ! $type) {
                    throw new \RuntimeException('Missing retained media metadata');
                }
            } catch (\Throwable $exception) {
                report($exception);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $component->getStatePath() => 'Kayıtlı dosya bilgisi okunamadı. Yeniden deneyin; mevcut video yayından kaldırılmadı.',
                ]);
            }

            return [
                'name' => $record->news_media[$component->mediaKey.'_name'] ?? ($component->mediaKey === 'source' ? 'Kayıtlı video.' : 'Kayıtlı altyazı.').pathinfo($file, PATHINFO_EXTENSION),
                'size' => $size,
                'type' => $type,
                'url' => 'news-media:'.hash('sha256', $file),
            ];
        });
    }

    public function mediaKey(string $key): static
    {
        $this->mediaKey = $key;

        return $this;
    }

    public function getValidationRules(): array
    {
        // Filament validates newly uploaded files only. Validate retained string paths too.
        return [...parent::getValidationRules(), function (string $attribute, array $value, \Closure $fail): void {
            $existing = $this->getRecord()?->news_media[$this->mediaKey] ?? null;
            foreach ($value as $file) {
                if (is_string($file)) {
                    (new NewsMediaFile($existing, $this->mediaKey === 'captions' ? 'captions' : 'video'))->validate($attribute, $file, $fail);
                }
            }
        }];
    }
}

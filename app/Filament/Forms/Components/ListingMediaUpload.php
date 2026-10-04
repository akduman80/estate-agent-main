<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListingMediaUpload extends FileUpload
{
    public function getValidationRules(): array
    {
        return [...parent::getValidationRules(), function (string $attribute, array $value, Closure $fail): void {
            $record = $this->getRecord();
            $existing = $this->getName() === 'video_path'
                ? array_filter([$record?->video_path])
                : ($record?->exists ? $record->images()->pluck('image_path')->all() : []);

            foreach ($value as $file) {
                if (! $file instanceof TemporaryUploadedFile && ! in_array($file, $existing, true)) {
                    $fail('Dosya bu ilana ait değil. Yeni dosyayı yükleme alanından seçin.');

                    return;
                }
            }
        }];
    }
}

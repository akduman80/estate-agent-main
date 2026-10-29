<?php

namespace App\Filament\Forms\Components;

use App\Models\ListingFormDraft;
use App\Services\ListingFormDraftService;
use Closure;
use App\Filament\Forms\Components\PublicMediaUpload as FileUpload;
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
            $draftId = $this->getLivewire()->draftId ?? null;
            $draft = $draftId ? ListingFormDraft::query()->whereKey($draftId)->where('user_id', auth()->id())->first() : null;

            foreach ($value as $file) {
                if (! $file instanceof TemporaryUploadedFile && ! in_array($file, $existing, true)
                    && ! (is_string($file) && app(ListingFormDraftService::class)->ownsPath($file, $this->getName(), $draft, $record))) {
                    $fail('Dosya bu ilana ait değil. Yeni dosyayı yükleme alanından seçin.');

                    return;
                }
            }
        }];
    }
}

<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Forms\NewsMediaFields;
use App\Filament\Resources\PostResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected static ?string $title = 'Yeni Emlak Haberi Ekle';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Emlak Haberi Kaydedildi';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Emlak Haberini Kaydet');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Kaydet ve Yeni Haber Ekle');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Vazgeç');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = NewsMediaFields::sanitize($data);
        $data['user_id'] = auth()->id();
        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}

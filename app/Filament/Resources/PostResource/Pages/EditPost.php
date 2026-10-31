<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Forms\NewsMediaFields;
use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected static ?string $title = 'Emlak Haberini Düzenle';

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Emlak Haberi Kaydedildi';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Değişiklikleri Kaydet');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Vazgeç');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Media is merged with the current record under the update lock.
        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $fresh = \App\Models\Post::query()->lockForUpdate()->findOrFail($record->getKey());
            $fresh->update(NewsMediaFields::sanitize($data, $fresh));
            $record->setRawAttributes($fresh->getAttributes(), true);
            return $record;
        });
    }

    protected function afterSave(): void
    {
        $this->refreshFormData(['news_media', 'cover_image']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Emlak Haberini Sil')->modalHeading('Emlak Haberini Sil')->modalDescription('Bu emlak haberini silmek istediğinizden emin misiniz?')->modalSubmitActionLabel('Haberi Sil')->modalCancelActionLabel('Vazgeç')->successNotificationTitle('Emlak Haberi Silindi'),
        ];
    }
}

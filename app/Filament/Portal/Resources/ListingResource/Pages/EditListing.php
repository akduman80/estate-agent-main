<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Filament\Portal\Resources\ListingResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->record?->eids_status === 'verified') {
            return Notification::make()
                ->success()
                ->title('İlan kaydedildi')
                ->body('EİDS onayı mevcut olduğu için ilan sitede yayınlanabilir.');
        }

        return Notification::make()
            ->warning()
            ->title('İlan taslakta kalacak')
            ->body('Bu ilan EİDS onayı almadığı için taslaklarda duracaktır ve sitede yayınlanmayacaktır.');
    }
}

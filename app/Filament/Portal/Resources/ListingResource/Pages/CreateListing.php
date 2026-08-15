<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Filament\Portal\Resources\ListingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id() ?? 1; // Giriş yapan kullanıcının ID'si
        $data['slug'] = Str::slug($data['title']) . '-' . rand(1000, 9999);
        $data['is_active'] = false;

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->record?->eids_status === 'verified') {
            return Notification::make()
                ->success()
                ->title('İlan yayına alındı')
                ->body('EİDS onayı alındığı için ilan sitede yayınlanabilir.');
        }

        return Notification::make()
            ->warning()
            ->title('İlan taslakta kalacak')
            ->body('Bu ilan EİDS onayı almadığı için taslaklarda duracaktır ve sitede yayınlanmayacaktır.');
    }
}

<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['title']).'-'.rand(1000, 9999);
        $data['is_active'] = false;
        $data['publication_status'] = 'draft';
        $data['source_type'] = match ($user->role) {
            UserRole::INDIVIDUAL => 'owner',
            UserRole::AGENT => 'real_estate_office',
            default => 'zydemlak',
        };
        $data['business_profile_id'] = $user->role === UserRole::AGENT ? $user->businessProfile?->id : null;
        $data['from_who'] = match ($data['source_type']) {
            'owner' => 'Sahibinden',
            'real_estate_office' => 'Emlak Ofisinden',
            default => 'ZYDEMLAK',
        };

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->warning()
            ->title('İlan taslak olarak kaydedildi')
            ->body('EİDS sorgusunu tamamlayıp ilanı yönetici onayına gönderebilirsiniz.');
    }
}

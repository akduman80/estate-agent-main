<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource;
use App\Models\User;
use App\Services\ListingQuotaService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateListing extends CreateRecord
{
    protected static string $resource = ListingResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->role !== UserRole::SUPERVISOR && app(ListingQuotaService::class)->hasReachedLimit($user)) {
            Notification::make()
                ->danger()
                ->title('Aktif ilan limitinize ulaştınız.')
                ->send();

            $this->redirect(ListingResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authenticatedUser = auth()->user();
        $owner = $authenticatedUser->role === UserRole::SUPERVISOR
            ? User::query()->find($data['user_id'] ?? null)
            : $authenticatedUser;

        $isPlatformOwner = $authenticatedUser->role === UserRole::SUPERVISOR
            && $owner?->id === $authenticatedUser->id;

        if (! $owner || (! $isPlatformOwner && ! in_array($owner->role, [UserRole::INDIVIDUAL, UserRole::AGENT], true))) {
            throw ValidationException::withMessages(['user_id' => 'Geçerli bir bireysel üye veya emlakçı seçmelisiniz.']);
        }

        app(ListingQuotaService::class)->assertAvailable($owner);

        $data['user_id'] = $owner->id;
        $data['slug'] = Str::slug($data['title']).'-'.rand(1000, 9999);
        $data['is_active'] = false;
        $data['publication_status'] = 'draft';
        $data['source_type'] = match ($owner->role) {
            UserRole::INDIVIDUAL => 'owner',
            UserRole::AGENT => 'real_estate_office',
            default => 'zydemlak',
        };
        $data['business_profile_id'] = $owner->role === UserRole::AGENT ? $owner->businessProfile?->id : null;
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

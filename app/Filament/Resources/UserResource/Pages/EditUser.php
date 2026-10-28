<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\SupervisorUserService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record->loadMissing('businessProfile');
        $profile = $user->businessProfile;

        return array_merge($data, [
            'password' => null,
            'identity_number' => null,
            'email_verified' => filled($user->email_verified_at),
            'phone_verified' => filled($user->phone_verified_at),
            'business_office_name' => $profile?->office_name,
            'business_license_number' => $profile?->license_number,
            'business_tax_office' => $profile?->tax_office,
            'business_tax_office_city_code' => $profile?->tax_office_city_code,
            'business_tax_office_code' => $profile?->tax_office_code,
            'business_tax_number' => null,
            'business_phone' => $profile?->business_phone,
            'business_address' => $profile?->address,
            'business_approval_status' => $profile?->approval_status?->value,
            'business_review_note' => $profile?->review_note,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        return app(SupervisorUserService::class)->update($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kullanıcı güncellendi';
    }
}

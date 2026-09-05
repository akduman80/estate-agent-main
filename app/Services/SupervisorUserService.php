<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Rules\TurkishIdentityNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SupervisorUserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $attributes = $this->userAttributes($data);
            $role = $attributes['role'];

            if (blank($attributes['password'] ?? null)) {
                throw ValidationException::withMessages(['password' => 'Şifre alanı zorunludur.']);
            }

            $this->applyIdentityNumber($attributes, $data, $role);

            $user = new User;
            $user->forceFill($attributes)->save();

            if ($role === UserRole::AGENT->value) {
                $user->businessProfile()->create($this->businessAttributes($data));
            }

            return $user->load('businessProfile');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = $this->userAttributes($data, $user);
            $role = $attributes['role'];

            $this->guardLastSupervisor($user, $attributes);
            $this->applyIdentityNumber($attributes, $data, $role, $user);

            if (blank($attributes['password'] ?? null)) {
                unset($attributes['password']);
            }

            $user->forceFill($attributes)->save();

            if ($role === UserRole::AGENT->value) {
                $profile = $user->businessProfile;
                $businessAttributes = $this->businessAttributes($data, $profile);

                $profile
                    ? $profile->update($businessAttributes)
                    : $user->businessProfile()->create($businessAttributes);
            }

            return $user->refresh()->load('businessProfile');
        });
    }

    private function userAttributes(array $data, ?User $user = null): array
    {
        $roleValue = $data['role'] instanceof UserRole ? $data['role']->value : (string) ($data['role'] ?? '');
        $statusValue = $data['account_status'] instanceof AccountStatus ? $data['account_status']->value : (string) ($data['account_status'] ?? '');
        $role = UserRole::tryFrom($roleValue);
        $status = AccountStatus::tryFrom($statusValue);

        if (! $role) {
            throw ValidationException::withMessages(['role' => 'Geçerli bir üyelik türü seçmelisiniz.']);
        }

        if (! $status) {
            throw ValidationException::withMessages(['account_status' => 'Geçerli bir hesap durumu seçmelisiniz.']);
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            throw ValidationException::withMessages(['first_name' => 'Ad ve soyad alanları zorunludur.']);
        }

        $emailVerified = (bool) ($data['email_verified'] ?? false);
        $phoneVerified = (bool) ($data['phone_verified'] ?? false);
        $isMember = in_array($role, [UserRole::INDIVIDUAL, UserRole::AGENT], true);
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));

        Validator::make([
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'] ?? null,
        ], [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->getKey()),
            ],
            'phone' => $isMember ? ['required', 'string', 'max:24'] : ['nullable', 'string', 'max:24'],
            'password' => $user
                ? ['nullable', Password::min(10)->letters()->mixedCase()->numbers()]
                : ['required', Password::min(10)->letters()->mixedCase()->numbers()],
        ])->validate();

        if ($isMember && ! array_key_exists((string) ($data['city'] ?? ''), TurkeyAddressService::getCities())) {
            throw ValidationException::withMessages(['city' => 'Geçerli bir il seçmelisiniz.']);
        }

        if ($isMember && ! array_key_exists((string) ($data['district'] ?? ''), TurkeyAddressService::getDistricts($data['city'] ?? null))) {
            throw ValidationException::withMessages(['district' => 'Seçilen ile bağlı geçerli bir ilçe seçmelisiniz.']);
        }

        if ($isMember && ! array_key_exists((string) ($data['neighborhood'] ?? ''), TurkeyAddressService::getNeighborhoods($data['city'] ?? null, $data['district'] ?? null))) {
            throw ValidationException::withMessages(['neighborhood' => 'Seçilen ilçeye bağlı geçerli bir mahalle veya köy seçmelisiniz.']);
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'email' => $email,
            'phone' => $isMember ? ($data['phone'] ?? null) : ($data['phone'] ?? $user?->phone),
            'role' => $role->value,
            'account_status' => $status->value,
            'password' => $data['password'] ?? null,
            'city' => $isMember ? ($data['city'] ?? null) : null,
            'district' => $isMember ? ($data['district'] ?? null) : null,
            'neighborhood' => $isMember ? ($data['neighborhood'] ?? null) : null,
            'listing_limit_override' => $isMember && filled($data['listing_limit_override'] ?? null)
                ? (int) $data['listing_limit_override']
                : null,
            'email_verified_at' => $emailVerified ? ($user?->email_verified_at ?? now()) : null,
            'phone_verified_at' => $isMember && $phoneVerified ? ($user?->phone_verified_at ?? now()) : null,
            'two_factor_enabled' => $isMember && (bool) ($data['two_factor_enabled'] ?? false),
        ];
    }

    private function applyIdentityNumber(array &$attributes, array $data, string $role, ?User $user = null): void
    {
        if ($role !== UserRole::INDIVIDUAL->value) {
            return;
        }

        $identityNumber = preg_replace('/\D/', '', (string) ($data['identity_number'] ?? ''));

        if ($identityNumber === '') {
            if ($user?->identity_number_hash) {
                return;
            }

            throw ValidationException::withMessages(['identity_number' => 'T.C. Kimlik Numarası zorunludur.']);
        }

        Validator::make(
            ['identity_number' => $identityNumber],
            ['identity_number' => [new TurkishIdentityNumber]]
        )->validate();

        $identityHash = hash('sha256', $identityNumber);
        $duplicate = User::query()
            ->where('identity_number_hash', $identityHash)
            ->when($user, fn ($query) => $query->whereKeyNot($user->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['identity_number' => 'Bu T.C. Kimlik Numarası başka bir kullanıcıda kayıtlı.']);
        }

        $attributes['identity_number'] = $identityNumber;
        $attributes['identity_number_hash'] = $identityHash;
    }

    private function businessAttributes(array $data, ?BusinessProfile $profile = null): array
    {
        $required = [
            'business_office_name' => 'Şirket / Ticari Unvan',
            'business_license_number' => 'Yetki Belgesi Numarası',
            'business_tax_office' => 'Vergi Dairesi',
            'business_address' => 'Açık iş yeri adresi',
        ];

        foreach ($required as $key => $label) {
            if (blank($data[$key] ?? null)) {
                throw ValidationException::withMessages([$key => "$label alanı zorunludur."]);
            }
        }

        $licenseNumber = trim((string) $data['business_license_number']);
        $duplicateLicense = BusinessProfile::query()
            ->where('license_number', $licenseNumber)
            ->when($profile, fn ($query) => $query->whereKeyNot($profile->getKey()))
            ->exists();

        if ($duplicateLicense) {
            throw ValidationException::withMessages(['business_license_number' => 'Bu yetki belgesi numarası başka bir işletmede kayıtlı.']);
        }

        $approvalStatusValue = $data['business_approval_status'] instanceof BusinessApprovalStatus
            ? $data['business_approval_status']->value
            : (string) ($data['business_approval_status'] ?? '');
        $approvalStatus = BusinessApprovalStatus::tryFrom($approvalStatusValue);

        if (! $approvalStatus) {
            throw ValidationException::withMessages(['business_approval_status' => 'Geçerli bir işletme onay durumu seçmelisiniz.']);
        }

        $attributes = [
            'office_name' => trim((string) $data['business_office_name']),
            'license_number' => $licenseNumber,
            'tax_office' => trim((string) $data['business_tax_office']),
            'business_phone' => $data['business_phone'] ?? null,
            'city' => $data['city'],
            'district' => $data['district'],
            'neighborhood' => $data['neighborhood'],
            'address' => trim((string) $data['business_address']),
            'approval_status' => $approvalStatus->value,
            'review_note' => $data['business_review_note'] ?? null,
        ];

        $taxNumber = preg_replace('/\D/', '', (string) ($data['business_tax_number'] ?? ''));

        if ($taxNumber === '') {
            if (! $profile?->tax_number_hash) {
                throw ValidationException::withMessages(['business_tax_number' => 'Vergi Numarası zorunludur.']);
            }

            return $attributes;
        }

        if (strlen($taxNumber) !== 10) {
            throw ValidationException::withMessages(['business_tax_number' => 'Vergi Numarası 10 haneli olmalıdır.']);
        }

        $taxHash = hash('sha256', $taxNumber);
        $duplicateTaxNumber = BusinessProfile::query()
            ->where('tax_number_hash', $taxHash)
            ->when($profile, fn ($query) => $query->whereKeyNot($profile->getKey()))
            ->exists();

        if ($duplicateTaxNumber) {
            throw ValidationException::withMessages(['business_tax_number' => 'Bu vergi numarası başka bir işletmede kayıtlı.']);
        }

        return $attributes + [
            'tax_number' => $taxNumber,
            'tax_number_hash' => $taxHash,
        ];
    }

    private function guardLastSupervisor(User $user, array $attributes): void
    {
        if ($user->role !== UserRole::SUPERVISOR) {
            return;
        }

        $willKeepPanelAccess = $attributes['role'] === UserRole::SUPERVISOR->value
            && $attributes['account_status'] === AccountStatus::ACTIVE->value
            && filled($attributes['email_verified_at']);

        if ($willKeepPanelAccess) {
            return;
        }

        $hasAnotherSupervisor = User::query()
            ->whereKeyNot($user->getKey())
            ->where('role', UserRole::SUPERVISOR->value)
            ->where('account_status', AccountStatus::ACTIVE->value)
            ->whereNotNull('email_verified_at')
            ->exists();

        if (! $hasAnotherSupervisor) {
            throw ValidationException::withMessages([
                'role' => 'Sistemde erişimi olan en az bir aktif supervisor kalmalıdır.',
            ]);
        }
    }
}

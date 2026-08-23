<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Mail\ApprovalRequestedMail;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ListingPublicationService
{
    public function submit(Listing $listing, User $user, bool $termsAccepted): void
    {
        abort_unless($listing->user_id === $user->id || $user->role === UserRole::SUPERVISOR, 403);

        $errors = [];
        if (! $termsAccepted) {
            $errors['terms'] = 'Yayın öncesi değiştirilemez alanlar onaylanmalıdır.';
        }
        if ($user->account_status !== AccountStatus::ACTIVE || ! $user->email_verified_at) {
            $errors['account'] = 'Aktif ve e-postası doğrulanmış bir hesap gereklidir.';
        }
        if ($user->role !== UserRole::SUPERVISOR && ! $user->eids_identity_verified_at) {
            $errors['eids_identity'] = 'Önce EİDS kimlik doğrulamasını tamamlayın.';
        }
        if ($user->role === UserRole::AGENT
            && $user->businessProfile?->approval_status !== BusinessApprovalStatus::APPROVED) {
            $errors['business'] = 'Emlak işletmesi yönetici tarafından onaylanmamış.';
        }
        if ($listing->eids_status !== 'verified') {
            $errors['eids'] = 'İlanın EİDS taşınmaz/yetki doğrulaması tamamlanmamış.';
        }
        if ($listing->eidsVerification?->expires_at?->isPast()) {
            $errors['eids'] = 'EİDS ilan yetkisinin süresi dolmuş.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $listing->forceFill([
            'publication_status' => 'pending_approval',
            'submitted_at' => now(),
            'publication_terms_accepted_at' => now(),
            'is_active' => false,
        ])->save();

        Mail::to(config('security.approval_email'))->send(new ApprovalRequestedMail('ilan', $user, $listing->id));
    }

    public function approve(Listing $listing, User $approver): void
    {
        abort_unless($approver->role === UserRole::SUPERVISOR, 403);

        if ($listing->publication_status !== 'pending_approval' || $listing->eids_status !== 'verified') {
            throw ValidationException::withMessages(['listing' => 'Yalnızca EİDS doğrulaması tamamlanmış ve onay bekleyen ilan yayınlanabilir.']);
        }

        if ($listing->eidsVerification?->expires_at?->isPast()) {
            throw ValidationException::withMessages(['listing' => 'EİDS yetki süresi dolmuş.']);
        }

        $listing->forceFill([
            'publication_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'published_at' => $listing->published_at ?: now(),
            'immutable_snapshot' => $this->immutableValues($listing),
            'is_active' => true,
        ])->save();
    }

    public function reject(Listing $listing, User $approver): void
    {
        abort_unless($approver->role === UserRole::SUPERVISOR, 403);
        $listing->forceFill([
            'publication_status' => 'rejected',
            'approved_at' => null,
            'approved_by' => $approver->id,
            'is_active' => false,
        ])->save();
    }

    private function immutableValues(Listing $listing): array
    {
        $verification = $listing->eidsVerification;

        return [
            'eids_document_no' => $listing->eids_document_no,
            'city' => $verification?->city ?: $listing->city_id,
            'district' => $verification?->district ?: $listing->district_id,
            'neighborhood' => $verification?->neighborhood ?: $listing->neighborhood_id,
            'block' => $verification?->block ?: $listing->block_number,
            'parcel' => $verification?->parcel ?: $listing->parcel_number,
            'category' => $listing->category,
            'type' => $listing->type,
            'source_type' => $listing->source_type,
            'user_id' => $listing->user_id,
            'business_profile_id' => $listing->business_profile_id,
        ];
    }
}

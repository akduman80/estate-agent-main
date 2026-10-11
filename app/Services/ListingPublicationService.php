<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Mail\ApprovalRequestedMail;
use App\Models\AgentConsultant;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ListingPublicationService
{
    public function __construct(
        private readonly ListingQuotaService $quotaService,
        private readonly EidsService $eidsService,
    ) {}

    public function submit(Listing $listing, User $user, bool $termsAccepted): void
    {
        abort_unless($this->canManage($listing, $user), 403);

        $errors = [];
        if (! $termsAccepted) {
            $errors['terms'] = 'Yayın öncesi değiştirilemez alanlar onaylanmalıdır.';
        }
        $owner = $listing->user;
        if ($owner?->account_status !== AccountStatus::ACTIVE || ! $owner?->email_verified_at) {
            $errors['account'] = 'Aktif ve e-postası doğrulanmış bir hesap gereklidir.';
        }
        if (! $listing->eids_exempt && $owner?->role !== UserRole::SUPERVISOR && ! $owner?->eids_identity_verified_at) {
            $errors['eids_identity'] = 'Önce EİDS kimlik doğrulamasını tamamlayın.';
        }
        if ($owner?->role === UserRole::AGENT
            && $owner->businessProfile?->approval_status !== BusinessApprovalStatus::APPROVED) {
            $errors['business'] = 'Emlak işletmesi yönetici tarafından onaylanmamış.';
        }
        // EİDS kontrolü yayınlama anında yapılır; kullanıcı ayrıca bir sekmeden sorgu başlatmaz.
        if (! $listing->eids_exempt && $listing->eids_status !== 'verified' && ! $this->eidsService->verifyListing($listing)) {
            $listing->refresh()->load('eidsVerification');
            $listing->forceFill(['publication_status' => 'draft', 'is_active' => false])->saveQuietly();
            $errors['eids'] = $listing->eidsVerification?->error_message ?: 'İlanın EİDS taşınmaz/yetki doğrulaması tamamlanmamış.';
        }
        if (! $listing->eids_exempt && $listing->eidsVerification?->expires_at?->isPast()) {
            $listing->forceFill(['publication_status' => 'draft', 'is_active' => false])->saveQuietly();
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

        if ($listing->publication_status !== 'pending_approval' || (! $listing->eids_exempt && $listing->eids_status !== 'verified')) {
            throw ValidationException::withMessages(['listing' => 'Yalnızca EİDS doğrulaması tamamlanmış ve onay bekleyen ilan yayınlanabilir.']);
        }

        if (! $listing->eids_exempt && $listing->eidsVerification?->expires_at?->isPast()) {
            throw ValidationException::withMessages(['listing' => 'EİDS yetki süresi dolmuş.']);
        }

        $this->quotaService->assertAvailable($listing->user);

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

    public function unpublish(Listing $listing, User $user): void
    {
        abort_unless($this->canManage($listing, $user), 403);

        $listing->forceFill([
            'publication_status' => 'unpublished',
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
            'eids_exempt' => (bool) $listing->eids_exempt,
            'category' => $listing->category,
            'type' => $listing->type,
            'source_type' => $listing->source_type,
            'user_id' => $listing->user_id,
            'business_profile_id' => $listing->business_profile_id,
        ];
    }

    private function canManage(Listing $listing, User $user): bool
    {
        if ($user->role === UserRole::SUPERVISOR || $listing->user_id === $user->id) {
            return true;
        }

        return $user->role === UserRole::CONSULTANT
            && $listing->created_by_id === $user->id
            && AgentConsultant::query()
                ->where('agent_id', $listing->user_id)
                ->where('consultant_id', $user->id)
                ->where('status', 'active')
                ->exists();
    }
}

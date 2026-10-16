<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Listing;

/** Read-only presentation of the existing, listing-specific verification. */
final class ListingEidsCard
{
    public static function forListing(Listing $listing): ?array
    {
        $verification = $listing->eidsVerification;

        if ($listing->eids_status !== 'verified'
            || ! $verification?->verified_at
            || $verification->verified_at->isFuture()
            || filled($verification->error_message)
            || ($verification->expires_at && ! $verification->expires_at->isFuture())
            || (string) $verification->listing_id !== (string) $listing->getKey()
            || (string) $verification->user_id !== (string) $listing->user_id
            || (string) $verification->business_profile_id !== (string) $listing->business_profile_id) {
            return null;
        }

        $role = $listing->user?->role;
        $isBusiness = filled($listing->business_profile_id)
            || in_array($listing->source_type, ['real_estate_office', 'zydemlak'], true)
            || in_array($role, [UserRole::AGENT, UserRole::CONSULTANT], true);

        if (! $isBusiness) {
            return $listing->source_type === 'owner' || $role === UserRole::INDIVIDUAL
                ? ['title' => 'Bu Gayrimenkul İçin Eids Onayı Alınmıştır', 'description' => null]
                : null;
        }

        // An explicit listing association must never fall back to another office.
        $profile = filled($listing->business_profile_id)
            ? $listing->businessProfile
            : $listing->user?->businessProfile;
        $name = trim((string) $profile?->office_name);
        $license = trim((string) $profile?->license_number);

        // Explicit site-owner identity requested by the owner. This is display
        // information only; it never grants or changes EIDS authorization.
        if (! $profile && blank($listing->business_profile_id)
            && $listing->source_type === 'zydemlak'
            && (int) $listing->user_id === 1
            && (int) $listing->user?->id === 1
            && $role === UserRole::SUPERVISOR) {
            $name = 'ZİYADE EMLAK';
            $license = '8000022';
        }

        if ($name === '' || $license === '') {
            return null;
        }

        return [
            'title' => $name.' – Yetki Belge No: '.$license,
            'description' => 'Bu gayrimenkul için Ticaret Bakanlığı EİDS sisteminde ilan verme iznine sahiptir.',
        ];
    }
}

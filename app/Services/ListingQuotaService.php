<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ListingQuotaService
{
    public function defaultLimit(User $user): ?int
    {
        return match ($user->role) {
            UserRole::INDIVIDUAL => 10,
            UserRole::AGENT => 100,
            default => null,
        };
    }

    public function effectiveLimit(User $user): ?int
    {
        if ($user->listing_limit_override !== null) {
            return (int) $user->listing_limit_override;
        }

        return $this->defaultLimit($user);
    }

    public function activeCount(User $user): int
    {
        return $user->listings()
            ->where('publication_status', 'approved')
            ->where('is_active', true)
            ->count();
    }

    public function hasReachedLimit(User $user): bool
    {
        $limit = $this->effectiveLimit($user);

        return $limit !== null && $this->activeCount($user) >= $limit;
    }

    public function assertAvailable(User $user): void
    {
        if ($this->hasReachedLimit($user)) {
            throw ValidationException::withMessages([
                'listing_limit' => 'Aktif ilan limitinize ulaştınız.',
            ]);
        }
    }
}

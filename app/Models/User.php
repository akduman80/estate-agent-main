<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_verified_at',
        'two_factor_enabled',
        'identity_number',
        'identity_number_hash',
        'city',
        'district',
        'neighborhood',
        'listing_limit_override',
        'password',
        'role',
        'account_status',
        'eids_user_code',
        'eids_identity_verified_at',
        'last_two_factor_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
            'identity_number' => 'encrypted',
            'eids_user_code' => 'encrypted',
            'eids_identity_verified_at' => 'datetime',
            'last_two_factor_at' => 'datetime',
            'listing_limit_override' => 'integer',
        ];
    }

    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    public function consents()
    {
        return $this->hasMany(UserConsent::class);
    }

    public function isActive(): bool
    {
        return $this->account_status === AccountStatus::ACTIVE;
    }

    // Panel Erişim Kontrolü
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->isActive() || ! $this->email_verified_at) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::SUPERVISOR,
            'portal' => in_array($this->role, [UserRole::AGENT, UserRole::INDIVIDUAL, UserRole::SUPERVISOR], true),
            'editor' => in_array($this->role, [UserRole::EDITOR, UserRole::SUPERVISOR], true),
            default => false,
        };
    }
}

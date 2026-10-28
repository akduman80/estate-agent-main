<?php

namespace App\Models;

use App\Enums\BusinessApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    protected static function booted(): void
    {
        static::updating(function (BusinessProfile $profile): void {
            if ($profile->isDirty('tax_office') && ! $profile->isDirty(['tax_office_city_code', 'tax_office_code'])) {
                $profile->tax_office_city_code = null;
                $profile->tax_office_code = null;
            }
        });

        static::updated(function (BusinessProfile $profile): void {
            if ($profile->wasChanged('tax_number_hash')
                || ($profile->wasChanged('tax_number') && $profile->tax_number !== $profile->getOriginal('tax_number'))) {
                $profile->user?->forceFill(['eids_user_code' => null, 'eids_identity_verified_at' => null])->save();
            }
        });
    }

    protected $fillable = [
        'user_id', 'office_name', 'license_number', 'tax_office', 'tax_number',
        'tax_office_city_code', 'tax_office_code', 'tax_number_hash', 'business_phone', 'city', 'district', 'neighborhood', 'address',
        'logo_path', 'approval_status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_number' => 'encrypted',
            'approval_status' => BusinessApprovalStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

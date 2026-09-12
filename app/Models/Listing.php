<?php

namespace App\Models;

use App\Observers\ListingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([ListingObserver::class])]
class Listing extends Model
{
    protected $fillable = [
        'user_id',
        'created_by_id',
        'business_profile_id',
        'source_type',
        'title',
        'slug',
        'description',
        'type',
        'category',
        'price',
        'currency',
        'zoning_status',
        'square_meters',
        'deed_status',
        'block_number',
        'parcel_number',
        'sheet_number',
        'kaks_emsal',
        'gabari',
        'credit_eligibility',
        'owner_nationality',
        'from_who',
        'swap_option',
        'eids_status',
        'publication_status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'published_at',
        'publication_terms_accepted_at',
        'immutable_snapshot',
        'eids_document_no',
        'city_id',
        'district_id',
        'neighborhood_id',
        'video_url',
        'is_active',
        'is_featured',
        'error_message',
        'response',
        'verified_at',
        'net_square_meters',
        'room_count',
        'heating_type',
        'building_age',
        'furniture_status',
        'floor_number',
        'total_floors',
        'deposit_price',
        'dues_price',
        'usage_status',
        'facade',
        'site_name',
        'subcategory',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'publication_terms_accepted_at' => 'datetime',
            'immutable_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ListingChangeLog::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'owner' => 'SAHİBİNDEN',
            'real_estate_office' => 'EMLAK OFİSİ',
            default => 'ZYDEMLAK',
        };
    }

    public function eidsVerification(): HasOne
    {
        return $this->hasOne(ListingEidsVerification::class);
    }

    public function eidsRequestLogs(): HasMany
    {
        return $this->hasMany(ListingEidsRequestLog::class);
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->eids_status === 'verified';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('eids_status', 'verified')
            ->where('publication_status', 'approved')
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('eidsVerification')
                    ->orWhereHas('eidsVerification', fn (Builder $verification) => $verification
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now()));
            });
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->eidsVerification?->city ?: $this->city_id;
    }

    public function getDistrictNameAttribute(): ?string
    {
        return $this->eidsVerification?->district ?: $this->district_id;
    }
}

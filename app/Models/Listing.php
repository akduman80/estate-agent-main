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
        'title',
        'slug',
        'description',
        'type',
        'price',
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
        'eids_document_no',
        'city_id',
        'district_id',
        'neighborhood_id',
        'video_url',
        'is_active',
        'error_message',
        'response',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
            ->where('eids_status', 'verified');
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

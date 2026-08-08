<?php

namespace App\Models;

use App\Observers\ListingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'eids_document_no',
        'city_id',
        'district_id',
        'neighborhood_id',
        'video_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingEidsVerification extends Model
{
    protected $fillable = [
        'listing_id',
        'user_id',
        'business_profile_id',
        'eids_user_code_hash',
        'error_message',
        'response',
        'verified_at',
        'expires_at',
        'city',
        'district',
        'neighborhood',
        'block',
        'parcel',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

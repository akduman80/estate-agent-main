<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingEidsRequestLog extends Model
{
    protected $fillable = [
        'listing_id',
        'owner_user_id',
        'owner_name',
        'owner_role',
        'business_profile_id',
        'business_office_name',
        'listing_source_type',
        'authorization_subject_type',
        'eids_identity_context',
        'eids_user_code_source',
        'eids_user_code_hash',
        'tax_number_last4',
        'sent_tax_number',
        'sent_user_code',
        'endpoint',
        'method',
        'request_payload',
        'response_status',
        'response_payload',
        'result_status',
        'error_message',
        'duration_ms',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'sent_tax_number' => 'boolean',
            'sent_user_code' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

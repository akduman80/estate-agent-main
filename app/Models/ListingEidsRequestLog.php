<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingEidsRequestLog extends Model
{
    protected $fillable = [
        'listing_id',
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
            'attempted_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

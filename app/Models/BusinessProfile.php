<?php

namespace App\Models;

use App\Enums\BusinessApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id', 'office_name', 'license_number', 'tax_office', 'tax_number',
        'tax_number_hash', 'business_phone', 'city', 'district', 'neighborhood', 'address',
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

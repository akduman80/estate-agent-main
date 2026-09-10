<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $fillable = [
        'path',
        'route_name',
        'method',
        'status_code',
        'referer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'browser',
        'platform',
        'visitor_hash',
        'user_id',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

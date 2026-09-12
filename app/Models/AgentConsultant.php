<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConsultant extends Model
{
    protected $fillable = ['agent_id', 'consultant_id', 'status', 'assigned_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }
}

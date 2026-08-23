<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConsent extends Model
{
    protected $fillable = ['user_id', 'legal_document_id', 'accepted_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}

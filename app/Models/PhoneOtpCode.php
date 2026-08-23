<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneOtpCode extends Model
{
    protected $fillable = ['user_id', 'code_hash', 'attempts', 'expires_at', 'locked_until', 'consumed_at', 'request_ip'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'locked_until' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = ['slug', 'title', 'version', 'content', 'is_required', 'is_active', 'published_at'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}

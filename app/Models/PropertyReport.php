<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyReport extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(PropertyComment::class, 'comment_id');
    }
}

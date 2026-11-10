<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyLike extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

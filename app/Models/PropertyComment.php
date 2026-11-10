<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyComment extends Model
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

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function reports()
    {
        return $this->hasMany(PropertyReport::class, 'comment_id');
    }
}

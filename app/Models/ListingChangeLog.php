<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingChangeLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['listing_id', 'user_id', 'field', 'old_value', 'new_value', 'ip_address', 'changed_at'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

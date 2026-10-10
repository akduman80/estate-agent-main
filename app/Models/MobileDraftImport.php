<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDraftImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'media' => 'array', 'pending_update' => 'boolean'];
    }

    public function listing() { return $this->belongsTo(Listing::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function user() { return $this->belongsTo(User::class); }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappOutbox extends Model
{
    protected $table = 'whatsapp_outbox';
    protected $guarded = [];
    protected $hidden = ['payload', 'recipient'];
    protected function casts(): array
    {
        return ['payload'=>'encrypted:array', 'available_at'=>'datetime', 'claimed_at'=>'datetime'];
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return ['enabled'=>'boolean', 'members_enabled'=>'boolean', 'listings_enabled'=>'boolean', 'checked_at'=>'datetime'];
    }
    public static function current(): self
    {
        return static::firstOrCreate(['id'=>1], ['enabled'=>config('whatsapp.enabled'), 'number'=>config('whatsapp.number')]);
    }
}

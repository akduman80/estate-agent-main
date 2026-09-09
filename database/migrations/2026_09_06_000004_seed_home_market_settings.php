<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'rent_increase_rate' => 31.79,
            'rent_increase_period' => 'Eylül 2026',
            'rent_increase_updated_at' => '2026-09-03',
        ] as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'rent_increase_rate',
            'rent_increase_period',
            'rent_increase_updated_at',
        ])->delete();
    }
};

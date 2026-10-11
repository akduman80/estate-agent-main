<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->boolean('eids_exempt')->default(false);
        });
        $setting = DB::table('settings')->where('key', 'eids_exempt_listing_types')->value('value');
        $types = $setting === null ? [] : json_decode($setting, true);
        DB::table('listings')->whereIn('type', is_array($types) ? $types : [])->update(['eids_exempt' => true]);
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn('eids_exempt'));
    }
};

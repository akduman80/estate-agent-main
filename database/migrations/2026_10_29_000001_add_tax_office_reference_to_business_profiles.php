<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('tax_office_city_code', 2)->nullable();
            $table->string('tax_office_code', 5)->nullable();
        });
    }

    public function down(): void
    {
        // Keep stored references on code rollback; existing tax_office text is unchanged.
    }
};

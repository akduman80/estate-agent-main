<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('city')->nullable()->after('identity_number_hash');
            $table->string('district')->nullable()->after('city');
            $table->index(['city', 'district']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['city', 'district']);
            $table->dropColumn(['city', 'district']);
        });
    }
};

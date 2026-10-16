<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table): void {
            $table->uuid('voting_period')->nullable();
            $table->timestamp('voting_reset_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('polls', fn (Blueprint $table) => $table->dropColumn(['voting_period', 'voting_reset_at']));
    }
};

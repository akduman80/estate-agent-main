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
            $table->foreignId('created_by_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'created_by_id']);
        });

        DB::table('listings')->whereNull('created_by_id')->update(['created_by_id' => DB::raw('user_id')]);

        Schema::create('agent_consultants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['agent_id', 'consultant_id']);
            $table->index(['agent_id', 'status']);
            $table->index(['consultant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_consultants');
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropForeign(['created_by_id']);
            $table->dropIndex(['user_id', 'created_by_id']);
            $table->dropColumn('created_by_id');
        });
    }
};

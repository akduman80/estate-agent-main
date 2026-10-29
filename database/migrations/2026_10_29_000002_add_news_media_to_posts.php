<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->json('news_media')->nullable());
    }

    public function down(): void
    {
        // Explicit rollback only: this removes the new media metadata, never posts/images.
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('news_media'));
    }
};

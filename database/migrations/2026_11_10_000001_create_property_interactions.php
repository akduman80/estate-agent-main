<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'listing_id']);
            $table->unique(['user_id', 'post_id']);
        });
        Schema::create('property_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('property_comments')->cascadeOnDelete();
            $table->string('kind', 24);
            $table->text('body');
            $table->string('status', 24)->default('pending');
            $table->string('author_label', 64)->nullable();
            $table->uuid('request_id')->unique();
            $table->timestamps();
            $table->index(['listing_id', 'status', 'created_at']);
            $table->index(['post_id', 'status', 'created_at']);
        });
        Schema::create('property_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comment_id')->constrained('property_comments')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->timestamps();
            $table->unique(['user_id', 'comment_id']);
        });
        Schema::create('property_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('property_comments')->nullOnDelete();
            $table->string('action', 32);
            $table->string('reason', 500);
            $table->timestamps();
        });
        Schema::create('property_interaction_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notifications')->default(true);
            $table->boolean('blocked')->default(false);
            $table->timestamps();
        });
        Schema::create('property_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained('property_comments')->cascadeOnDelete();
            $table->string('message', 255);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_notifications');
        Schema::dropIfExists('property_interaction_preferences');
        Schema::dropIfExists('property_moderation_logs');
        Schema::dropIfExists('property_reports');
        Schema::dropIfExists('property_comments');
        Schema::dropIfExists('property_likes');
    }
};

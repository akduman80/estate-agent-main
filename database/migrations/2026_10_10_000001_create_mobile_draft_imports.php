<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mobile_draft_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
        Schema::create('mobile_draft_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->string('local_id', 100);
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->json('media');
            $table->string('remote_revision', 100);
            $table->boolean('pending_update')->default(false);
            $table->string('request_hash', 64);
            $table->string('target_updated_at', 100)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'kind', 'local_id']);
            $table->unique('project_id');
            $table->unique('listing_id');
        });
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('extra_fields')->nullable();
            $table->json('mobile_video_paths')->nullable();
        });
        Schema::table('listings', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('mapped_address')->nullable();
            $table->json('extra_fields')->nullable();
            $table->json('mobile_video_paths')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['latitude', 'longitude', 'mapped_address', 'extra_fields', 'mobile_video_paths']));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['extra_fields', 'mobile_video_paths']));
        Schema::dropIfExists('mobile_draft_imports');
        Schema::dropIfExists('mobile_draft_tokens');
    }
};

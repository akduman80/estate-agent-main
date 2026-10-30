<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->boolean('members_enabled')->default(true);
            $table->boolean('listings_enabled')->default(true);
            $table->string('number', 20)->default('');
            $table->string('connection_state', 30)->default('unchecked');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('whatsapp_outbox', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 30);
            $table->string('event_id', 100);
            $table->string('recipient', 20);
            $table->text('payload');
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->string('provider_id', 200)->nullable();
            $table->string('error_code', 60)->nullable();
            $table->timestamps();
            $table->unique(['event_type', 'event_id', 'recipient'], 'whatsapp_event_recipient_unique');
            $table->index(['status', 'available_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_outbox');
        Schema::dropIfExists('whatsapp_settings');
    }
};

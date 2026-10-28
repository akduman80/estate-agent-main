<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('listings', 'form_details')) {
            Schema::table('listings', fn (Blueprint $table) => $table->json('form_details')->nullable());
        }
        if (! Schema::hasColumn('listings', 'private_note')) {
            Schema::table('listings', fn (Blueprint $table) => $table->text('private_note')->nullable());
        }
        if (! Schema::hasTable('listing_form_drafts')) {
            Schema::create('listing_form_drafts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version')->default(0);
                $table->longText('payload')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'listing_id']);
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive: notes/drafts must survive an application rollback.
        // Remove these additive fields only after an explicit, backed-up data-retention decision.
    }
};

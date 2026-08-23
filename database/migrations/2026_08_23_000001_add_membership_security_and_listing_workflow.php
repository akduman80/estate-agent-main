<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone', 24)->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->text('identity_number')->nullable()->after('phone_verified_at');
            $table->char('identity_number_hash', 64)->nullable()->unique()->after('identity_number');
            $table->string('account_status')->default('active')->after('role');
            $table->text('eids_user_code')->nullable()->after('account_status');
            $table->timestamp('eids_identity_verified_at')->nullable()->after('eids_user_code');
            $table->timestamp('last_two_factor_at')->nullable()->after('eids_identity_verified_at');
        });

        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('office_name');
            $table->string('license_number')->unique();
            $table->string('tax_office');
            $table->text('tax_number');
            $table->char('tax_number_hash', 64)->unique();
            $table->string('business_phone', 24)->nullable();
            $table->string('city');
            $table->string('district');
            $table->text('address');
            $table->string('logo_path')->nullable();
            $table->string('approval_status')->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_otp_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32)->index();
            $table->string('target_email')->nullable();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose', 'created_at']);
        });

        Schema::create('phone_otp_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('title');
            $table->string('version');
            $table->longText('content');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['slug', 'version']);
        });

        Schema::create('user_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'legal_document_id']);
        });

        Schema::table('listings', function (Blueprint $table): void {
            $table->foreignId('business_profile_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('source_type')->default('zydemlak')->after('business_profile_id');
            $table->string('publication_status')->default('approved')->after('eids_status');
            $table->timestamp('submitted_at')->nullable()->after('publication_status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('approved_by');
            $table->timestamp('publication_terms_accepted_at')->nullable()->after('published_at');
            $table->json('immutable_snapshot')->nullable()->after('publication_terms_accepted_at');
            $table->decimal('net_square_meters', 10, 2)->nullable();
            $table->string('room_count')->nullable();
            $table->string('heating_type')->nullable();
            $table->string('building_age')->nullable();
            $table->string('furniture_status')->nullable();
            $table->string('floor_number')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->decimal('deposit_price', 15, 2)->nullable();
            $table->decimal('dues_price', 15, 2)->nullable();
            $table->string('usage_status')->nullable();
            $table->string('facade')->nullable();
            $table->string('site_name')->nullable();
            $table->string('subcategory')->nullable();
            $table->index(['publication_status', 'eids_status', 'is_active']);
        });

        Schema::table('listing_eids_verifications', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('listing_id')->constrained()->nullOnDelete();
            $table->foreignId('business_profile_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->char('eids_user_code_hash', 64)->nullable()->after('business_profile_id');
        });

        // Mevcut çalışan ve EİDS doğrulanmış ilanları ZYDEMLAK kaynağıyla koru;
        // yayın zamanı backfill'i değiştirilemez alan kurallarını eski ilanlarda da etkinleştirir.
        DB::table('listings')
            ->where('eids_status', 'verified')
            ->where('is_active', true)
            ->update([
                'publication_status' => 'approved',
                'approved_at' => DB::raw('created_at'),
                'published_at' => DB::raw('created_at'),
            ]);

        Schema::create('listing_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field');
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('changed_at');
            $table->index(['listing_id', 'changed_at']);
        });

        DB::table('users')->where('role', 'agent')->update(['account_status' => 'active']);
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_change_logs');

        Schema::table('listing_eids_verifications', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['business_profile_id']);
            $table->dropColumn(['user_id', 'business_profile_id', 'eids_user_code_hash']);
        });

        Schema::table('listings', function (Blueprint $table): void {
            $table->dropForeign(['business_profile_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'business_profile_id', 'source_type', 'publication_status', 'submitted_at',
                'approved_at', 'approved_by', 'published_at', 'publication_terms_accepted_at',
                'immutable_snapshot', 'net_square_meters', 'room_count', 'heating_type',
                'building_age', 'furniture_status', 'floor_number', 'total_floors',
                'deposit_price', 'dues_price', 'usage_status', 'facade', 'site_name', 'subcategory',
            ]);
        });

        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('phone_otp_codes');
        Schema::dropIfExists('email_otp_codes');
        Schema::dropIfExists('business_profiles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['identity_number_hash']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'phone_verified_at', 'identity_number',
                'identity_number_hash', 'account_status', 'eids_user_code',
                'eids_identity_verified_at', 'last_two_factor_at',
            ]);
        });
    }
};

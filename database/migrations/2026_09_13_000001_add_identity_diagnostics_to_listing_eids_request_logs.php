<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_eids_request_logs', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('listing_id')->constrained('users')->nullOnDelete();
            $table->string('owner_name')->nullable()->after('owner_user_id');
            $table->string('owner_role')->nullable()->after('owner_name');
            $table->foreignId('business_profile_id')->nullable()->after('owner_role')->constrained()->nullOnDelete();
            $table->string('business_office_name')->nullable()->after('business_profile_id');
            $table->string('listing_source_type')->nullable()->after('business_office_name');
            $table->string('authorization_subject_type')->nullable()->after('listing_source_type');
            $table->string('eids_identity_context')->nullable()->after('authorization_subject_type');
            $table->string('eids_user_code_source')->nullable()->after('eids_identity_context');
            $table->string('eids_user_code_hash', 64)->nullable()->after('eids_user_code_source');
            $table->string('tax_number_last4', 4)->nullable()->after('eids_user_code_hash');
            $table->boolean('sent_tax_number')->default(false)->after('tax_number_last4');
            $table->boolean('sent_user_code')->default(false)->after('sent_tax_number');

            $table->index(['eids_identity_context', 'result_status'], 'eids_logs_context_status_index');
            $table->index(['authorization_subject_type', 'result_status'], 'eids_logs_subject_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('listing_eids_request_logs', function (Blueprint $table) {
            $table->dropIndex('eids_logs_context_status_index');
            $table->dropIndex('eids_logs_subject_status_index');

            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropConstrainedForeignId('business_profile_id');
            $table->dropColumn([
                'owner_name',
                'owner_role',
                'business_office_name',
                'listing_source_type',
                'authorization_subject_type',
                'eids_identity_context',
                'eids_user_code_source',
                'eids_user_code_hash',
                'tax_number_last4',
                'sent_tax_number',
                'sent_user_code',
            ]);
        });
    }
};

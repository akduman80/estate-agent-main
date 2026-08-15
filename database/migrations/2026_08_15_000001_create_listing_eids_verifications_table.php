<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_eids_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('error_message')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('block')->nullable();
            $table->string('parcel')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('listings', 'eids_error_message')) {
            DB::table('listings')
                ->where(function ($query) {
                    $query->whereNotNull('eids_error_message')
                        ->orWhereNotNull('eids_response')
                        ->orWhereNotNull('eids_verified_at')
                        ->orWhereNotNull('eids_expires_at')
                        ->orWhereNotNull('eids_city')
                        ->orWhereNotNull('eids_district')
                        ->orWhereNotNull('eids_neighborhood')
                        ->orWhereNotNull('eids_block')
                        ->orWhereNotNull('eids_parcel');
                })
                ->orderBy('id')
                ->get([
                    'id',
                    'eids_error_message',
                    'eids_response',
                    'eids_verified_at',
                    'eids_expires_at',
                    'eids_city',
                    'eids_district',
                    'eids_neighborhood',
                    'eids_block',
                    'eids_parcel',
                    'created_at',
                    'updated_at',
                ])
                ->each(function ($listing): void {
                    DB::table('listing_eids_verifications')->insert([
                        'listing_id' => $listing->id,
                        'error_message' => $listing->eids_error_message,
                        'response' => $listing->eids_response,
                        'verified_at' => $listing->eids_verified_at,
                        'expires_at' => $listing->eids_expires_at,
                        'city' => $listing->eids_city,
                        'district' => $listing->eids_district,
                        'neighborhood' => $listing->eids_neighborhood,
                        'block' => $listing->eids_block,
                        'parcel' => $listing->eids_parcel,
                        'created_at' => $listing->created_at ?? now(),
                        'updated_at' => $listing->updated_at ?? now(),
                    ]);
                });

            Schema::table('listings', function (Blueprint $table) {
                $table->dropColumn([
                    'eids_error_message',
                    'eids_response',
                    'eids_verified_at',
                    'eids_expires_at',
                    'eids_city',
                    'eids_district',
                    'eids_neighborhood',
                    'eids_block',
                    'eids_parcel',
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->text('eids_error_message')->nullable()->after('eids_document_no');
            $table->json('eids_response')->nullable()->after('eids_error_message');
            $table->timestamp('eids_verified_at')->nullable()->after('eids_response');
            $table->timestamp('eids_expires_at')->nullable()->after('eids_verified_at');
            $table->string('eids_city')->nullable()->after('eids_expires_at');
            $table->string('eids_district')->nullable()->after('eids_city');
            $table->string('eids_neighborhood')->nullable()->after('eids_district');
            $table->string('eids_block')->nullable()->after('eids_neighborhood');
            $table->string('eids_parcel')->nullable()->after('eids_block');
        });

        DB::table('listing_eids_verifications')
            ->orderBy('listing_id')
            ->get()
            ->each(function ($verification): void {
                DB::table('listings')
                    ->where('id', $verification->listing_id)
                    ->update([
                        'eids_error_message' => $verification->error_message,
                        'eids_response' => $verification->response,
                        'eids_verified_at' => $verification->verified_at,
                        'eids_expires_at' => $verification->expires_at,
                        'eids_city' => $verification->city,
                        'eids_district' => $verification->district,
                        'eids_neighborhood' => $verification->neighborhood,
                        'eids_block' => $verification->block,
                        'eids_parcel' => $verification->parcel,
                    ]);
            });

        Schema::dropIfExists('listing_eids_verifications');
    }
};

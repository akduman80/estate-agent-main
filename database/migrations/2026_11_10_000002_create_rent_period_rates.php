<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_period_rates', function (Blueprint $table) {
            $table->id();
            $table->date('period')->unique();
            $table->decimal('rate', 7, 2);
            $table->string('source_url', 1000);
            $table->date('published_on');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('rent_rate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_id')->constrained('rent_period_rates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot');
            $table->timestamps();
        });
        DB::table('rent_period_rates')->insert(['period' => '2026-11-01', 'rate' => 31.23, 'source_url' => 'https://veriportali.tuik.gov.tr/Bulten/Index?p=Tuketici-Fiyat-Endeksi-Ekim-2026-58291', 'published_on' => '2026-11-03', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('rent_period_rates')->where('period', '2026-11-01')->value('id');
        DB::table('rent_rate_logs')->insert(['rate_id' => $id, 'snapshot' => json_encode(['period' => '2026-11-01', 'rate' => '31.23', 'source' => 'TÜİK Ekim 2026 bülteni 58291', 'published_on' => '2026-11-03', 'action' => 'initial-source']), 'created_at' => now(), 'updated_at' => now()]);
        foreach ([
            ['2024-07-01', 65.07, '2024-07-03', 'https://veriportali.tuik.gov.tr/Bulten/Index?p=Tuketici-Fiyat-Endeksi-Haziran-2024-53616'],
            ['2026-07-01', 32.03, '2026-07-03', 'https://veriportali.tuik.gov.tr/tr/press/58289'],
            ['2026-08-01', 31.90, '2026-08-03', 'https://veriportali.tuik.gov.tr/tr/press/58297'],
        ] as [$period, $rate, $published, $source]) {
            $id = DB::table('rent_period_rates')->insertGetId(['period' => $period, 'rate' => $rate, 'source_url' => $source, 'published_on' => $published, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('rent_rate_logs')->insert(['rate_id' => $id, 'snapshot' => json_encode(['period' => $period, 'rate' => $rate, 'source_url' => $source, 'published_on' => $published, 'action' => 'initial-source']), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_rate_logs');
        Schema::dropIfExists('rent_period_rates');
    }
};

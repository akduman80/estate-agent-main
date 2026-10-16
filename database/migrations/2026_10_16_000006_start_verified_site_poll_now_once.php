<?php

use App\Models\Poll;
use App\Services\PollResetAudit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Poll::query()->whereKey(1)->exists()) {
            return;
        }
        DB::transaction(function (): void {
            $poll = Poll::query()->lockForUpdate()->findOrFail(1);
            $report = PollResetAudit::read();
            if ($poll->voting_period !== '32b5e655-e019-46e5-b9bb-6542f9f7a107'
                || ! ($report['reset_completed'] ?? false)) {
                throw new RuntimeException('Doğrulanmış anket dönemi bulunamadı; başlangıç değiştirilmedi.');
            }
            if ($poll->starts_at && $poll->starts_at->isFuture()) {
                $report['original_starts_at'] = $poll->starts_at->toIso8601String();
                $poll->forceFill(['starts_at' => now()])->save();
            }
            $report['starts_at'] = $poll->starts_at?->toIso8601String();
            PollResetAudit::write($report);
        });
    }

    public function down(): void
    {
    }
};

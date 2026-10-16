<?php

use App\Models\Poll;
use App\Services\PollResetAudit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // A fresh installation has no historical poll to reset.
        if (! Poll::query()->whereKey(1)->exists()) {
            return;
        }
        DB::transaction(function (): void {
            $report = PollResetAudit::read();
            if (($report['match_count'] ?? 0) !== 1 || ($report['poll_id'] ?? null) !== 1
                || ! ($report['backup_verified'] ?? false)) {
                throw new RuntimeException('Hedef anket ve yayın öncesi oy yedeği doğrulanmadı; sıfırlama yapılmadı.');
            }
            $poll = Poll::query()->lockForUpdate()->findOrFail(1);
            if (! hash_equals($report['question_sha256'], hash('sha256', $poll->question))) {
                throw new RuntimeException('Hedef anket sorusu değişti; sıfırlama yapılmadı.');
            }
            $disk = Storage::disk('local');
            if (! $disk->exists($report['backup_path']) || ! hash_equals(
                $report['backup_sha256'], hash('sha256', $disk->get($report['backup_path']))
            )) {
                throw new RuntimeException('Yayın öncesi oy yedeği okunamadı veya değişti; sıfırlama yapılmadı.');
            }
            if ($poll->voting_reset_at === null) {
                if (Artisan::call('polls:reset-voting-once', ['poll' => 1]) !== 0) {
                    throw new RuntimeException('Yedekli anket sıfırlama tamamlanamadı.');
                }
            }
            $poll->refresh();
            if (! $poll->voting_period || ! $poll->voting_reset_at) {
                throw new RuntimeException('Yeni anket dönemi doğrulanamadı.');
            }
            $report['reset_completed'] = true;
            $report['period'] = $poll->voting_period;
            $report['reset_at'] = $poll->voting_reset_at->toIso8601String();
            $report['zero_votes_verified_at_reset'] = $poll->votes()->count() === 0
                && (int) $poll->options()->sum('votes_count') === 0;
            PollResetAudit::write($report);
        });
    }

    public function down(): void
    {
        // Never repeat a reset or restore historical votes on deployment rollback.
    }
};

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
            $period = '32b5e655-e019-46e5-b9bb-6542f9f7a107';
            if ($poll->voting_period !== $period) {
                throw new RuntimeException('Test oylarının dönemi değişti; hiçbir oy silinmedi.');
            }
            // These are the two dedicated, recorded browser identities used in live QA.
            $tokens = ['0c4c1eca-fe1b-49c7-a56c-e0e0d0080b08', '8239787c-362e-4445-a9e3-ab6c273b4c0a'];
            $hashes = array_map(fn ($token) => hash_hmac('sha256', $period.'|browser:'.$token, (string) config('app.key')), $tokens);
            $votes = $poll->votes()->whereIn('voter_hash', $hashes)->get();
            foreach ($votes->groupBy('poll_option_id') as $optionId => $group) {
                $updated = $poll->options()->whereKey($optionId)->where('votes_count', '>=', $group->count())
                    ->decrement('votes_count', $group->count());
                if ($updated !== 1) {
                    throw new RuntimeException('Test oyu sayacı doğrulanamadı; temizleme geri alındı.');
                }
            }
            $poll->votes()->whereIn('id', $votes->modelKeys())->delete();
            $report = PollResetAudit::read();
            if (! $report || ($report['period'] ?? null) !== $period) {
                throw new RuntimeException('Doğrulanmış dönem raporu bulunamadı; temizleme geri alındı.');
            }
            $report['test_votes_removed'] = ! $poll->votes()->whereIn('voter_hash', $hashes)->exists();
            $report['removed_test_vote_count'] = ($report['removed_test_vote_count'] ?? 0) + $votes->count();
            $report['counters_consistent_after_cleanup'] = (int) $poll->options()->sum('votes_count') === $poll->votes()->count();
            PollResetAudit::write($report);
        });
    }

    public function down(): void
    {
        // Test votes must never be reintroduced on rollback.
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Poll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResetPollVoting extends Command
{
    protected $signature = 'polls:reset-voting-once {poll : Explicit poll ID}';

    protected $description = 'Start a new voting period for one poll, once only';

    public function handle(): int
    {
        return DB::transaction(function (): int {
            $poll = Poll::query()->lockForUpdate()->find($this->argument('poll'));
            if (! $poll) {
                $this->error('Anket bulunamadı; hiçbir kayıt değiştirilmedi.');
                return self::FAILURE;
            }
            if ($poll->voting_reset_at !== null) {
                $this->info('Bu anket daha önce sıfırlandı. Mevcut oylar korundu.');
                return self::SUCCESS;
            }
            $period = (string) Str::uuid();
            $backupPath = "poll-vote-backups/poll-{$poll->id}-{$period}.json";
            $backup = json_encode([
                'backed_up_at' => now()->toIso8601String(),
                'poll' => $poll->toArray(),
                'options' => $poll->options()->get()->toArray(),
                'votes' => $poll->votes()->get()->toArray(),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $disk = Storage::disk('local');
            if (! $disk->put($backupPath, $backup, 'private')
                || ! hash_equals(hash('sha256', $backup), hash('sha256', (string) $disk->get($backupPath)))) {
                $this->error('Oy yedeği doğrulanamadı. Sıfırlama yapılmadı.');
                return self::FAILURE;
            }
            $this->info("Özel disk oy yedeği: {$backupPath}; SHA-256: ".hash('sha256', $backup));
            $poll->votes()->delete();
            $poll->options()->update(['votes_count' => 0]);
            $poll->forceFill(['voting_period' => $period, 'voting_reset_at' => now()])->save();
            $this->info("Anket #{$poll->id} sıfırlandı. Yeni dönem: {$poll->voting_period}");
            return self::SUCCESS;
        });
    }
}

<?php

use App\Models\Poll;
use App\Services\PollResetAudit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $normalize = fn ($text) => preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii(
                html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'tr'
            )));
            $expected = $normalize('WEB SİTEMİZ SİZCE OLMUŞ MU?');
            $matches = Poll::query()->get()->filter(fn ($poll) => $normalize($poll->question) === $expected);
            $report = ['match_count' => $matches->count(), 'backup_verified' => false, 'reset_completed' => false];
            if ($matches->count() !== 1) {
                PollResetAudit::write($report);
                return;
            }
            $poll = Poll::query()->lockForUpdate()->findOrFail($matches->first()->id);
            $backupPath = 'poll-vote-backups/site-poll-before-reset-20261016-'.Str::uuid().'.json';
            $content = json_encode([
                'backed_up_at' => now()->toIso8601String(),
                'poll' => $poll->toArray(),
                'options' => $poll->options()->get()->toArray(),
                'votes' => $poll->votes()->get()->toArray(),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $disk = Storage::disk('local');
            if (! $disk->put($backupPath, $content, 'private') || $disk->get($backupPath) !== $content) {
                throw new RuntimeException('Anket yedeği doğrulanamadı; hiçbir oy değiştirilmedi.');
            }
            PollResetAudit::write($report + [
                'poll_id' => $poll->id,
                'status' => $poll->status,
                'starts_at' => $poll->starts_at?->toIso8601String(),
                'ends_at' => $poll->ends_at?->toIso8601String(),
                'backup_path' => $backupPath,
                'backup_sha256' => hash('sha256', $content),
                'question_sha256' => hash('sha256', $poll->question),
            ]);
            $report = PollResetAudit::read();
            $report['backup_verified'] = true;
            PollResetAudit::write($report);
        });
    }

    public function down(): void
    {
        // Backups are deliberately retained; rollback never restores or deletes votes.
    }
};

<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PollResetAudit
{
    public const PATH = 'poll-vote-backups/site-poll-reset-20261016-status.json';

    public static function read(): ?array
    {
        $disk = Storage::disk('local');
        return $disk->exists(self::PATH)
            ? json_decode($disk->get(self::PATH), true, flags: JSON_THROW_ON_ERROR) : null;
    }

    public static function write(array $report): void
    {
        $content = json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $disk = Storage::disk('local');
        if (! $disk->put(self::PATH, $content, 'private') || $disk->get(self::PATH) !== $content) {
            throw new \RuntimeException('Anket işlem raporu doğrulanamadı.');
        }
        // Deployment and HTTP run as different users in the same application group.
        // Share only the sanitized status; vote backup files retain owner-only access.
        if (PHP_OS_FAMILY !== 'Windows') {
            if (! chmod(dirname($disk->path(self::PATH)), 0750) || ! chmod($disk->path(self::PATH), 0640)) {
                throw new \RuntimeException('Anket durum raporu uygulama grubuyla paylaşılamadı.');
            }
        }
    }

    public static function publicStatus(): ?array
    {
        $report = self::read();
        return $report ? array_intersect_key($report, array_flip([
            'match_count', 'poll_id', 'title', 'question', 'status', 'starts_at', 'ends_at', 'backup_verified',
            'reset_completed', 'period', 'test_votes_removed',
        ])) : null;
    }
}

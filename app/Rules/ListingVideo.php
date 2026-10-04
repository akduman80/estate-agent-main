<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ListingVideo implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('Geçerli bir video dosyası yükleyin.');

            return;
        }

        if (! in_array($value->getMimeType(), config('listings.video.mime_types'), true)) {
            $fail('Video MP4 (H.264) veya WebM (VP8/VP9) formatında olmalıdır.');

            return;
        }

        try {
            $binary = config('listings.video.ffprobe_binary');
            if (! str_contains($binary, '/') && ! str_contains($binary, '\\')) {
                $binary = (new ExecutableFinder)->find($binary, $binary, ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin']);
            }
            $process = new Process([
                $binary,
                '-v', 'error', '-protocol_whitelist', 'file,pipe',
                '-show_entries', 'format=duration:stream=codec_type,codec_name,duration',
                '-of', 'json', $value->getRealPath(),
            ]);
            $process->setTimeout(15);
            $process->mustRun();
            $metadata = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            report($exception);
            $fail('Video süresi doğrulanamadı. Geçerli bir MP4/WebM dosyası seçin; sorun sürerse yöneticiye bildirin.');

            return;
        }

        $videoStreams = collect($metadata['streams'] ?? [])->where('codec_type', 'video');
        $codecs = $value->getMimeType() === 'video/mp4' ? ['h264'] : ['vp8', 'vp9'];

        if ($videoStreams->isEmpty() || $videoStreams->contains(fn (array $stream): bool => ! in_array($stream['codec_name'] ?? null, $codecs, true))) {
            $fail('Tarayıcı uyumluluğu için MP4 (H.264) veya WebM (VP8/VP9) video seçin.');

            return;
        }

        $duration = $metadata['format']['duration'] ?? null;
        if (! is_numeric($duration) || ! is_finite((float) $duration) || (float) $duration <= 0) {
            $fail('Video süresi okunamadı. Başka bir MP4/WebM dosyası seçin.');

            return;
        }

        $duration = max((float) $duration, ...collect($metadata['streams'] ?? [])
            ->pluck('duration')->filter(fn ($duration): bool => is_numeric($duration))->map(fn ($duration): float => (float) $duration)->all());

        if ($duration > config('listings.video.max_duration_seconds')) {
            $fail('Video en fazla 60 saniye olabilir. Videoyu kısaltıp tekrar yükleyin.');
        }
    }
}

<?php

namespace App\Rules;

use App\Services\NewsVideo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class NewsMediaFile implements ValidationRule
{
    public function __construct(private ?string $existing = null, private string $kind = 'video') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            if (! $this->existing || ! hash_equals($this->existing, $value)) {
                $fail('Yalnız bu habere ait dosyayı kullanın veya yeni dosya yükleyin.');
            }

            return;
        }
        if (! $value instanceof UploadedFile) {
            $fail('Geçerli bir dosya yükleyin.');

            return;
        }
        if ($this->kind === 'captions') {
            if ($value->getSize() > 1024 * 1024 || strtolower($value->getClientOriginalExtension()) !== 'vtt') {
                $fail('En fazla 1 MB, geçerli bir WebVTT (.vtt) altyazı dosyası seçin.');

                return;
            }
            $content = file_get_contents($value->getRealPath());
            if (! preg_match('/^(?:\xEF\xBB\xBF)?WEBVTT(?:\r?\n|[ \t])/', $content) || preg_match('/<\s*(?:script|iframe)|<\?php/i', $content)) {
                $fail('En fazla 1 MB, geçerli bir WebVTT (.vtt) altyazı dosyası seçin.');
            }

            return;
        }
        if (! in_array(strtolower($value->getClientOriginalExtension()), ['mp4', 'mov', 'webm', 'mkv', 'm4v'], true) || ! in_array($value->getMimeType(), config('news_video.mime_types'), true)
            || $value->getSize() > config('news_video.max_upload_mb') * 1024 * 1024) {
            $fail('Boyut sınırına uygun MP4, MOV, WebM, MKV veya M4V video yükleyin.');

            return;
        }
        try {
            app(NewsVideo::class)->probe($value->getRealPath());
        } catch (\Throwable $e) {
            report($e);
            $fail('Video doğrulanamadı. FFprobe kullanılabilir olmalı; dosya geçerli ve süre/çözünürlük sınırlarına uygun olmalıdır.');
        }
    }
}

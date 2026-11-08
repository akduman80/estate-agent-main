<?php

namespace App\Services;

use App\Models\Listing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ListingSocialPreview
{
    public function url(string $route, array $parameters): string
    {
        $origin = rtrim(config('app.url'), '/');
        if (app()->environment('production')) {
            $origin = preg_replace('~^http:~', 'https:', $origin);
        }

        return $origin.route($route, $parameters, false);
    }

    public function description(Listing $listing): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('~</(?:p|div|li)>|<br\s*/?>~i', ' ', $listing->description ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(Str::squish($text) ?: $listing->title, 180);
    }

    public function image(Listing $listing): array
    {
        // Read only the configured storage disk, never arbitrary user-provided URLs.
        // Content hashing also detects replacements made at the same storage key.
        foreach ($listing->images->sortBy(fn ($image) => [! (bool) $image->is_main, (int) $image->sort_order, $image->id]) as $image) {
            $path = $image->image_path;
            if (! $path || str_contains($path, '..') || str_contains($path, '://') || str_contains($path, '\\') || str_contains($path, "\0") || str_starts_with($path, '/')) {
                continue;
            }
            try {
                $disk = Storage::disk(config('listings.social_disk', 'public'));
                $stream = $disk->readStream($path);
                if (! is_resource($stream)) {
                    continue;
                }
                try {
                    $bytes = stream_get_contents($stream, 20 * 1024 * 1024 + 1);
                } finally {
                    fclose($stream);
                }
                if (! is_string($bytes) || strlen($bytes) > 20 * 1024 * 1024) {
                    continue;
                }

                return $this->render($listing, $bytes, true);
            } catch (Throwable $exception) {
                // A removed, unreadable or corrupt cover must not break the listing.
                report($exception);
            }
        }

        return $this->render($listing, file_get_contents(public_path('fe-assets/assets/img/zydemlak-logo.png')));
    }

    private function render(Listing $listing, string $bytes, bool $watermark = false): array
    {
        $dimensions = @getimagesizefromstring($bytes);
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 24000000) {
            throw new RuntimeException('Invalid social preview source image.');
        }
        $logoBytes = file_get_contents(public_path('fe-assets/assets/img/zydemlak-logo-transparent.png'));
        $version = hash('sha256', 'social-v2:'.$listing->getKey().':'.$bytes.':'.$logoBytes.':'.(int) $watermark);
        $jpeg = Cache::remember('listing-social:'.$version, now()->addDay(), function () use ($bytes, $logoBytes, $watermark) {
            $source = @imagecreatefromstring($bytes);
            if (! $source) {
                throw new RuntimeException('Cannot decode social preview source.');
            }
            $canvas = imagecreatetruecolor(1200, 630);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            $scale = min(1200 / imagesx($source), 630 / imagesy($source));
            $width = (int) round(imagesx($source) * $scale);
            $height = (int) round(imagesy($source) * $scale);
            imagecopyresampled($canvas, $source, (int) ((1200 - $width) / 2), (int) ((630 - $height) / 2), 0, 0, $width, $height, imagesx($source), imagesy($source));
            if ($watermark) {
                // Keep the existing faint, centered brand protection on this new public copy.
                $logo = imagecreatefromstring($logoBytes);
                $scale = min($width * .48 / imagesx($logo), $height * .40 / imagesy($logo));
                $markWidth = max(1, (int) round(imagesx($logo) * $scale));
                $markHeight = max(1, (int) round(imagesy($logo) * $scale));
                $mark = imagecreatetruecolor($markWidth, $markHeight);
                imagealphablending($mark, false);
                imagesavealpha($mark, true);
                imagecopyresampled($mark, $logo, 0, 0, 0, 0, $markWidth, $markHeight, imagesx($logo), imagesy($logo));
                for ($y = 0; $y < $markHeight; $y++) {
                    for ($x = 0; $x < $markWidth; $x++) {
                        $color = imagecolorsforindex($mark, imagecolorat($mark, $x, $y));
                        $luminance = .299 * $color['red'] + .587 * $color['green'] + .114 * $color['blue'];
                        $neutral = max($color['red'], $color['green'], $color['blue']) - min($color['red'], $color['green'], $color['blue']) <= 35;
                        $background = $neutral ? max(0, min(1, ($luminance - 185) / 55)) : 0;
                        $alpha = (int) round(127 - (127 - $color['alpha']) * (1 - $background) * .24);
                        imagesetpixel($mark, $x, $y, imagecolorallocatealpha($mark, $color['red'], $color['green'], $color['blue'], $alpha));
                    }
                }
                imagecopy($canvas, $mark, (int) ((1200 - $markWidth) / 2), (int) ((630 - $markHeight) / 2), 0, 0, $markWidth, $markHeight);
                imagedestroy($mark);
                imagedestroy($logo);
            }
            ob_start();
            imagejpeg($canvas, null, 85);
            $result = ob_get_clean();
            imagedestroy($source);
            imagedestroy($canvas);

            return $result;
        });

        return [
            'bytes' => $jpeg,
            'version' => $version,
            'url' => $this->url('listings.social-image', ['listing' => $listing->slug, 'version' => $version]),
        ];
    }
}

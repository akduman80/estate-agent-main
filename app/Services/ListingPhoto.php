<?php

namespace App\Services;

use App\Models\ListingImage;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/** Presentation copies only; never overwrites source media. */
class ListingPhoto
{
    public const VERSION = 'center-logo-v2';

    public static function url(ListingImage $image): string
    {
        return route('listing.photo', ['image' => $image->getKey(), 'v' => self::VERSION], false);
    }

    public function cached(ListingImage $image): string
    {
        $path = $image->image_path;
        abort_unless(is_string($path) && ! str_contains($path, '..') && ! str_contains($path, '\\')
            && ! str_starts_with($path, '/') && ! str_contains($path, "\0"), 404);
        $logoUrl = app(SiteLogoService::class)->url('fe-assets/assets/img/zydemlak-logo-transparent.png');
        $key = hash('sha256', $logoUrl.'|'.self::VERSION.'|'.$image->getKey().'|'.$path.'|'.$image->updated_at);
        $directory = storage_path('app/private/listing-photos');
        if (! is_dir($directory) && ! @mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Photo cache directory unavailable.');
        }
        $target = $directory.'/'.$key.'.webp';
        $lock = fopen($directory.'/'.$key.'.lock', 'c');
        if (! $lock) {
            throw new \RuntimeException('Photo cache lock unavailable.');
        }
        try {
            flock($lock, LOCK_EX);
            if (is_file($target)) {
                return $target;
            }
            try {
                $stream = Storage::disk('public')->readStream($path);
            } catch (\League\Flysystem\FilesystemException) {
                abort(404);
            }
            abort_unless(is_resource($stream), 404);
            try {
                $bytes = stream_get_contents($stream, 25 * 1024 * 1024 + 1);
            } finally {
                fclose($stream);
            }
            abort_unless(is_string($bytes) && strlen($bytes) <= 25 * 1024 * 1024, 404);
            $size = @getimagesizefromstring($bytes);
            abort_unless($size && $size[0] * $size[1] <= 24000000, 422);
            $picture = (new ImageManager(new Driver()))->read($bytes)->scaleDown(width: 1920, height: 1920);
            $canvas = $picture->core()->native();
            $width = $picture->width();
            $height = $picture->height();
            $logoPath = rawurldecode(parse_url($logoUrl, PHP_URL_PATH));
            $logoBytes = str_starts_with($logoPath, '/storage/')
                ? Storage::disk('public')->get(substr($logoPath, 9))
                : file_get_contents(public_path(ltrim($logoPath, '/')));
            $logo = (new ImageManager(new Driver()))->read($logoBytes)
                ->scaleDown(width: max(1, (int) ($width * .48)), height: max(1, (int) ($height * .40)));
            $mark = $logo->core()->native();
            imagealphablending($mark, false);
            imagesavealpha($mark, true);
            // Remove pale neutral logo background, then retain 24% of the remaining opacity.
            for ($y = 0; $y < $logo->height(); $y++) {
                for ($x = 0; $x < $logo->width(); $x++) {
                    $c = imagecolorsforindex($mark, imagecolorat($mark, $x, $y));
                    $luminance = .299 * $c['red'] + .587 * $c['green'] + .114 * $c['blue'];
                    $neutral = max($c['red'], $c['green'], $c['blue']) - min($c['red'], $c['green'], $c['blue']) <= 35;
                    $background = $neutral ? max(0, min(1, ($luminance - 185) / 55)) : 0;
                    $alpha = (int) round(127 - (127 - $c['alpha']) * (1 - $background) * .24);
                    imagesetpixel($mark, $x, $y, imagecolorallocatealpha($mark, $c['red'], $c['green'], $c['blue'], $alpha));
                }
            }
            imagealphablending($canvas, true);
            imagecopy($canvas, $mark, (int) (($width - $logo->width()) / 2), (int) (($height - $logo->height()) / 2), 0, 0, $logo->width(), $logo->height());
            $temporary = tempnam($directory, 'photo-');
            try {
                $picture->toWebp(82)->save($temporary);
                if (! rename($temporary, $target)) {
                    throw new \RuntimeException('Photo cache write failed.');
                }
            } finally {
                if (is_file($temporary)) { unlink($temporary); }
            }
            return $target;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

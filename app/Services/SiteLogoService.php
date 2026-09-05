<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteLogoService
{
    public function url(string $fallback): string
    {
        $path = Setting::get('site_logo');

        if (! is_string($path) || blank($path) || ! Storage::disk('public')->exists($path)) {
            return $this->versionedAsset($fallback);
        }

        $displayPath = $this->transparentCopy($path);
        $url = Storage::disk('public')->url($displayPath);

        return $this->withVersion($url, Storage::disk('public')->lastModified($displayPath));
    }

    private function transparentCopy(string $path): string
    {
        $disk = Storage::disk('public');
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        if (! function_exists('imagecreatefromstring') || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $path;
        }

        $fingerprint = sha1($path.'|'.$disk->size($path).'|'.$disk->lastModified($path));
        $processedPath = "site/processed/{$fingerprint}.png";

        if ($disk->exists($processedPath)) {
            return $processedPath;
        }

        $contents = $disk->get($path);
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return $path;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $result = imagecreatetruecolor($width, $height);

        imagealphablending($result, false);
        imagesavealpha($result, true);
        imagefill($result, 0, 0, imagecolorallocatealpha($result, 0, 0, 0, 127));

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $sourceAlpha = ($rgba >> 24) & 0x7F;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                $chroma = max($red, $green, $blue) - min($red, $green, $blue);
                $luminance = (int) round(($red * 0.299) + ($green * 0.587) + ($blue * 0.114));
                $backgroundAlpha = 0;

                // Açık ve nötr tonları yumuşak biçimde şeffaflaştırır. Renkli logo
                // çizgileri korunur; beyaz detaylar sayfadaki beyaz zeminden görünür.
                if ($chroma <= 35 && $luminance >= 185) {
                    $backgroundAlpha = (int) min(127, round(127 * (($luminance - 185) / 55)));
                }

                $alpha = max($sourceAlpha, $backgroundAlpha);
                imagesetpixel($result, $x, $y, imagecolorallocatealpha($result, $red, $green, $blue, $alpha));
            }
        }

        ob_start();
        imagepng($result, null, 9);
        $png = ob_get_clean();

        imagedestroy($source);
        imagedestroy($result);

        if (! is_string($png)) {
            return $path;
        }

        $disk->put($processedPath, $png, 'public');

        return $processedPath;
    }

    private function versionedAsset(string $path): string
    {
        $absolutePath = public_path(ltrim($path, '/'));
        $version = is_file($absolutePath) ? filemtime($absolutePath) : null;

        return $this->withVersion(asset($path), $version);
    }

    private function withVersion(string $url, int|false|null $version): string
    {
        if (! $version) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
    }
}

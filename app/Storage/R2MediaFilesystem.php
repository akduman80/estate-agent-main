<?php

namespace App\Storage;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\URL;

class R2MediaFilesystem extends FilesystemAdapter
{
    public function url($path)
    {
        if ($this->adapter->isRemote($path)) {
            return static::mediaUrl($path);
        }

        return '/storage/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    public static function mediaUrl(string $path): string
    {
        return URL::temporarySignedRoute('r2.media', now()->addHour(), ['path' => $path], absolute: false);
    }
}

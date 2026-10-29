<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;

/** Keep local public previews on the panel origin; private/cloud URLs stay signed. */
class PublicMediaUpload extends FileUpload
{
    public function image(): static
    {
        parent::image();

        return $this->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(15360)
            ->maxParallelUploads(2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $original = $this->getUploadedFileUsing;
        $this->getUploadedFileUsing(static function (BaseFileUpload $component, string $file, string | array | null $storedFileNames) use ($original): ?array {
            $information = $component->evaluate($original, [
                'file' => $file,
                'storedFileNames' => $storedFileNames,
            ]);

            if ($information !== null && $component->getDiskName() === 'public'
                && $component->getVisibility() === 'public'
                && config('filesystems.disks.public.driver') === 'local') {
                $information['url'] = '/storage/'.implode('/', array_map('rawurlencode', explode('/', $file)));
            }

            return $information;
        });
    }
}

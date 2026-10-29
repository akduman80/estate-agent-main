<?php

namespace App\Filament\Forms;

use App\Rules\NewsMediaFile;
use Filament\Forms\Components\FileUpload;

class NewsMediaUpload extends FileUpload
{
    protected string $mediaKey = 'source';

    public function mediaKey(string $key): static
    {
        $this->mediaKey = $key;

        return $this;
    }

    public function getValidationRules(): array
    {
        // Filament validates newly uploaded files only. Validate retained string paths too.
        return [...parent::getValidationRules(), function (string $attribute, array $value, \Closure $fail): void {
            $existing = $this->getRecord()?->news_media[$this->mediaKey] ?? null;
            foreach ($value as $file) {
                if (is_string($file)) {
                    (new NewsMediaFile($existing, $this->mediaKey === 'captions' ? 'captions' : 'video'))->validate($attribute, $file, $fail);
                }
            }
        }];
    }
}

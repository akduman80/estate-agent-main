<?php

namespace App\Jobs;

use App\Models\ListingImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessListingImage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tempPath,
        public int $listingId,
        public bool $isMain = false
    ) {}

    public function handle(): void
    {
        // $this-> ile property'lere erişiyoruz
        $fullPath = Storage::disk('public')->path($this->tempPath);

        if (!file_exists($fullPath)) {
            return;
        }

        // Intervention Image V3
        $manager = new ImageManager(new Driver());
        $image = $manager->read($fullPath);

        // Maksimum genişliği 1920px yap, oranları koru
        $image->scale(width: 1920);

        // Yeni webp dosya adı ve yolu
        $filename = 'listings/' . uniqid() . '.webp';
        $newFullPath = Storage::disk('public')->path($filename);

        // WebP olarak %80 kalitede kaydet
        $image->toWebp(80)->save($newFullPath);

        // Veritabanına kaydet
        ListingImage::create([
            'listing_id' => $this->listingId,
            'image_path' => $filename,
            'is_main'    => $this->isMain,
        ]);

        // Geçici yüklenen ham (15MB) görseli sil
        Storage::disk('public')->delete($this->tempPath);
    }
}

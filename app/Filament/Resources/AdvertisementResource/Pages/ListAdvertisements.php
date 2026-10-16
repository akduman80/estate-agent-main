<?php

namespace App\Filament\Resources\AdvertisementResource\Pages;

use App\Filament\Resources\AdvertisementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected static string $view = 'filament.resources.advertisements.pages.list-advertisements';

    public function mount(): void
    {
        parent::mount(); // Authorize the resource before any import or cleanup.

        if (Artisan::call('advertisements:prepare-home') !== 0) {
            throw new RuntimeException('Reklam aktarımı tamamlanamadı. Mevcut kayıtlar korunuyor; lütfen tekrar deneyin.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

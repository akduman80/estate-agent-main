<?php

namespace App\Filament\Resources\AdvertisementResource\Pages;

use App\Filament\Resources\AdvertisementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected static string $view = 'filament.resources.advertisements.pages.list-advertisements';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

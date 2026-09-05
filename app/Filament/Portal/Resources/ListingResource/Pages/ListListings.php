<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Filament\Portal\Resources\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListings extends ListRecords
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn (): bool => ListingResource::canCreate()),
        ];
    }
}

<?php

namespace App\Filament\Resources\RentPeriodRateResource\Pages;

use App\Filament\Resources\RentPeriodRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentPeriodRates extends ListRecords
{
    protected static string $resource = RentPeriodRateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Portal\Resources\ConsultantResource\Pages;

use App\Filament\Portal\Resources\ConsultantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultants extends ListRecords
{
    protected static string $resource = ConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Danışman Ekle')];
    }
}

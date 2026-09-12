<?php

namespace App\Filament\Resources\AgentConsultantResource\Pages;

use App\Filament\Resources\AgentConsultantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgentConsultants extends ListRecords
{
    protected static string $resource = AgentConsultantResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Danışman Ata')];
    }
}

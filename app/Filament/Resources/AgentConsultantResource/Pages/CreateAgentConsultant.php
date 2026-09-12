<?php

namespace App\Filament\Resources\AgentConsultantResource\Pages;

use App\Filament\Resources\AgentConsultantResource;
use App\Models\User;
use App\Services\AgentConsultantService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAgentConsultant extends CreateRecord
{
    protected static string $resource = AgentConsultantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AgentConsultantService::class)->assign(
            User::findOrFail($data['agent_id']),
            User::findOrFail($data['consultant_id']),
        );
    }
}

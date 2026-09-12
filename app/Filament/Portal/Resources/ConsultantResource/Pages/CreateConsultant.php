<?php

namespace App\Filament\Portal\Resources\ConsultantResource\Pages;

use App\Filament\Portal\Resources\ConsultantResource;
use App\Services\AgentConsultantService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateConsultant extends CreateRecord
{
    protected static string $resource = ConsultantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AgentConsultantService::class)->create(auth()->user(), $data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Danışman sisteme eklendi');
    }
}

<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Support\ProjectMapTransfer;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(): void
    {
        parent::mount();
        if (session()->has('project-map-transfer.project')) {
            $this->form->fill(ProjectMapTransfer::apply($this->data ?? [], 'project'));
        }
    }
}

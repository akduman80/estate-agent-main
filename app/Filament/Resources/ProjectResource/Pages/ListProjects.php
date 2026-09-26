<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('installProjectsApp')
                ->label("Projeler App'ini Telefona Yükle")
                ->icon('heroicon-o-arrow-down-on-square')
                ->color('success')
                ->url(fn (): string => ProjectResource::getUrl(panel: 'projects').'?install=1')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PollResource\Pages;

use App\Filament\Resources\PollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPoll extends EditRecord
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('Anketi Kapat')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'active')
                ->action(function (): void {
                    $this->record->update(['status' => 'closed']);
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}

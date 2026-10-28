<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Filament\Portal\Resources\ListingResource;
use App\Services\ListingPublicationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditListing extends EditRecord
{
    use Concerns\HasListingWorkspace;

    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = ListingResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->startWorkspace();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'form_details' => $this->record->form_details, 'private_note' => $this->record->private_note];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->validatedWorkspaceData([...$this->record->attributesToArray(), 'form_details' => $this->record->form_details, ...$data]);
    }

    protected function beforeValidate(): void
    {
        ListingResource::getEloquentQuery()->whereKey($this->record->id)->lockForUpdate()->firstOrFail();
        $this->assertWorkspaceUnchanged();
    }

    protected function afterSave(): void
    {
        $this->recordStamp = $this->listingStamp($this->record->fresh());
    }

    protected function getFormActions(): array
    {
        return [$this->getSaveFormAction()->label('Değişiklikleri Kaydet'), ...$this->workspaceActions()];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('unpublishListing')
                ->label('İlanı yayından kaldır')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->requiresConfirmation()
                ->disabled(fn (): bool => ! ($this->record->is_active && $this->record->publication_status === 'approved'))
                ->action(function (): void {
                    app(ListingPublicationService::class)->unpublish($this->record, auth()->user());
                    Notification::make()->warning()->title('İlan yayından kaldırıldı')->send();
                    $this->redirect(ListingResource::getUrl('index'));
                }),
            Actions\DeleteAction::make()
                ->label('İlanı kaldır')
                ->modalHeading('İlan kalıcı olarak silinsin mi?')
                ->modalDescription('Bu işlem geri alınamaz. İlan kalıcı olarak silinecek.'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->record?->eids_exempt) {
            return Notification::make()->success()->title('İlan kaydedildi')
                ->body('Tapusuz ilan için EİDS onayı gerekmiyor. İlanı yönetici onayına gönderebilirsiniz.');
        }

        if ($this->record?->eids_status === 'verified') {
            return Notification::make()
                ->success()
                ->title('İlan kaydedildi')
                ->body('EİDS onayı mevcut. Yayın için ilanı yönetici onayına gönderin.');
        }

        return Notification::make()
            ->warning()
            ->title('İlan taslakta kalacak')
            ->body('Bu ilan EİDS onayı almadığı için taslaklarda duracaktır ve sitede yayınlanmayacaktır.');
    }
}

<?php

namespace App\Filament\Portal\Resources\ListingResource\Pages;

use App\Filament\Portal\Resources\ListingResource;
use App\Services\ListingPublicationService;
use Filament\Actions;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditListing extends EditRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submitForApproval')
                ->label('İlanı Onaya Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->publication_status, ['draft', 'rejected'], true))
                ->form([
                    Checkbox::make('terms')
                        ->label('Taşınmaz numarası, konum, ada/parsel, kategori, ilan tipi ve ilan kaynağını kontrol ettim; EİDS ile doğrulanan resmî alanların yayınlandıktan sonra değiştirilemeyeceğini kabul ediyorum.')
                        ->accepted()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Yayın öncesi son kontrol')
                ->modalDescription(fn (): string => sprintf(
                    'Taşınmaz: %s | İl/İlçe: %s / %s | Ada/Parsel: %s / %s | Kategori: %s | Tip: %s | Kaynak: %s',
                    $this->record->eids_document_no,
                    $this->record->city_name,
                    $this->record->district_name,
                    $this->record->eidsVerification?->block ?: $this->record->block_number,
                    $this->record->eidsVerification?->parcel ?: $this->record->parcel_number,
                    $this->record->category,
                    $this->record->type,
                    $this->record->source_label,
                ))
                ->action(function (array $data): void {
                    try {
                        // Yayınlama isteği, formdaki son değişiklikleri de kapsasın.
                        $this->save(false, false);
                        $this->record->refresh()->load('eidsVerification');
                        app(ListingPublicationService::class)->submit($this->record, auth()->user(), (bool) ($data['terms'] ?? false));
                        Notification::make()->success()->title('İlan yönetici onayına gönderildi')->send();
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->warning()
                            ->title('İlan taslakta kaldı')
                            ->body(implode(' ', $exception->errors()['eids'] ?? $exception->errors()['listing'] ?? ['İlan EİDS onayı alınamadığı için onaya gönderilemedi.']))
                            ->send();
                    }

                    $this->refreshFormData(['publication_status', 'submitted_at', 'is_active', 'eids_status']);
                }),
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

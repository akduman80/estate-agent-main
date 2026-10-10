<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\MobileDraftImport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MobileDraftImportResource extends Resource
{
    protected static ?string $model = MobileDraftImport::class;
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Telefondan gelen taslaklar';
    protected static ?string $modelLabel = 'Mobil taslak';
    protected static ?string $pluralModelLabel = 'Mobil taslaklar';

    public static function canViewAny(): bool { return auth()->user()?->role === UserRole::SUPERVISOR; }
    public static function canCreate(): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('payload.adSoyad')->label('Kayıt'),
            Tables\Columns\TextColumn::make('kind')->label('Tür')->badge(),
            Tables\Columns\IconColumn::make('pending_update')->label('Güncelleme bekliyor')->boolean(),
            Tables\Columns\TextColumn::make('user.name')->label('Hesap'),
            Tables\Columns\TextColumn::make('updated_at')->label('Aktarım zamanı')->dateTime()->sortable(),
        ])->defaultSort('updated_at', 'desc')->actions([
            Tables\Actions\Action::make('uygula')->label('Seçimleri mevcut kayda uygula')->requiresConfirmation()
                ->visible(fn (MobileDraftImport $record) => $record->pending_update || collect($record->media)->contains('disk', 'local'))
                ->modalDescription('Yeni kayıt açılmaz. EİDS kilitli alanları değiştirilemez; sitenin mevcut kuralları uygulanır.')
                ->action(function (MobileDraftImport $record): void {
                    try {
                        app(\App\Http\Controllers\MobileDraftController::class)->applyUpdate($record);
                        \Filament\Notifications\Notification::make()->success()->title('Mevcut kayıt güncellendi')->send();
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        \Filament\Notifications\Notification::make()->danger()->title('Güncelleme uygulanmadı')
                            ->body(collect($e->errors())->flatten()->join(' '))->send();
                    }
                }),
            Tables\Actions\Action::make('incele')->label('Bilgiler ve medya')->icon('heroicon-o-eye')
                ->modalContent(fn (MobileDraftImport $record) => view('filament.mobile-draft-details', ['record' => $record]))
                ->modalSubmitAction(false)->modalCancelActionLabel('Kapat'),
            Tables\Actions\Action::make('duzenle')->label('Web panelinde düzenle')
                ->url(fn (MobileDraftImport $record) => $record->kind === 'ilan'
                    ? '/portal/listings/'.$record->listing_id.'/edit' : '/admin/projects/'.$record->project_id.'/edit'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Resources\MobileDraftImportResource\Pages\ListMobileDraftImports::route('/')];
    }
}

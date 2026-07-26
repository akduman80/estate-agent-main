<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Models\Listing;
use App\Services\EidsService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tüm İlanlar';

    protected static ?string $modelLabel = 'İlan';

    protected static ?string $pluralModelLabel = 'İlanlar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('title')
                                 ->label('İlan Başlığı')
                                 ->required(),

                        Select::make('user_id')
                              ->label('İlan Sahibi (Emlakçı)')
                              ->relationship('user', 'name')
                              ->searchable()
                              ->required(),

                        Select::make('type')
                              ->label('İlan Tipi')
                              ->options([
                                  'sale' => 'Satılık',
                                  'rent' => 'Kiralık',
                              ])
                              ->required(),

                        TextInput::make('price')
                                 ->label('Fiyat (TL)')
                                 ->numeric()
                                 ->required(),

                        Select::make('eids_status')
                              ->label('EİDS Durumu')
                              ->options([
                                  'pending'  => 'Beklemede',
                                  'verified' => 'Doğrulandı',
                                  'rejected' => 'Reddedildi',
                              ])
                              ->required(),

                        TextInput::make('eids_document_no')
                                 ->label('EİDS Belge / Taşınmaz No'),
                    ]),

                RichEditor::make('description')
                          ->label('Açıklama')
                          ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                          ->label('İlan Başlığı')
                          ->searchable()
                          ->sortable(),

                TextColumn::make('user.name')
                          ->label('Emlakçı')
                          ->searchable(),

                TextColumn::make('price')
                          ->label('Fiyat')
                          ->money('TRY')
                          ->sortable(),

                TextColumn::make('eids_status')
                          ->label('EİDS Durumu')
                          ->badge()
                          ->color(fn (string $state): string => match ($state) {
                              'verified' => 'success',
                              'pending'  => 'warning',
                              'rejected' => 'danger',
                              default    => 'gray',
                          }),

                TextColumn::make('created_at')
                          ->label('Tarih')
                          ->dateTime('d.m.Y H:i')
                          ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('eids_status')
                                           ->label('EİDS Durumuna Göre Filtrele')
                                           ->options([
                                               'verified' => 'Doğrulanmış',
                                               'pending'  => 'Bekleyen',
                                               'rejected' => 'Reddedilen',
                                           ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('reverify_eids')
                      ->label('EİDS Sorgula')
                      ->icon('heroicon-o-arrow-path')
                      ->color('warning')
                      ->action(function (Listing $record) {
                          app(EidsService::class)->verifyListing($record);

                          Notification::make()
                                      ->title('EİDS Durumu Güncellendi')
                                      ->success()
                                      ->send();
                      }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit'   => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}

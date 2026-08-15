<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingEidsRequestLogResource\Pages;
use App\Models\ListingEidsRequestLog;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingEidsRequestLogResource extends Resource
{
    protected static ?string $model = ListingEidsRequestLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationGroup = 'EİDS';

    protected static ?string $navigationLabel = 'Sorgu Kayıtları';

    protected static ?string $modelLabel = 'EİDS Sorgu Kaydı';

    protected static ?string $pluralModelLabel = 'EİDS Sorgu Kayıtları';

    protected static ?string $breadcrumb = 'EİDS Sorgu Kayıtları';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('attempted_at', 'desc')
            ->columns([
                TextColumn::make('attempted_at')
                    ->label('Sorgu Zamanı')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('request_payload.TasinmazId')
                    ->label('Taşınmaz ID')
                    ->searchable(),
                TextColumn::make('request_payload.IlanNo')
                    ->label('İlan No')
                    ->searchable(),
                TextColumn::make('result_status')
                    ->label('Sonuç')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'verified' => 'Doğrulandı',
                        'rejected' => 'Reddedildi',
                        'pending' => 'Beklemede',
                        'error' => 'Hata',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'rejected', 'error' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('response_status')
                    ->label('HTTP')
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->label('Süre')
                    ->suffix(' ms')
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Hata')
                    ->limit(45)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('result_status')
                    ->label('Sonuç')
                    ->options([
                        'verified' => 'Doğrulandı',
                        'rejected' => 'Reddedildi',
                        'pending' => 'Beklemede',
                        'error' => 'Hata',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Genel')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('attempted_at')->label('Sorgu Zamanı')->dateTime('d.m.Y H:i:s'),
                        TextEntry::make('listing.title')->label('İlan'),
                        TextEntry::make('endpoint')->label('Adres')->columnSpanFull(),
                        TextEntry::make('method')->label('Metot'),
                        TextEntry::make('response_status')->label('HTTP Durumu'),
                        TextEntry::make('duration_ms')->label('Süre')->suffix(' ms'),
                        TextEntry::make('result_status')
                            ->label('Sonuç')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'verified' => 'Doğrulandı',
                                'rejected' => 'Reddedildi',
                                'pending' => 'Beklemede',
                                'error' => 'Hata',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'verified' => 'success',
                                'rejected', 'error' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('error_message')->label('Hata Mesajı')->columnSpanFull(),
                    ]),
                Section::make('Gönderilen Bilgiler')
                    ->schema([
                        TextEntry::make('request_payload_json')
                            ->label('Request')
                            ->state(fn (ListingEidsRequestLog $record): string => self::formatPayload($record->request_payload))
                            ->fontFamily(FontFamily::Mono)
                            ->extraAttributes(['class' => 'whitespace-pre-wrap break-words'])
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Dönen Cevap')
                    ->schema([
                        TextEntry::make('response_payload_json')
                            ->label('Response')
                            ->state(fn (ListingEidsRequestLog $record): string => self::formatPayload($record->response_payload))
                            ->fontFamily(FontFamily::Mono)
                            ->extraAttributes(['class' => 'whitespace-pre-wrap break-words'])
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function formatPayload(mixed $payload): string
    {
        if ($payload === null || $payload === []) {
            return '-';
        }

        if (is_string($payload)) {
            $decodedPayload = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decodedPayload;
            } else {
                return $payload;
            }
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: print_r($payload, true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('listing');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListingEidsRequestLogs::route('/'),
            'view' => Pages\ViewListingEidsRequestLog::route('/{record}'),
        ];
    }
}

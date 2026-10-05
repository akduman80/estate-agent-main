<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingEidsRequestLogResource\Pages;
use App\Models\ListingEidsRequestLog;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
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
                TextColumn::make('operation')->label('İşlem')
                    ->getStateUsing(fn (ListingEidsRequestLog $record): string => data_get($record->response_payload, 'stage') === 'callback_session_check' ? 'EİDS Dönüş / Oturum Hatası' : (array_key_exists('yetkiKodu', $record->request_payload ?? []) ? 'Kullanıcı Kodu Alma' : 'İlan Yetki Sorgusu')),
                TextColumn::make('owner_name')->label('Hesap')->searchable()->placeholder('-'),
                TextColumn::make('business_office_name')->label('Emlak İşletmesi')->searchable()->placeholder('-')->toggleable(),
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
                TextColumn::make('eids_identity_context')
                    ->label('EİDS Bağlamı')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'zydemlak_platform_company' => 'ZYDEMLAK / Şirket',
                        'agent_company' => 'Agent / Şirket',
                        'owner_person' => 'Şahıs',
                        'user_account' => 'Kullanıcı',
                        default => $state ?: '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'zydemlak_platform_company' => 'info',
                        'agent_company' => 'warning',
                        'owner_person' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('tax_number_last4')
                    ->label('Vergi Son 4')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('eids_user_code_source')
                    ->label('Kod Kaynağı')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'settings.eids_user_code' => 'Platform Ayarı',
                        'users.eids_user_code' => 'Kullanıcı',
                        'missing' => 'Eksik',
                        default => $state ?: '-',
                    })
                    ->toggleable(),
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
                SelectFilter::make('eids_identity_context')
                    ->label('EİDS Bağlamı')
                    ->options([
                        'zydemlak_platform_company' => 'ZYDEMLAK / Şirket',
                        'agent_company' => 'Agent / Şirket',
                        'owner_person' => 'Şahıs',
                        'user_account' => 'Kullanıcı',
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
                        TextEntry::make('error_message')
                            ->label(fn (ListingEidsRequestLog $record): string => $record->result_status === 'rejected' ? 'Reddetme Nedeni' : 'Hata Mesajı')
                            ->placeholder('EİDS açıklama döndürmedi')
                            ->columnSpanFull()
                            ->color(fn (ListingEidsRequestLog $record): string => $record->result_status === 'rejected' ? 'danger' : 'gray'),
                    ]),
                Section::make('EİDS Kimlik Bağlamı')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('eids_identity_context')
                            ->label('Sorgu Bağlamı')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'zydemlak_platform_company' => 'ZYDEMLAK / Şirket',
                                'agent_company' => 'Agent / Şirket',
                                'owner_person' => 'Şahıs',
                                'user_account' => 'Kullanıcı',
                                default => $state ?: '-',
                            }),
                        TextEntry::make('authorization_subject_type')
                            ->label('Yetki Tipi')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'company' => 'Şirket / Vergi No ile',
                                'person' => 'Şahıs / Vergi No olmadan',
                                default => $state ?: '-',
                            }),
                        TextEntry::make('eids_user_code_source')
                            ->label('Kullanıcı Kodu Kaynağı')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'settings.eids_user_code' => 'Platform ayarı',
                                'users.eids_user_code' => 'Kullanıcı hesabı',
                                'missing' => 'Eksik',
                                default => $state ?: '-',
                            }),
                        TextEntry::make('owner_name')->label('Hesap Sahibi')->placeholder('-'),
                        TextEntry::make('owner_role')->label('Hesap Rolü')->placeholder('-'),
                        TextEntry::make('listing_source_type')->label('İlan Kaynağı')->placeholder('-'),
                        TextEntry::make('business_office_name')->label('Emlak İşletmesi')->placeholder('-'),
                        TextEntry::make('tax_number_last4')->label('Vergi No Son 4')->placeholder('-'),
                        TextEntry::make('eids_user_code_hash')->label('Kullanıcı Kodu Hash')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Gönderilen Bilgiler')
                    ->schema([
                        ViewEntry::make('request_payload_json')
                            ->label('Request')
                            ->state(fn (ListingEidsRequestLog $record): string => self::formatPayload($record->request_payload))
                            ->view('filament.infolists.json-entry')
                            ->columnSpanFull(),
                    ]),
                Section::make('Dönen Cevap')
                    ->schema([
                        ViewEntry::make('response_payload_json')
                            ->label('Response')
                            ->state(fn (ListingEidsRequestLog $record): string => self::formatPayload($record->response_payload, true))
                            ->view('filament.infolists.json-entry')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function formatPayload(mixed $payload, bool $humanizeNulls = false): string
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

        if ($humanizeNulls) {
            $payload = self::replaceNullValues($payload);
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: print_r($payload, true);
    }

    private static function replaceNullValues(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                static fn (mixed $item): mixed => self::replaceNullValues($item),
                $value,
            );
        }

        return $value === null ? 'EİDS veri döndürmedi' : $value;
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

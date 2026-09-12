<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\ErrorLogResource\Pages;
use App\Models\ErrorLog;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Error Logs';
    protected static ?string $modelLabel = 'Hata Kaydı';
    protected static ?string $pluralModelLabel = 'Hata Kayıtları';
    protected static ?string $navigationGroup = 'Sistem';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')->label('Tarih')->dateTime('d.m.Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('level')->label('Seviye')->badge()->color('danger'),
                Tables\Columns\TextColumn::make('message')->label('Hata')->searchable()->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('exception_class')->label('Tür')->searchable()->limit(45)->toggleable(),
                Tables\Columns\TextColumn::make('method')->label('Metot')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('route_name')->label('Rota')->searchable()->limit(35)->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('Kullanıcı')->searchable()->default('-'),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')->label('Seviye')->options([
                    'error' => 'Hata', 'critical' => 'Kritik', 'warning' => 'Uyarı',
                ]),
                Tables\Filters\Filter::make('unresolved')->label('Çözülmemişler')
                    ->query(fn (Builder $query): Builder => $query->whereNull('resolved_at')),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Hata bilgileri')->columns(3)->schema([
                TextEntry::make('occurred_at')->label('Tarih')->dateTime('d.m.Y H:i:s'),
                TextEntry::make('level')->label('Seviye')->badge()->color('danger'),
                TextEntry::make('exception_class')->label('Exception türü')->columnSpan(2),
                TextEntry::make('message')->label('Mesaj')->columnSpanFull(),
                TextEntry::make('file')->label('Dosya')->columnSpanFull(),
                TextEntry::make('line')->label('Satır'),
                TextEntry::make('user.name')->label('Kullanıcı'),
                TextEntry::make('ip_address')->label('IP adresi'),
                TextEntry::make('method')->label('HTTP metodu'),
                TextEntry::make('route_name')->label('Rota'),
                TextEntry::make('url')->label('URL')->columnSpanFull(),
                TextEntry::make('user_agent')->label('User agent')->columnSpanFull(),
            ]),
            Section::make('İstek bağlamı')->schema([
                ViewEntry::make('context_json')->label('Context')
                    ->state(fn (ErrorLog $record): string => self::formatJson($record->context))
                    ->view('filament.infolists.json-entry')->columnSpanFull(),
            ]),
            Section::make('Stack trace')->schema([
                TextEntry::make('trace')->label('')->prose()->copyable()->columnSpanFull(),
            ]),
        ]);
    }

    private static function formatJson(mixed $value): string
    {
        return $value === null ? '-' : (json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListErrorLogs::route('/'), 'view' => Pages\ViewErrorLog::route('/{record}')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

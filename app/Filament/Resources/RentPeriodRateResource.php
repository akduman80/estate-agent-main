<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\RentPeriodRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RentPeriodRateResource extends Resource
{
    protected static ?string $model = RentPeriodRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Kira Dönem Oranları';

    protected static ?string $modelLabel = 'Kira dönem oranı';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('period')->label('Yenileme ayının ilk günü')->required()->maxDate(now()->endOfMonth())->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('rate')->label('TÜFE 12 aylık ortalama (%)')->required()->numeric()->minValue(0)->maxValue(999)->step(0.01),
            Forms\Components\TextInput::make('source_url')->label('Resmî TÜİK kaynak URL')->url()->required()->maxLength(1000)->columnSpanFull(),
            Forms\Components\DatePicker::make('published_on')->label('Resmî yayın tarihi')->required()->maxDate(today()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('period')->label('Yenileme dönemi')->date('m.Y')->sortable(),
            Tables\Columns\TextColumn::make('rate')->label('Oran (%)'),
            Tables\Columns\TextColumn::make('source_url')->label('Kaynak')->limit(55)->url(fn ($record) => $record->source_url)->openUrlInNewTab(),
            Tables\Columns\TextColumn::make('published_on')->label('Yayın tarihi')->date(),
            Tables\Columns\TextColumn::make('updated_at')->label('Son güncelleme')->dateTime(),
        ])->defaultSort('period', 'desc')->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => RentPeriodRateResource\Pages\ListRentPeriodRates::route('/'), 'create' => RentPeriodRateResource\Pages\CreateRentPeriodRate::route('/create'), 'edit' => RentPeriodRateResource\Pages\EditRentPeriodRate::route('/{record}/edit')];
    }
}

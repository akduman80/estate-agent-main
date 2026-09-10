<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ListingChangeLogResource\Pages;
use App\Models\ListingChangeLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListingChangeLogResource extends Resource
{
    protected static ?string $model = ListingChangeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'İlan Değişiklik Geçmişi';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('listing.title')
                ->label('İlan')
                ->searchable()
                ->limit(34)
                ->wrap()
                ->width('330px')
                ->extraCellAttributes(['style' => 'max-width: 330px; overflow-wrap: anywhere;']),
            Tables\Columns\TextColumn::make('user.name')
                ->label('Kullanıcı')
                ->limit(18)
                ->wrap()
                ->width('160px'),
            Tables\Columns\TextColumn::make('field')
                ->label('Alan')
                ->limit(24)
                ->wrap()
                ->width('190px')
                ->extraCellAttributes(['style' => 'max-width: 190px; overflow-wrap: anywhere;']),
            Tables\Columns\TextColumn::make('old_value')
                ->label('Eski Değer')
                ->limit(28)
                ->wrap()
                ->width('200px')
                ->extraCellAttributes(['style' => 'max-width: 200px; overflow-wrap: anywhere;']),
            Tables\Columns\TextColumn::make('new_value')
                ->label('Yeni Değer')
                ->limit(28)
                ->wrap()
                ->width('200px')
                ->extraCellAttributes(['style' => 'max-width: 200px; overflow-wrap: anywhere;']),
            Tables\Columns\TextColumn::make('changed_at')
                ->label('Tarih')
                ->dateTime('d.m.Y H:i:s')
                ->width('160px')
                ->grow(false),
        ])->defaultSort('changed_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListListingChangeLogs::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

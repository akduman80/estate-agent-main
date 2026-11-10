<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\PropertyLike;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyLikeResource extends Resource
{
    protected static ?string $model = PropertyLike::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?string $navigationLabel = 'Haber ve İlan Beğenileri';

    protected static ?string $modelLabel = 'Etkileşim beğenisi';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::SUPERVISOR;
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['user', 'listing', 'post']))->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Üye')->searchable(),
            Tables\Columns\TextColumn::make('listing.title')->label('İlan')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('post.title')->label('Haber')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc')->recordUrl(null)->filters([
            Tables\Filters\Filter::make('listing')->label('İlan beğenileri')->query(fn ($query) => $query->whereNotNull('listing_id')),
            Tables\Filters\Filter::make('post')->label('Haber beğenileri')->query(fn ($query) => $query->whereNotNull('post_id')),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => PropertyLikeResource\Pages\ListPropertyLikes::route('/')];
    }
}

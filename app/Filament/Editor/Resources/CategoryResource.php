<?php

namespace App\Filament\Editor\Resources;

use App\Filament\Editor\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategoriler';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategoriler';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                         ->label('Kategori Adı')
                         ->required()
                         ->maxLength(255)
                         ->live(onBlur: true)
                         ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                         ->label('URL (Slug)')
                         ->required()
                         ->maxLength(255)
                         ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                          ->label('Kategori Adı')
                          ->searchable()
                          ->sortable(),

                TextColumn::make('slug')
                          ->label('URL (Slug)')
                          ->searchable(),

                TextColumn::make('posts_count')
                          ->label('Yazı Sayısı')
                          ->counts('posts')
                          ->sortable(),

                TextColumn::make('created_at')
                          ->label('Oluşturulma Tarihi')
                          ->dateTime('d.m.Y H:i')
                          ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}

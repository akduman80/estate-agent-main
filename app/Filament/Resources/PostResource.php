<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Emlak Haberi';

    protected static ?string $pluralModelLabel = 'Emlak Haberleri';

    protected static ?string $navigationLabel = 'Emlak Haberleri';

    public static function form(Form $form): Form
    {
        return \App\Filament\Editor\Resources\PostResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Editor\Resources\PostResource::table($table);
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}

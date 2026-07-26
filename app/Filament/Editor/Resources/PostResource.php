<?php

namespace App\Filament\Editor\Resources;

use App\Filament\Editor\Resources\PostResource\Pages;
use App\Filament\Editor\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('title')
                                 ->label('Haber / Yazı Başlığı')
                                 ->required()
                                 ->maxLength(255)
                                 ->live(onBlur: true)
                                 ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                                 ->label('URL (Slug)')
                                 ->required()
                                 ->unique(ignoreRecord: true),

                        Select::make('category_id')
                              ->label('Kategori')
                              ->relationship('category', 'name')
                              ->searchable()
                              ->preload()
                              ->required(),

                        Select::make('status')
                              ->label('Yayın Durumu')
                              ->options([
                                  'draft'     => 'Taslak',
                                  'in_review' => 'Editör Onayında',
                                  'published' => 'Yayında',
                              ])
                              ->default('draft')
                              ->required(),
                    ]),

                Textarea::make('summary')
                        ->label('Kısa Özet')
                        ->rows(2)
                        ->columnSpanFull(),

                RichEditor::make('content')
                          ->label('İçerik')
                          ->required()
                          ->columnSpanFull(),

                FileUpload::make('cover_image')
                          ->label('Kapak Görseli')
                          ->image()
                          ->directory('posts')
                          ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('Kapak'),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable(),
                Tables\Columns\TextColumn::make('author.name')->label('Yazar'),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori'),
                Tables\Columns\TextColumn::make('status')
                                         ->label('Durum')
                                         ->badge()
                                         ->color(fn (string $state): string => match ($state) {
                                             'published' => 'success',
                                             'in_review' => 'warning',
                                             'draft'     => 'gray',
                                             default     => 'gray',
                                         }),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}

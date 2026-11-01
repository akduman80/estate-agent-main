<?php

namespace App\Filament\Editor\Resources;

use App\Filament\Editor\Resources\PostResource\Pages;
use App\Filament\Forms\NewsMediaFields;
use App\Models\Post;
use Filament\Forms;
use App\Filament\Forms\Components\PublicMediaUpload as FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Emlak Haberi';

    protected static ?string $pluralModelLabel = 'Emlak Haberleri';

    protected static ?string $navigationLabel = 'Emlak Haberleri';

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
                            ->afterStateUpdated(function ($state, callable $set, string $operation): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

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
                                'draft' => 'Taslak',
                                'in_review' => 'Editör Onayında',
                                'published' => 'Yayında',
                            ])
                            ->default('draft')
                            ->required(),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Ana sayfada öne çıkar')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('published_at')->label('Yayın Tarihi')->timezone('Europe/Istanbul')->helperText('Türkiye saati (UTC+3). İleri tarihli haberler belirtilen saatte görünür.'),
                    ]),

                RichEditor::make('summary')
                    ->label('Kısa Özet')
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('İçerik')
                    ->required()
                    ->columnSpanFull(),

                FileUpload::make('cover_image')
                    ->label('Kapak Görseli')
                    ->image()
                    ->directory('posts')
                    ->saveUploadedFileUsing(function (\Filament\Forms\Components\BaseFileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): string {
                        $path = 'posts/'.$component->getUploadedFileNameForStorage($file);
                        $stream = fopen($file->getRealPath(), 'rb');
                        try {
                            $saved = $component->getDisk()->put($path, $stream, ['visibility' => 'public']);
                        } catch (\Throwable $exception) {
                            report($exception);
                            $saved = false;
                        } finally {
                            if (is_resource($stream)) {
                                fclose($stream);
                            }
                        }
                        if (! $saved) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'data.cover_image' => 'Kapak görseli kaydedilemedi. Lütfen yeniden deneyin.',
                            ]);
                        }

                        return $path;
                    })
                    ->columnSpanFull(),

                Forms\Components\Section::make('Haber Medyası')
                    ->description('Görsel eklemek için mevcut kapak ve içerik görsel alanlarını kullanın. Video ve YouTube bağlantısı birlikte eklenebilir. Video yükleme yalnız yetkili admin hesabına açıktır.')
                    ->schema(NewsMediaFields::schema())
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
                        'draft' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_featured')->label('Öne çıkan')->boolean(),
                Tables\Columns\TextColumn::make('news_media.status')->label('Haber Videosu')
                    ->formatStateUsing(fn ($state) => NewsMediaFields::status($state))->badge(),
                Tables\Columns\TextColumn::make('published_at')->label('Yayın Tarihi')->dateTime('d.m.Y H:i')->timezone('Europe/Istanbul'),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])
            ->searchPlaceholder('Haber Ara')
            ->filters([Tables\Filters\SelectFilter::make('status')->label('Yayın Durumu')->options(['draft' => 'Taslak Haberler', 'in_review' => 'Onay Bekleyen Haberler', 'published' => 'Yayımlanan Haberler'])])
            ->actions([
                Tables\Actions\EditAction::make()->label('Haber Düzenle'),
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

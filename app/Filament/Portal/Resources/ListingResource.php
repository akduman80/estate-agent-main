<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ListingResource\Pages;
use App\Models\Listing;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // 1. ADIM: Temel Bilgiler
                    Wizard\Step::make('Genel Bilgiler')
                        ->schema([
                            TextInput::make('title')
                                ->label('İlan Başlığı')
                                ->required()
                                ->maxLength(255),
                            Grid::make(['default' => 1, 'md' => 2])
                                ->schema([
                                    Select::make('type')
                                        ->label('İlan Tipi')
                                        ->options([
                                            'sale' => 'Satılık',
                                            'rent' => 'Kiralık',
                                        ])
                                        ->required(),
                                    TextInput::make('price')
                                        ->label('Fiyat (TL)')
                                        ->numeric()
                                        ->required(),
                                ]),
                            RichEditor::make('description')
                                ->label('Açıklama')
                                ->columnSpanFull(),
                        ]),

                    // 2. ADIM: EİDS & Konum Bilgileri
                    Wizard\Step::make('EİDS & Adres')
                        ->schema([
                            TextInput::make('eids_document_no')
                                ->label('Taşınmaz Ticareti Yetki Belgesi / EİDS No')
                                ->placeholder('EİDS Doğrulama No')
                                ->required(),
                            Grid::make(['default' => 1, 'md' => 3])
                                ->schema([
                                    Select::make('city_id')
                                        ->label('İl')
                                        ->options([
                                            '34' => 'İstanbul',
                                            '06' => 'Ankara',
                                            '35' => 'İzmir',
                                        ])
                                        ->live()
                                        ->required(),
                                    Select::make('district_id')
                                        ->label('İlçe')
                                        ->options(fn (Get $get) => match ($get('city_id')) {
                                            '34' => ['kadikoy' => 'Kadıköy', 'besiktas' => 'Beşiktaş'],
                                            '06' => ['cankaya' => 'Çankaya', 'kecloren' => 'Keçiören'],
                                            '35' => ['karsiyaka' => 'Karşıyaka', 'alsancak' => 'Alsancak'],
                                            default => [],
                                        })
                                        ->required(),
                                    TextInput::make('neighborhood_id')->label('Mahalle'),
                                ]),
                            Grid::make(['default' => 1, 'md' => 2])
                                ->schema([
                                    TextInput::make('block_number')->label('Ada No'),
                                    TextInput::make('parcel_number')->label('Parsel No'),
                                ]),
                        ]),

                    // 3. ADIM: Medya (Görsel & Video)
                    Wizard\Step::make('Görsel ve Video')
                        ->schema([
                            Select::make('category')
                                  ->label('Kategori')
                                  ->options([
                                      'housing'   => 'Konut',
                                      'workplace' => 'İşyeri',
                                      'land'      => 'Arsa',
                                  ])
                                  ->default('housing')
                                  ->required(),
                            FileUpload::make('images')
                                ->label('İlan Fotoğrafları')
                                ->multiple()
                                ->image()
                                ->directory('listings/temp')
                                ->maxFiles(20)
                                ->maxSize(15360)
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            TextInput::make('video_url')
                                ->label('YouTube Video Linki')
                                ->placeholder('https://www.youtube.com/watch?v=...')
                                ->url(),
                        ]),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('İlan Başlığı')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Fiyat')->money('TRY')->sortable(),
                Tables\Columns\TextColumn::make('eids_status')
                    ->label('EİDS Durumu')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending'  => 'warning',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit'   => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}

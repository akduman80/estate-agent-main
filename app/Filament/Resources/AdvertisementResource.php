<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Reklamlar';
    protected static ?string $modelLabel = 'Reklam';
    protected static ?string $pluralModelLabel = 'Reklamlar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reklam içeriği')->schema([
                Forms\Components\TextInput::make('label')->label('Reklam adı')->required()->maxLength(255),
                Forms\Components\Select::make('placement')->label('Reklam alanı / sıra')->options(['home01' => 'Ana sayfa — 1. sıra (fincan/kupa)', 'home02' => 'Ana sayfa — 2. sıra (tişört/sweatshirt)', 'home03' => 'Ana sayfa — 3. sıra (şapka/DTF)', 'left' => 'Eski sol alan (gösterilmez)', 'right' => 'Eski sağ alan (gösterilmez)'])->required()->default('home01')->live()->helperText('Yeni alan üç sıradan oluşur. Bir sırada en son oluşturulan kayıt kullanılır; eski kayıtlar korunur.'),
                Forms\Components\FileUpload::make('image_path')->label('Reklam görseli')->image()->disk('public')->visibility('public')->directory('site/ads')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(8192)->required()->helperText('Ana sayfa için 1600 × 240 px (20:3) yatay görsel yükleyin. Görsel kırpılmaz; masaüstünde ve telefonda aynıdır.'),
                Forms\Components\FileUpload::make('horizontal_image_path')->label('Yatay reklam görseli (isteğe bağlı)')->image()->disk('public')->visibility('public')->directory('site/ads')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(8192)->visible(fn (Forms\Get $get): bool => in_array($get('placement'), ['left', 'right'], true))->helperText('Eski kayıttaki dosya korunur; footer reklam alanı artık gösterilmez.'),
                Forms\Components\TextInput::make('target_url')->label('Tıklanınca açılacak bağlantı')->url()->maxLength(2048),
            ])->columns(2),
            Forms\Components\Section::make('Yayın ve dönüş ayarları')->description('Ana sayfa reklamı sıra ile döner: 5 saniye sabit gösterim ve 0,64 saniye geçiş. Yayın tarihleri ve Yayında anahtarı uygulanır.')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Yayın başlangıcı')->seconds(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Yayın bitişi')->seconds(false)->after('starts_at'),
                Forms\Components\TextInput::make('rotation_interval_seconds')->label('Gösterim sıklığı (saniye)')->numeric()->integer()->minValue(5)->maxValue(3600)->default(5)->required()->visible(fn (Forms\Get $get): bool => in_array($get('placement'), ['left', 'right'], true))->helperText('Eski değer korunur; yeni alan sabit 5 saniyelik gösterim kullanır.'),
                Forms\Components\Toggle::make('is_active')->label('Yayında')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_path')->label('Görsel')->disk('public')->square()->size(64),
            Tables\Columns\TextColumn::make('label')->label('Reklam')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('placement')->label('Alan / sıra')->formatStateUsing(fn (string $state): string => match ($state) { 'home01' => 'Ana sayfa / 1', 'home02' => 'Ana sayfa / 2', 'home03' => 'Ana sayfa / 3', 'right' => 'Eski sağ (kapalı)', default => 'Eski sol (kapalı)' }),
            Tables\Columns\TextColumn::make('rotation_interval_seconds')->label('Sabit gösterim')->formatStateUsing(fn ($state, Advertisement $record): string => str_starts_with($record->placement, 'home') ? '5 sn' : $state.' sn (eski)'),
            Tables\Columns\TextColumn::make('starts_at')->label('Başlangıç')->dateTime('d.m.Y H:i'),
            Tables\Columns\TextColumn::make('ends_at')->label('Bitiş')->dateTime('d.m.Y H:i'),
            Tables\Columns\IconColumn::make('is_active')->label('Yayında')->boolean(),
        ])->defaultSort('created_at', 'desc')->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}

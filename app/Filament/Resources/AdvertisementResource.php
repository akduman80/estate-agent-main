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
                Forms\Components\Select::make('placement')->label('Ekran kenarı')->options(['left' => 'Sol', 'right' => 'Sağ'])->required()->default('left'),
                Forms\Components\FileUpload::make('image_path')->label('Reklam görseli')->image()->disk('public')->visibility('public')->directory('site/ads')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(8192)->imageEditor()->imageCropAspectRatio('2:5')->required()->helperText('Dikey görsel önerilir; görseli 2:5 oranında kırpabilirsiniz.'),
                Forms\Components\TextInput::make('target_url')->label('Tıklanınca açılacak bağlantı')->url()->maxLength(2048),
            ])->columns(2),
            Forms\Components\Section::make('Yayın ve dönüş ayarları')->description('Reklam tarih aralığında yayınlanır. Daha kısa saniye değeri aynı kenardaki diğer reklamlara göre daha sık gösterim sağlar.')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Yayın başlangıcı')->seconds(false),
                Forms\Components\DateTimePicker::make('ends_at')->label('Yayın bitişi')->seconds(false)->after('starts_at'),
                Forms\Components\TextInput::make('rotation_interval_seconds')->label('Gösterim sıklığı (saniye)')->numeric()->integer()->minValue(10)->maxValue(3600)->default(60)->required()->helperText('Örnek: 10 saniye seçilen reklam, 60 saniyelik reklama göre yaklaşık altı kat sık gösterilir.'),
                Forms\Components\Toggle::make('is_active')->label('Yayında')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_path')->label('Görsel')->disk('public')->square()->size(64),
            Tables\Columns\TextColumn::make('label')->label('Reklam')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('placement')->label('Kenar')->formatStateUsing(fn (string $state): string => $state === 'right' ? 'Sağ' : 'Sol'),
            Tables\Columns\TextColumn::make('rotation_interval_seconds')->label('Dönüş aralığı')->suffix(' sn'),
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

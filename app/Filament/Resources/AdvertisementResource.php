<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use App\Support\HomeAdvertisements;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            Forms\Components\ViewField::make('advertisement_help')->view('filament.resources.advertisements.usage')->dehydrated(false)->columnSpanFull(),
            Forms\Components\Section::make('Reklam içeriği')->schema([
                Forms\Components\Placeholder::make('area')->label('Reklam alanı')->content('Ana sayfa — ilanların üstündeki tek yatay reklam alanı'),
                Forms\Components\TextInput::make('label')->label('Reklam adı')->required()->maxLength(255),
                Forms\Components\Select::make('placement')->label('Gösterim sırası')->options(HomeAdvertisements::placements())->searchable()->required()->unique(ignoreRecord: true)->default(fn (): ?string => collect(array_keys(HomeAdvertisements::placements()))->diff(Advertisement::query()->pluck('placement'))->first())->helperText('1–99 arasında kullanılmayan bir sıra seçin. Reklamlar küçükten büyüğe döner; aynı sıra iki kez kullanılamaz.'),
                Forms\Components\FileUpload::make('image_path')->label('Yatay reklam görseli')->image()->disk('public')->visibility('public')->directory('site/ads')->acceptedFileTypes(HomeAdvertisements::MIME_TYPES)->maxSize(HomeAdvertisements::MAX_KILOBYTES)->rules(['dimensions:width='.HomeAdvertisements::WIDTH.',height='.HomeAdvertisements::HEIGHT])->required()->helperText('Tam 1600 × 240 px (20:3); JPG/JPEG, PNG veya WebP; en fazla 8 MB. Görselin tamamı gösterilir; kırpma ve boyutlandırma yapılmaz.')->validationMessages(['dimensions' => 'Görsel tam 1600 × 240 piksel olmalıdır.'])->columnSpanFull(),
                Forms\Components\TextInput::make('target_url')->label('Tıklanınca açılacak bağlantı')->url()->rules(['url:http,https'])->required()->maxLength(2048)->helperText('http:// veya https:// ile başlayan tam bağlantı. WhatsApp hazır mesajını ?text= parametresiyle bağlantıya ekleyin.'),
            ])->columns(2),
            Forms\Components\Section::make('Yayın ve dönüş ayarları')->description('Ana sayfa reklamı sıra ile döner: 5 saniye sabit gösterim ve 0,64 saniye geçiş. Yayın tarihleri ve Yayında anahtarı uygulanır.')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Yayın başlangıcı')->timezone(HomeAdvertisements::TIMEZONE)->seconds(false)->helperText('Türkiye saati. Boşsa hemen yayınlanabilir.'),
                Forms\Components\DateTimePicker::make('ends_at')->label('Yayın bitişi')->timezone(HomeAdvertisements::TIMEZONE)->seconds(false)->after(fn (Forms\Get $get): string => filled($get('starts_at')) ? 'starts_at' : '')->helperText('Türkiye saati. Boşsa süresiz; başlangıçtan sonra olmalıdır.'),
                Forms\Components\Toggle::make('is_active')->label('Yayında')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_path')->label('Görsel')->disk('public')->width(160)->height(24),
            Tables\Columns\TextColumn::make('label')->label('Reklam')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('placement')->label('Gösterim sırası')->formatStateUsing(fn (string $state): string => ((int) substr($state, 4)).'. sıra')->sortable(),
            Tables\Columns\TextColumn::make('starts_at')->label('Başlangıç (Türkiye)')->dateTime('d.m.Y H:i', HomeAdvertisements::TIMEZONE)->placeholder('Hemen'),
            Tables\Columns\TextColumn::make('ends_at')->label('Bitiş (Türkiye)')->dateTime('d.m.Y H:i', HomeAdvertisements::TIMEZONE)->placeholder('Süresiz'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Yayında'),
            Tables\Columns\TextColumn::make('publication_status')->label('Durum')->state(fn (Advertisement $record): string => ! $record->is_active ? 'Yayından kaldırıldı' : ($record->starts_at?->isFuture() ? 'Yayın bekliyor' : ($record->ends_at?->isPast() ? 'Süresi doldu' : 'Sitede yayında'))),
        ])->defaultSort('placement')->actions([Tables\Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('placement', array_keys(HomeAdvertisements::placements()));
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

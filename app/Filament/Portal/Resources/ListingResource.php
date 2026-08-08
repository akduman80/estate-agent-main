<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ListingResource\Pages;
use App\Models\Listing;
use App\Models\Setting;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

// --- TÜRKÇE ETİKET YÖNETİMİ ---
    protected static ?string $modelLabel       = 'İlan';
    protected static ?string $pluralModelLabel = 'İlanlar';
    protected static ?string $navigationLabel  = 'İlanlar';
    protected static ?string $breadcrumb       = 'İlanlar';

    /**
     * Settings verisinden dinamik Selectbox Key-Value dizisi üreten yardımcı fonksiyon.
     */
    protected static function getDynamicOptions(string $key, array $defaultOptions): array
    {
        $settings = Setting::get($key, $defaultOptions);
        if (empty($settings) || !is_array($settings)) {
            $settings = $defaultOptions;
        }

        // Dizi elemanlarını ['Tarla' => 'Tarla'] formatına getirir
        $formatted = [];
        foreach ($settings as $option) {
            $formatted[$option] = $option;
        }
        return $formatted;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // --- 1. ADIM: TEMEL İLAN BİLGİLERİ ---
                    Wizard\Step::make('Temel Bilgiler')
                               ->schema([
                                   TextInput::make('title')
                                            ->label('İlan Başlığı')
                                            ->placeholder('Örn: ZİYADE EMLAKTAN SUMBAS GAFFARLI KESMESUR YAYLASINDA YAYLALIK')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                   RichEditor::make('description')
                                             ->label('Açıklama')
                                             ->columnSpanFull(),

                                   Grid::make(['default' => 1, 'md' => 2])
                                       ->schema([
                                           // DİNAMİK İLAN TİPİ
                                           Select::make('type')
                                                 ->label('İlan Tipi')
                                                 ->options(static::getDynamicOptions('options_type', ['Satılık', 'Kiralık']))
                                                 ->required(),

                                           TextInput::make('price')
                                                    ->label('Fiyat (TL)')
                                                    ->numeric()
                                                    ->required()
                                                    ->suffix('TL'),
                                       ]),

                                   // Ticaret Bakanlığı Fiyat Cezası Uyarısı (Sahibinden Birebir Formatı)
                                   Placeholder::make('eids_price_warning')
                                              ->label('')
                                              ->content(new HtmlString('
                                    <div style="background-color: #fff8e1; border-left: 4px solid #ffa000; padding: 10px; font-size: 12px; color: #555; border-radius: 4px; margin-top: 5px;">
                                        <strong>Ticaret Bakanlığı</strong>, ilgili mevzuat gereğince ilanlardaki fiyat artışlarını yakından takip etmektedir.
                                        <strong>Piyasa koşullarına uygun olmayan fiyat artışı yapılan ilanlara ilişkin bilgilerin</strong> Ticaret Bakanlığı tarafından talep edildiğini ve
                                        <strong>aykırılık tespit edilen ilanlar için 858.620 TL\'ye varan idari para cezası uygulanabileceğini</strong> önemle hatırlatırız.
                                    </div>
                                '))
                                              ->columnSpanFull(),
                               ]),

                    // --- 2. ADIM: SAHİBİNDEN GAYRİMENKUL & ARSA DETAYLARI ---
                    Wizard\Step::make('Gayrimenkul & İmar Detayları')
                               ->schema([
                                   Grid::make(['default' => 1, 'md' => 3])
                                       ->schema([
                                           Select::make('zoning_status')
                                                 ->label('İmar Durumu')
                                                 ->options(static::getDynamicOptions('options_zoning_status', [
                                                     'Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik'
                                                 ])),

                                           TextInput::make('square_meters')
                                                    ->label('m² (Alan)')
                                                    ->numeric()
                                                    ->suffix('m²'),

                                           Select::make('deed_status')
                                                 ->label('Tapu Durumu')
                                                 ->options(static::getDynamicOptions('options_deed_status', [
                                                     'Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet'
                                                 ])),
                                       ]),

                                   Grid::make(['default' => 1, 'md' => 3])
                                       ->schema([
                                           TextInput::make('block_number')->label('Ada No'),
                                           TextInput::make('parcel_number')->label('Parsel No'),
                                           TextInput::make('sheet_number')->label('Pafta No'),
                                       ]),

                                   Grid::make(['default' => 1, 'md' => 2])
                                       ->schema([
                                           TextInput::make('kaks_emsal')->label('Kaks (Emsal)')->placeholder('Örn: 0.20 / 0.40'),
                                           TextInput::make('gabari')->label('Gabari')->placeholder('Örn: Serbest / 6.50 m'),
                                       ]),

                                   Grid::make(['default' => 1, 'md' => 3])
                                       ->schema([
                                           Select::make('credit_eligibility')
                                                 ->label('Krediye Uygunluk')
                                                 ->options(static::getDynamicOptions('options_credit_eligibility', [
                                                     'Evet', 'Hayır', 'Bilinmiyor'
                                                 ]))
                                                 ->default('Bilinmiyor'),

                                           Select::make('owner_nationality')
                                                 ->label('Gayrimenkul Sahibi')
                                                 ->options(static::getDynamicOptions('options_owner_nationality', [
                                                     'T.C. Vatandaşı', 'Yabancı'
                                                 ]))
                                                 ->default('T.C. Vatandaşı'),

                                           Select::make('from_who')
                                                 ->label('Kimden')
                                                 ->options(static::getDynamicOptions('options_from_who', [
                                                     'Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından'
                                                 ]))
                                                 ->default('Emlak Ofisinden'),
                                       ]),

                                   Select::make('swap_option')
                                         ->label('Takaslı')
                                         ->options(static::getDynamicOptions('options_swap_option', [
                                             'Evet', 'Hayır'
                                         ]))
                                         ->default('Hayır'),
                               ]),

                    // --- 3. ADIM: ADRES & EİDS ---
                    Wizard\Step::make('EİDS & Konum')
                               ->schema([
                                   TextInput::make('eids_document_no')
                                            ->label('Taşınmaz Numarası / EİDS Belge No')
                                            ->placeholder('Örn: 94536593')
                                            ->required(),

                                   Grid::make(['default' => 1, 'md' => 3])
                                       ->schema([
                                           Select::make('city_id')
                                                 ->label('İl')
                                                 ->options(static::getDynamicOptions('options_cities', [
                                                     'Osmaniye', 'İstanbul', 'Ankara'
                                                 ]))
                                                 ->searchable()
                                                 ->required(),

                                           Select::make('district_id')
                                                 ->label('İlçe')
                                                 ->options(static::getDynamicOptions('options_districts', [
                                                     'Sumbas', 'Kadirli', 'Kadıköy', 'Beşiktaş', 'Çankaya'
                                                 ]))
                                                 ->searchable()
                                                 ->required(),

                                           TextInput::make('neighborhood_id')->label('Mahalle / Yayla')->placeholder('Gaffarli Kesmesur Yaylası'),
                                       ]),
                               ]),

                    // --- 4. ADIM: MEDYA ---
                    // --- 4. ADIM: MEDYA ---
                    Wizard\Step::make('Görsel ve Video')
                               ->schema([
                                   FileUpload::make('uploaded_images')
                                             ->label('İlan Fotoğrafları')
                                             ->multiple()
                                             ->image()
                                             ->directory('listings')
                                             ->maxFiles(30)
                                             ->maxSize(15360) // 15MB
                                             ->reorderable()
                                             ->dehydrated(false) // Listings tablosunda bu sütunu aramasını engeller
                                                                 // 1. DÜZENLEME EKRANINDA MEVCUT RESİMLERİ YÜKLER:
                                             ->loadStateFromRelationshipsUsing(function (FileUpload $component, Listing $record) {
                                           $images = $record->images()->pluck('image_path')->toArray();
                                           $component->state($images);
                                       })
                                       // 2. KAYDETME / GÜNCELLEME ESNASINDA VERİTABANINA YAZAR:
                                             ->saveRelationshipsUsing(function (Listing $record, $state) {
                                           if (is_array($state)) {
                                               // Mevcut eski resim kayıtlarını temizleyip güncel listeyi yazıyoruz
                                               $record->images()->delete();

                                               foreach (array_values($state) as $index => $path) {
                                                   $record->images()->create([
                                                       'image_path' => $path,
                                                       'is_main'    => $index === 0, // İlk sıradaki resim kapak resmi
                                                       'sort_order' => $index,
                                                   ]);
                                               }
                                           }
                                       })
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
                Tables\Columns\TextColumn::make('title')->label('İlan Başlığı')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('price')->label('Fiyat')->money('TRY')->sortable(),
                Tables\Columns\TextColumn::make('eids_document_no')->label('Taşınmaz No'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListListings::route('/'),
            'create' => Pages\CreateListing::route('/create'),
            'edit'   => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var User $user */
        $user = auth()->user();

        // Eğer kullanıcı AGENT ise sadece kendi ilanlarını görsün
        if ($user && ($user->role === UserRole::AGENT || $user->role === UserRole::AGENT->value)) {
            return $query->where('user_id', $user->id);
        }

        // SUPERVISOR tüm ilanları görür
        return $query;
    }
}

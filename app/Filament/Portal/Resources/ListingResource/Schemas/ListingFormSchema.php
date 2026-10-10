<?php

/** Created by ramazanorak, Date: 8.08.2026 */

namespace App\Filament\Portal\Resources\ListingResource\Schemas;

use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\BasicInfoStep;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\MediaStep;
use App\Filament\Portal\Resources\ListingResource\Schemas\Steps\RealEstateDetailsStep;
use App\Models\Listing;
use App\Models\Setting;
use App\Rules\ListingDecimalPrecision;
use App\Services\TurkeyAddressService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class ListingFormSchema
{
    public static function make(): array
    {
        return [
            \Filament\Forms\Components\Section::make('Harita konumu ve ek bilgiler')->schema([
                \Filament\Forms\Components\Hidden::make('latitude')->rules(['nullable', 'numeric', 'between:-90,90']),
                \Filament\Forms\Components\Hidden::make('longitude')->rules(['nullable', 'numeric', 'between:-180,180']),
                \Filament\Forms\Components\ViewField::make('location_picker')->view('filament.forms.components.project-location-picker'),
                \Filament\Forms\Components\Textarea::make('mapped_address')->label('Harita adresi')->maxLength(1000),
                \Filament\Forms\Components\Repeater::make('extra_fields')->label('Ek bilgiler')->defaultItems(0)->schema([
                    \Filament\Forms\Components\Hidden::make('_mobileKey'),
                    \Filament\Forms\Components\TextInput::make('ad')->label('Bilgi adı')->required()->maxLength(100),
                    \Filament\Forms\Components\Textarea::make('deger')->label('Değer')->required()->maxLength(2000),
                ])->columns(2),
            ])->collapsed(),
            Wizard::make([
                BasicInfoStep::make(),
                RealEstateDetailsStep::make(),
                MediaStep::make(),
            ])->columnSpanFull(),
        ];
    }

    protected static function getDynamicOptions(string $key, array $defaultOptions): array
    {
        $settings = Setting::get($key, $defaultOptions);
        if (empty($settings) || ! is_array($settings)) {
            $settings = $defaultOptions;
        }

        $formatted = [];
        foreach ($settings as $option) {
            $formatted[$option] = $option;
        }

        return $formatted;
    }

    private static function getBasicInfoStep(): Wizard\Step
    {
        return Wizard\Step::make('Temel Bilgiler')
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
                        Select::make('type')
                            ->label('İlan Tipi')
                            ->options(static::getDynamicOptions('options_type', ['Satılık', 'Kiralık']))
                            ->required(),

                        TextInput::make('price')
                            ->label('Fiyat (TL)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->required()
                            ->suffix('TL'),
                    ]),

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
            ]);
    }

    private static function getRealEstateDetailsStep(): Wizard\Step
    {
        return Wizard\Step::make('Gayrimenkul & İmar Detayları')
            ->schema([
                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('zoning_status')
                            ->label('İmar Durumu')
                            ->options(static::getDynamicOptions('options_zoning_status', [
                                'Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik',
                            ])),

                        TextInput::make('square_meters')
                            ->label('m² (Alan)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->suffix('m²'),

                        Select::make('deed_status')
                            ->label('Tapu Durumu')
                            ->options(static::getDynamicOptions('options_deed_status', [
                                'Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet',
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
                                'Evet', 'Hayır', 'Bilinmiyor',
                            ]))
                            ->default('Bilinmiyor'),

                        Select::make('owner_nationality')
                            ->label('Gayrimenkul Sahibi')
                            ->options(static::getDynamicOptions('options_owner_nationality', [
                                'T.C. Vatandaşı', 'Yabancı',
                            ]))
                            ->default('T.C. Vatandaşı'),

                        Select::make('from_who')
                            ->label('Kimden')
                            ->options(static::getDynamicOptions('options_from_who', [
                                'Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından',
                            ]))
                            ->default('Emlak Ofisinden'),
                    ]),

                Select::make('swap_option')
                    ->label('Takaslı')
                    ->options(static::getDynamicOptions('options_swap_option', [
                        'Evet', 'Hayır',
                    ]))
                    ->default('Hayır'),
            ]);
    }

    private static function getEidsAndLocationStep(): Wizard\Step
    {
        return Wizard\Step::make('EİDS & Konum')
            ->schema([
                TextInput::make('eids_document_no')
                    ->label('Taşınmaz Numarası / EİDS Belge No')
                    ->placeholder('Örn: 94536593')
                    ->type('text')
                    ->inputMode('numeric')
                    ->regex('/\A[0-9]+\z/')
                    ->maxLength(255)
                    ->validationMessages(['regex' => 'Taşınmaz ID yalnızca rakamlardan oluşmalıdır.'])
                    ->required(),

                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('city_id')
                            ->label('İl')
                            ->options(TurkeyAddressService::getCities())
                            ->searchable()
                            ->live() // İl değiştiğinde formu reaktif olarak günceller
                            ->afterStateUpdated(function (Set $set): void {
                                $set('district_id', null);
                                $set('neighborhood_id', null);
                            })
                            ->required(),

                        Select::make('district_id')
                            ->label('İlçe')
                            ->options(fn (Get $get) => TurkeyAddressService::getDistricts($get('city_id')))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('neighborhood_id', null))
                            ->required(),

                        Select::make('neighborhood_id')
                            ->label('Mahalle / Köy')
                            ->options(fn (Get $get) => TurkeyAddressService::getNeighborhoods(
                                $get('city_id'),
                                $get('district_id'),
                            ))
                            ->searchable(),
                    ]),
            ]);
    }

    private static function getMediaStep(): Wizard\Step
    {
        return Wizard\Step::make('Görsel ve Video')
            ->schema([
                FileUpload::make('uploaded_images')
                    ->label('İlan Fotoğrafları')
                    ->multiple()
                    ->image()
                    ->directory('listings')
                    ->maxFiles(30)
                    ->maxSize(15360)
                    ->reorderable()
                    ->dehydrated(false)
                    ->loadStateFromRelationshipsUsing(function (FileUpload $component, Listing $record) {
                        $images = $record->images()->pluck('image_path')->toArray();
                        $component->state($images);
                    })
                    ->saveRelationshipsUsing(function (Listing $record, $state) {
                        if (is_array($state)) {
                            $record->images()->delete();

                            foreach (array_values($state) as $index => $path) {
                                $record->images()->create([
                                    'image_path' => $path,
                                    'is_main' => $index === 0,
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
            ]);
    }
}

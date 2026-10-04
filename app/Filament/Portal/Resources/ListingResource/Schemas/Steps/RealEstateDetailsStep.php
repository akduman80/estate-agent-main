<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use App\Models\Listing;
use App\Models\Setting;
use App\Rules\ListingDecimalPrecision;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;

class RealEstateDetailsStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Gayrimenkul Detayları')
            ->icon('heroicon-o-home-modern')
            ->description('Taşınmaz özellikleri')
            ->schema([
                // --- GENEL ALAN (Tüm Türler İçin Ortak) ---
                Grid::make(['default' => 1, 'md' => 4])
                    ->schema([
                        TextInput::make('square_meters')
                            ->label('m² (Alan)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->required()
                            ->suffix('m²'),

                        TextInput::make('net_square_meters')
                            ->label('Net m²')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->suffix('m²'),

                        Select::make('deed_status')
                            ->label('Tapu Durumu')
                            ->options(static::getDynamicOptions('options_deed_status', [
                                'Hisseli Tapu', 'Müstakil Parsel', 'Tahsis Tapu', 'Zilliyet',
                            ])),

                        Select::make('from_who')
                            ->label('Kimden')
                            ->options(static::getDynamicOptions('options_from_who', [
                                'Emlak Ofisinden', 'Sahibinden', 'İnşaat Firmasından',
                            ]))
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                // --- 1. KONUT / EV DETAYLARI (Daire, Müstakil Ev vb. Seçilince Çıkar) ---
                Grid::make(['default' => 1, 'md' => 3])
                    ->visible(fn (Get $get) => in_array($get('category'), ['Daire', 'Müstakil Ev', 'İşyeri']))
                    ->schema([
                        Select::make('room_count')
                            ->label('Oda Sayısı')
                            ->options(static::getDynamicOptions('options_room_count', [
                                '1+0', '1+1', '2+1', '3+1', '4+1',
                            ])),

                        Select::make('heating_type')
                            ->label('Isıtma Tipi')
                            ->options(static::getDynamicOptions('options_heating_type', [
                                'Kombi (Doğalgaz)', 'Klima', 'Merkezi Sistem', 'Sobalı',
                            ])),

                        Select::make('building_age')
                            ->label('Bina Yaşı')
                            ->options(static::getDynamicOptions('options_building_age', [
                                '0 (Yeni)', '1-5 Arası', '6-10 Arası', '11-15 Arası', '16-20 Arası', '21 ve üzeri',
                            ])),

                        Select::make('furniture_status')
                            ->label('Eşya Durumu')
                            ->options(static::getDynamicOptions('options_furniture_status', [
                                'Eşyalı', 'Eşyasız',
                            ])),

                        Select::make('usage_status')
                            ->label('Kullanım Durumu')
                            ->options(static::getDynamicOptions('options_usage_status', [
                                'Boş', 'Kiracılı', 'Mülk Sahibi', 'Yapım Aşamasında',
                            ])),

                        Select::make('facade')
                            ->label('Cephe')
                            ->options(static::getDynamicOptions('options_facade', [
                                'Kuzey', 'Güney', 'Doğu', 'Batı',
                            ])),

                        TextInput::make('floor_number')
                            ->label('Bulunduğu Kat')
                            ->placeholder('Örn: 3. Kat / Giriş Kat'),

                        TextInput::make('total_floors')
                            ->label('Kat Sayısı')
                            ->numeric()
                            ->placeholder('Örn: 5'),

                        TextInput::make('site_name')
                            ->label('Site Adı')
                            ->maxLength(255),
                    ]),

                // --- 2. KİRALIK İLANLARA ÖZEL EKLER (Kiralık Seçilince Çıkar) ---
                Grid::make(['default' => 1, 'md' => 2])
                    ->visible(fn (Get $get) => $get('type') === 'Kiralık')
                    ->schema([
                        TextInput::make('deposit_price')
                            ->label('Depozito (TL)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->suffix('TL'),

                        TextInput::make('dues_price')
                            ->label('Aidat (TL)')
                            ->numeric()
                            ->rules([new ListingDecimalPrecision])
                            ->suffix('TL'),
                    ]),

                // --- 3. ARSA / TARLA / İMAR DETAYLARI (Arsa veya Tarla Seçilince Çıkar) ---
                Grid::make(['default' => 1, 'md' => 3])
                    ->visible(fn (Get $get) => in_array($get('category'), ['Arsa', 'Tarla']))
                    ->schema([
                        Select::make('zoning_status')
                            ->label('İmar Durumu')
                            ->options(static::getDynamicOptions('options_zoning_status', [
                                'Tarla', 'Konut İmarlı', 'Ticari İmarlı', 'Sanayi İmarlı', 'Bağ & Bahçe', 'Zeytinlik',
                            ])),

                        TextInput::make('block_number')->label('Ada No')->disabled(fn (?Listing $record): bool => $record?->eids_status === 'verified'),
                        TextInput::make('parcel_number')->label('Parsel No')->disabled(fn (?Listing $record): bool => $record?->eids_status === 'verified'),
                        TextInput::make('sheet_number')->label('Pafta No'),
                        TextInput::make('kaks_emsal')->label('Kaks (Emsal)')->placeholder('Örn: 0.20 / 0.40'),
                        TextInput::make('gabari')->label('Gabari')->placeholder('Örn: Serbest / 6.50 m'),
                    ]),

                // --- DİĞER ORTAK AYARLAR ---
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

                        Select::make('swap_option')
                            ->label('Takaslı')
                            ->options(static::getDynamicOptions('options_swap_option', [
                                'Evet', 'Hayır',
                            ]))
                            ->default('Hayır'),
                    ]),

                ...EidsAndLocationStep::fields(),
            ]);
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
}

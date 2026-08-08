<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use App\Models\Setting;

class RealEstateDetailsStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Gayrimenkul & İmar Detayları')
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
                          ]);
    }

    protected static function getDynamicOptions(string $key, array $defaultOptions): array
    {
        $settings = Setting::get($key, $defaultOptions);
        if (empty($settings) || !is_array($settings)) {
            $settings = $defaultOptions;
        }

        $formatted = [];
        foreach ($settings as $option) {
            $formatted[$option] = $option;
        }
        return $formatted;
    }
}

<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Models\Setting;
use Illuminate\Support\HtmlString;

class BasicInfoStep
{
    public static function make(): Wizard\Step
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

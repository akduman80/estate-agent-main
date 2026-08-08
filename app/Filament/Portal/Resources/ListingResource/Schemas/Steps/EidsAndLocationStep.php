<?php

namespace App\Filament\Portal\Resources\ListingResource\Schemas\Steps;

use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Services\TurkeyAddressService;

class EidsAndLocationStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('EİDS & Konum')
                          ->schema([
                              TextInput::make('eids_document_no')
                                       ->label('Taşınmaz Numarası / EİDS Belge No')
                                       ->placeholder('Örn: 94536593')
                                       ->required(),

                              Grid::make(['default' => 1, 'md' => 3])
                                  ->schema([
                                      Select::make('city_id')
                                            ->label('İl')
                                            ->options(TurkeyAddressService::getCities())
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('district_id', null))
                                            ->required(),

                                      Select::make('district_id')
                                            ->label('İlçe')
                                            ->options(fn (Get $get) => TurkeyAddressService::getDistricts($get('city_id')))
                                            ->searchable()
                                            ->required(),

                                      TextInput::make('neighborhood_id')
                                               ->label('Mahalle / Yayla')
                                               ->placeholder('Gaffarli Kesmesur Yaylası'),
                                  ]),
                          ]);
    }
}

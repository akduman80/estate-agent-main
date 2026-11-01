<?php

namespace App\Providers;

use App\Services\TurkeyAddressService;
use Filament\Forms\Components\Select;
use Illuminate\Support\ServiceProvider;

class AddressSelectServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Select::configureUsing(function (Select $select): void {
            if (! in_array($select->getName(), [
                'city', 'district', 'neighborhood', 'city_id', 'district_id', 'neighborhood_id',
            ], true)) {
                return;
            }

            // Choices keeps this value from its first render (even before a parent is
            // selected), so it must also cover lists loaded after changing the parent.
            // Options themselves remain limited to the selected city/district.
            // Choices loops up to this ceiling, so use the dataset maximum rather
            // than an arbitrary huge integer, even for initially empty child lists.
            $select->optionsLimit(TurkeyAddressService::getOptionsLimit());
        });
    }
}

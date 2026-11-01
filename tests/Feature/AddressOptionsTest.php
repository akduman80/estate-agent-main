<?php

namespace Tests\Feature;

use App\Services\TurkeyAddressService as Address;
use Filament\Forms\Components\Select;
use Tests\TestCase;

class AddressOptionsTest extends TestCase
{
    public function test_every_source_record_is_available_with_unchanged_keys(): void
    {
        $data = json_decode(file_get_contents(resource_path('data/turkey-neighborhoods.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(81, Address::getCities());
        $this->assertEqualsCanonicalizing(array_keys($data), array_keys(Address::getCities()));
        $total = 0;
        foreach ($data as $city => $districts) {
            $this->assertEqualsCanonicalizing(array_keys($districts), array_keys(Address::getDistricts($city)), $city);
            foreach ($districts as $district => $names) {
                $options = Address::getNeighborhoods($city, $district);
                $this->assertEqualsCanonicalizing(array_values(array_unique($names)), array_values($options), "$city/$district");
                $this->assertSame(array_keys($options), array_values($options));
                $total += count($options);
            }
        }
        $this->assertGreaterThan(70000, $total);
    }

    public function test_sorting_is_turkish_even_when_process_locale_is_c(): void
    {
        $old = setlocale(LC_COLLATE, 0);
        setlocale(LC_COLLATE, 'C');
        try {
            $names = ['Ünye', 'Zonguldak', 'İzmir', 'Isparta', 'Ödemiş', 'Ordu', 'Şişli', 'Sivas', 'Çorum', 'Ceyhan', 'Ğ', 'G', 'i', 'ı'];
            Address::sortNames($names);
            $this->assertSame(['Ceyhan', 'Çorum', 'G', 'Ğ', 'ı', 'Isparta', 'i', 'İzmir', 'Ordu', 'Ödemiş', 'Sivas', 'Şişli', 'Ünye', 'Zonguldak'], $names);
        } finally {
            setlocale(LC_COLLATE, $old);
        }
    }

    public function test_address_selects_render_all_choices_and_keep_search(): void
    {
        foreach (['city', 'city_id', 'district', 'district_id', 'neighborhood', 'neighborhood_id'] as $name) {
            $options = $name === 'city' || $name === 'city_id' ? Address::getCities() : Address::getNeighborhoods('Artvin', 'Yusufeli');
            $select = Select::make($name)->options($options)->searchable();
            $this->assertGreaterThanOrEqual(count($options), $select->getOptionsLimit());
            $this->assertTrue($select->isSearchable());
        }
        $this->assertSame(50, Select::make('category')->getOptionsLimit());
        $this->assertSame(15, Select::make('category')->optionsLimit(15)->getOptionsLimit());
    }

    public function test_invalid_parent_has_no_children_and_legacy_merkez_still_resolves(): void
    {
        $this->assertSame([], Address::getDistricts('invalid'));
        $this->assertSame([], Address::getNeighborhoods('Ankara', 'Kadıköy'));
        $this->assertSame([], Address::getNeighborhoods(null, null));
        $this->assertNotEmpty(Address::getNeighborhoods('Osmaniye', 'Merkez'));
        $this->assertSame(Address::getNeighborhoods('Osmaniye', 'Merkez'), Address::getNeighborhoods('Osmaniye', 'Osmaniye Merkez'));
        $this->assertSame([], Address::getNeighborhoods('Ankara', 'Osmaniye Merkez'));
    }
}

<?php

namespace App\Services;

class TaxOfficeDirectory
{
    public function data(): array
    {
        return json_decode(file_get_contents(resource_path('data/tax-offices.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function cities(): array
    {
        $cities = [];
        foreach ($this->data()['offices'] as $office) {
            $cities[$office['city_code']] = $office['city_name'];
        }
        $collator = new \Collator('tr_TR');
        uasort($cities, fn ($a, $b) => $collator->compare($a, $b));

        return $cities;
    }

    public function offices(string $city): array
    {
        $offices = array_values(array_filter($this->data()['offices'], fn ($office) => $office['city_code'] === $city));
        $collator = new \Collator('tr_TR');
        usort($offices, fn ($a, $b) => $collator->compare($a['name'], $b['name']));

        return $offices;
    }

    public function find(string $city, string $code): ?array
    {
        foreach ($this->offices($city) as $office) {
            if ($office['code'] === $code) {
                return $office;
            }
        }

        return null;
    }
}

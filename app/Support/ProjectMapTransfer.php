<?php

namespace App\Support;

use App\Services\TurkeyAddressService;

class ProjectMapTransfer
{
    public static function apply(array $state, string $target): array
    {
        $location = session()->pull('project-map-transfer.'.$target);
        if (! is_array($location)) {
            return $state;
        }
        $state['latitude'] = $location['latitude'];
        $state['longitude'] = $location['longitude'];
        $city = self::matchOption(TurkeyAddressService::getCities(), $location['city'] ?? null);
        $districtName = $location['district'] ?? null;
        if ($city && self::normalize($districtName ?? '') === self::normalize($city.' Merkez')) {
            $districtName = 'Merkez';
        }
        $district = self::matchOption(TurkeyAddressService::getDistricts($city), $districtName);
        $neighborhood = self::matchOption(TurkeyAddressService::getNeighborhoods($city, $district), $location['neighborhood'] ?? null);
        $fields = $target === 'project'
            ? ['city' => $city, 'district' => $district, 'neighborhood' => $neighborhood]
            : ['city_id' => $city, 'district_id' => $district, 'neighborhood_id' => $neighborhood,
                'mapped_address' => filled($location['address'] ?? null) ? '<p>'.e($location['address']).'</p>' : null,
                'block_number' => $location['block'] ?? null, 'parcel_number' => $location['parcel'] ?? null];
        foreach ($fields as $field => $value) {
            if ($value !== null && $value !== '') {
                $state[$field] = $value;
            }
        }
        if ($target === 'listing') {
            $state['form_details'] = array_replace($state['form_details'] ?? [], array_filter([
                'latitude' => $location['latitude'], 'longitude' => $location['longitude'], 'address' => $location['address'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return $state;
    }

    private static function matchOption(array $options, ?string $name): ?string
    {
        if (! filled($name)) {
            return null;
        }
        $normalized = self::normalize($name);
        foreach ($options as $key => $label) {
            if (self::normalize((string) $label) === $normalized) {
                return (string) $key;
            }
        }

        return null;
    }

    private static function normalize(string $name): string
    {
        return mb_strtolower(trim(str_replace(['İ', 'I'], ['i', 'ı'], $name)), 'UTF-8');
    }
}

<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class LocationResolver
{
    private static ?array $index = null;

    public function resolve(string $text, array $previous, bool $explicitCity = false): array
    {
        if (self::$index === null) {
            self::$index = ['district' => [], 'neighborhood' => []];
            $data = json_decode(file_get_contents(resource_path('data/turkey-neighborhoods.json')), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $city => $districts) foreach ($districts as $district => $names) {
                $location = ['city' => $city, 'district' => $district];
                self::$index['district'][$this->fold($district)][] = $location;
                foreach (array_unique($names) as $name) {
                    $key = preg_replace('/\s+(?:mahallesi|mah\.?|koyu|koy)\b.*$/u', '', $this->fold($name));
                    if (strlen($key) >= 4) self::$index['neighborhood'][$key][] = $location + ['neighborhood' => $name];
                }
            }
        }
        // A district's town centre is not the province's Merkez district.
        $explicitDistricts = [];
        foreach (self::$index['district'] as $key => $locations) {
            if ($key !== 'merkez' && preg_match('/(?<![a-z])'.preg_quote($key, '/').'(?:nde|nda|de|da|den|dan)?(?![a-z])/', $text)) $explicitDistricts[$key] = true;
        }
        if ($explicitDistricts) $text = preg_replace('/\bmerkez(?:inde|de|i)?\b/', '', $text);
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $keys = [];
        for ($i = 0; $i < count($words); $i++) for ($length = 1; $length <= min(6, count($words) - $i); $length++) {
            $key = implode(' ', array_slice($words, $i, $length));
            $keys[$key] = true;
            $keys[preg_replace('/(?:nde|nda|de|da|den|dan)$/', '', $key)] = true;
        }
        $filters = []; $ambiguous = false;
        foreach (['district', 'neighborhood'] as $kind) {
            $matches = [];
            foreach (array_diff_key($keys, array_flip(['bahceli', 'havuzlu', 'otoparkli', 'manzarali', 'satilik', 'kiralik', 'daire', 'villa', 'arsa', 'tarla', 'zemin', 'katli'])) as $key => $_) foreach (self::$index[$kind][$key] ?? [] as $location) {
                $scope = array_merge($previous, $filters);
                if ($kind === 'neighborhood' && isset($scope['city']) && $scope['city'] !== $location['city']) continue;
                if ($kind === 'neighborhood' && isset($scope['district']) && $scope['district'] !== $location['district']) continue;
                $matches[json_encode($location)] = $location;
            }
            if ($kind === 'district' && isset($previous['city'])) {
                $scoped = array_filter($matches, fn ($m) => $m['city'] === $previous['city']);
                if ($explicitCity && $matches && !$scoped) return ['filters' => [], 'ambiguous' => true];
                if ($scoped) $matches = $scoped;
            }
            if (count($matches) === 1) $filters = array_merge($filters, array_values($matches)[0]);
            elseif (count($matches) > 1) $ambiguous = true;
        }
        if (preg_match('/koyu|koyunde|mahallesi|mahallesinde/', $text) && ! isset($filters['neighborhood'])) $ambiguous = true;
        return compact('filters', 'ambiguous');
    }

    private function fold(string $value): string { return Str::ascii(Str::lower($value), 'tr'); }
}

<?php

namespace App\Support;

use App\Models\Setting;

class ListingCategories
{
    public const HOMES = ['Konut', 'Daire', 'Rezidans', 'Müstakil Ev', 'Ev', 'Villa', 'Çiftlik Evi', 'Köşk & Konak', 'Yalı', 'Yalı Dairesi', 'Yazlık', 'Kooperatif'];

    public static function groups(): array
    {
        return ['Konut' => array_values(array_diff(self::HOMES, ['Konut', 'Ev'])), 'İş Yeri' => ['İşyeri', 'Dükkan & Mağaza'], 'Arsa' => ['Arsa', 'Tarla'], 'Bina' => ['Bina'], 'Devre Mülk' => ['Devre Mülk'], 'Turistik Tesis' => ['Turistik Tesis']];
    }

    public static function group(?string $category): ?string
    {
        foreach (self::groups() as $group => $categories) {
            if (in_array($category, $categories, true) || $category === $group || ($group === 'Konut' && $category === 'Ev')) {
                return $group;
            }
        }

        return $category ? 'Diğer' : null;
    }

    public static function options(): array
    {
        $options = self::groups();
        $configured = Setting::get('options_category', ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri']);
        foreach ($options as $group => $values) {
            $options[$group] = array_combine($values, $values);
        }
        foreach (is_array($configured) ? $configured : [] as $category) {
            if (is_string($category) && $category !== '') {
                $options[self::group($category) ?? 'Diğer'][$category] = $category;
            }
        }

        return $options;
    }

    public static function types(?string $category): array
    {
        $types = ['Satılık', 'Kiralık'];
        if (in_array($category, ['Daire', 'Villa'], true)) {
            $types[] = 'Devren Satılık Konut';
        }
        if (self::group($category) === 'İş Yeri') {
            $types = [...$types, 'Devren Satılık', 'Devren Kiralık'];
        }
        $configured = Setting::get('options_type', ['Satılık', 'Kiralık']);
        // Preserve configured legacy operations without redefining them as devren housing.
        foreach (is_array($configured) ? $configured : [] as $type) {
            if (is_string($type) && ! str_starts_with($type, 'Devren')) {
                $types[] = $type;
            }
        }

        return array_combine(array_unique($types), array_unique($types));
    }
}

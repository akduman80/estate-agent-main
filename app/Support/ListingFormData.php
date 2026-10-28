<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListingFormData
{
    public const FIELDS = ['latitude', 'longitude', 'mapped_address', 'extra_fields', 'user_id', 'owner_agent_id', 'title', 'description', 'category', 'subcategory', 'type', 'price', 'currency', 'square_meters', 'net_square_meters', 'room_count', 'heating_type', 'building_age', 'furniture_status', 'floor_number', 'total_floors', 'deposit_price', 'dues_price', 'usage_status', 'facades', 'site_name', 'zoning_status', 'deed_status', 'block_number', 'parcel_number', 'sheet_number', 'kaks_emsal', 'gabari', 'credit_eligibility', 'owner_nationality', 'swap_option', 'eids_document_no', 'city_id', 'district_id', 'neighborhood_id', 'video_path', 'video_url', 'uploaded_images', 'private_note', 'form_details'];

    public static function clean(array $data): array
    {
        $data = Arr::only($data, self::FIELDS);
        if (! empty($data['form_details']['facade_declined'])) {
            $data['facades'] = [];
        }
        if (isset($data['form_details']) && is_array($data['form_details'])) {
            $data['form_details'] = Arr::only($data['form_details'], ['open_area', 'bathrooms', 'kitchen', 'balcony', 'energy_class', 'in_site', 'rental_period', 'address', 'latitude', 'longitude', 'hide_location', 'hide_phone', 'virtual_tour', 'features', 'facade_declined']);
            foreach ($data['form_details']['features'] ?? [] as $key => $group) {
                if (! in_array($key, ['interior', 'exterior', 'neighborhood', 'transport', 'view', 'housing', 'accessible'], true)) {
                    unset($data['form_details']['features'][$key]);

                    continue;
                }
                $data['form_details']['features'][$key] = ['declined' => (bool) ($group['declined'] ?? false), 'values' => ! empty($group['declined']) ? [] : array_values(array_unique($group['values'] ?? []))];
            }
        }
        if (! in_array($data['category'] ?? null, ListingCategories::HOMES, true) && ListingCategories::group($data['category'] ?? null) !== 'İş Yeri') {
            foreach (['room_count', 'heating_type', 'building_age', 'furniture_status', 'floor_number', 'total_floors', 'usage_status', 'facades', 'site_name'] as $key) {
                $data[$key] = null;
            }
            foreach (['open_area', 'bathrooms', 'kitchen', 'balcony', 'energy_class', 'in_site', 'features'] as $key) {
                unset($data['form_details'][$key]);
            }
        }
        if (! in_array($data['category'] ?? null, ['Arsa', 'Tarla'], true)) {
            foreach (['zoning_status', 'sheet_number', 'kaks_emsal', 'gabari'] as $key) {
                $data[$key] = null;
            }
        }
        if (($data['type'] ?? null) !== 'Kiralık') {
            $data['deposit_price'] = null;
        }

        return $data;
    }

    public static function validate(array $data, bool $publishing = false): array
    {
        foreach (['price', 'square_meters', 'net_square_meters', 'deposit_price', 'dues_price'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = self::decimal($data[$key]);
            }
        }
        $rules = [
            'price' => ['nullable', 'numeric', 'min:0'], 'square_meters' => ['nullable', 'numeric', 'min:0'],
            'net_square_meters' => ['nullable', 'numeric', 'min:0', 'lte:square_meters'],
            'deposit_price' => ['nullable', 'numeric', 'min:0'], 'dues_price' => ['nullable', 'numeric', 'min:0'],
            'form_details.open_area' => ['nullable', 'numeric', 'min:0'],
            'form_details.latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:form_details.longitude'],
            'form_details.longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:form_details.latitude'],
            'form_details.virtual_tour' => ['nullable', 'url', 'max:2048', function ($attribute, $value, $fail) {
                $url = parse_url($value);
                if (($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass']) || isset($url['port']) || ! in_array(strtolower($url['host'] ?? ''), ['my.matterport.com', 'matterport.com', 'www.matterport.com', 'app.immoviewer.com', 'tour.immoviewer.com', 'www.immoviewer.com'], true)) {
                    $fail('HTTPS Matterport veya Immoviewer bağlantısı girin.');
                }
            }],
            'private_note' => ['nullable', 'string', 'max:10000'],
            'form_details.hide_phone' => ['nullable', 'boolean'],
            'form_details.hide_location' => ['nullable', 'boolean'],
            'form_details.bathrooms' => ['nullable', 'integer', 'between:0,50'],
            'form_details.address' => ['nullable', 'string', 'max:1000'],
            'form_details.features.*.declined' => ['nullable', 'boolean'],
            'form_details.features.*.values' => ['nullable', 'array', 'max:100'],
            'form_details.features.*.values.*' => ['string', 'max:150'],
        ];
        if ($publishing) {
            $rules['title'] = ['required', 'string', 'max:255'];
            $rules['description'] = ['required', 'string'];
            $rules['category'] = ['required', Rule::in(array_merge(...array_values(array_map('array_keys', ListingCategories::options()))))];
            $rules['type'] = ['required', Rule::in(array_keys(ListingCategories::types($data['category'] ?? null)))];
        }
        Validator::make($data, $rules, [
            'min' => ':attribute negatif olamaz.', 'lte' => 'Net alan brüt alandan büyük olamaz.',
            'required' => ':attribute alanını doldurun.', 'in' => 'Kategori ile işlem türü eşleşmiyor.',
        ])->validate();

        return self::clean($data);
    }

    public static function decimal(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $value = trim(str_replace([' ', "\u{00a0}"], '', $value));
        if (str_contains($value, ',')) {
            return str_replace(',', '.', str_replace('.', '', $value));
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return str_replace('.', '', $value);
        }

        return $value;
    }
}

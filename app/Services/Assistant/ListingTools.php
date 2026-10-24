<?php

namespace App\Services\Assistant;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;

class ListingTools
{
    public function query(): Builder
    {
        return Listing::query()->published()->with(['images', 'eidsVerification']);
    }

    public function search(array $filters): array
    {
        return $this->searchPage($filters)['items'];
    }

    public function searchPage(array $filters, int $page = 1): array
    {
        $page = max(1, min(1000, $page));
        $query = $this->query();
        foreach (['city' => 'city_id', 'district' => 'district_id', 'neighborhood' => 'neighborhood_id', 'type' => 'type', 'category' => 'category', 'rooms' => 'room_count', 'heating' => 'heating_type', 'building_age' => 'building_age', 'total_floors' => 'total_floors', 'currency' => 'currency'] as $key => $column) {
            if (isset($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        if (isset($filters['categories']) && is_array($filters['categories'])) {
            $query->whereIn('category', array_slice($filters['categories'], 0, 5));
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        foreach (['min_price' => ['price', '>='], 'min_area' => ['square_meters', '>='], 'max_area' => ['square_meters', '<='], 'min_net_area' => ['net_square_meters', '>='], 'max_net_area' => ['net_square_meters', '<='], 'price_lt' => ['price', '<'], 'price_gt' => ['price', '>']] as $key => [$column, $operator]) {
            if (isset($filters[$key])) $query->where($column, $operator, $filters[$key]);
        }
        // Unknown amenities must never turn title/description mentions into confirmed matches.
        if (!empty($filters['features'])) throw new \InvalidArgumentException('Unsupported amenity filter');
        $floorValues = static fn ($n) => [(string) $n, $n.'. Kat', $n.'. kat', $n.' Kat', $n.' kat'];
        if (isset($filters['floor'])) $query->whereIn('floor_number', $filters['floor'] === '0' ? ['0', 'Zemin', 'Zemin Kat', 'Giriş', 'Giriş Kat'] : $floorValues($filters['floor']));
        if (!empty($filters['exclude_ground'])) {
            // Free-text floor fields must contain a recognised non-ground value;
            // missing or unrecognised values cannot prove a negative constraint.
            $knownFloors = ['Bodrum', 'Bodrum Kat', 'Çatı Katı'];
            foreach (range(-10, 200) as $floor) if ($floor !== 0) $knownFloors = array_merge($knownFloors, $floorValues($floor));
            $query->whereIn('floor_number', $knownFloors);
        }
        if (!empty($filters['exclude_shared'])) $query->whereIn('deed_status', ['Müstakil Parsel', 'Kat Mülkiyeti', 'Kat İrtifakı', 'Tam Tapu']);
        $records = $query->orderBy(($filters['sort'] ?? '') ? 'price' : 'id', ($filters['sort'] ?? '') === 'price_asc' ? 'asc' : 'desc')
            ->orderBy('id')->offset(($page - 1) * 4)->limit(5)->get();

        return ['items' => $records->take(4)->map(fn ($listing) => $this->card($listing))->all(), 'page' => $page, 'has_more' => $records->count() > 4];
    }

    public function details(array $ids): array
    {
        $found = $this->query()->whereKey(array_slice($ids, 0, 12))->get()->keyBy('id');

        return collect($ids)->unique()->map(fn ($id) => isset($found[$id]) ? $this->card($found[$id]) : null)->filter()->values()->all();
    }

    private function card(Listing $listing): array
    {
        $gross = (float) $listing->square_meters;
        $net = (float) $listing->net_square_meters;
        $price = (float) $listing->price;

        return [
            'id' => $listing->id, 'title' => $listing->title, 'url' => route('listings.show', $listing->slug),
            'price' => $price > 0 ? $price : null, 'type' => $listing->type, 'category' => $listing->category, 'currency' => $listing->currency, 'city' => $listing->city_name, 'district' => $listing->district_name, 'neighborhood' => $listing->neighborhood_id,
            'gross_area' => $gross > 0 ? $gross : null, 'net_area' => $net > 0 ? $net : null,
            'gross_unit_price' => $gross > 0 && $price > 0 ? round($price / $gross, 2) : null,
            'net_unit_price' => $net > 0 && $price > 0 ? round($price / $net, 2) : null,
            'rooms' => $listing->room_count, 'building_age' => $listing->building_age, 'floor' => $listing->floor_number,
            'heating' => $listing->heating_type, 'deed' => $listing->deed_status, 'usage' => $listing->usage_status,
            'date' => $listing->published_at,
            'image' => $listing->images->first() ? asset('storage/'.$listing->images->first()->image_path) : null,
        ];
    }
}

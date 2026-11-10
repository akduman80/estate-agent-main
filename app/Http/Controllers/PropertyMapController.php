<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Setting;
use App\Services\ListingPhoto;
use App\Services\TurkeyAddressService;
use Illuminate\Http\Request;

class PropertyMapController extends Controller
{
    public function index()
    {
        $categories = collect(Setting::get('options_category', []))->merge(['Konut', 'Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri'])->merge(Listing::query()->published()->distinct()->pluck('category'))->filter()->unique()->values()->all();

        return view('tools.property-map', ['cities' => TurkeyAddressService::getCities(), 'types' => Setting::get('options_type', ['Satılık', 'Kiralık']), 'categories' => $categories]);
    }

    public function records(Request $request)
    {
        $data = $request->validate([
            'south' => ['required', 'numeric', 'between:-90,90'], 'north' => ['required', 'numeric', 'between:-90,90', 'gte:south'],
            'west' => ['required', 'numeric', 'between:-180,180'], 'east' => ['required', 'numeric', 'between:-180,180', 'gte:west'],
            'city' => ['nullable', 'string', 'max:100'], 'district' => ['nullable', 'string', 'max:100'], 'neighborhood' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:1000000000000'], 'max_price' => ['nullable', 'numeric', 'min:0', 'max:1000000000000'],
            'min_area' => ['nullable', 'numeric', 'min:0', 'max:1000000000'], 'max_area' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
        $query = Listing::query()->published()->with('images')->whereBetween('latitude', [$data['south'], $data['north']])->whereBetween('longitude', [$data['west'], $data['east']]);
        // Privacy is filtered in SQL before pagination; no hidden coordinates enter JSON.
        $query->where(fn ($q) => $q->whereNull('form_details->hide_location')->orWhere('form_details->hide_location', false));
        foreach (['city' => 'city_id', 'district' => 'district_id', 'neighborhood' => 'neighborhood_id', 'type' => 'type', 'category' => 'category'] as $input => $column) {
            if (filled($data[$input] ?? null)) {
                $query->where($column, $data[$input]);
            }
        }
        foreach (['min_price' => ['price', '>='], 'max_price' => ['price', '<='], 'min_area' => ['square_meters', '>='], 'max_area' => ['square_meters', '<=']] as $input => [$column,$operator]) {
            if (isset($data[$input])) {
                $query->where($column, $operator, $data[$input]);
            }
        }
        $page = $query->orderBy('id')->simplePaginate(200);

        return response()->json(['data' => $page->getCollection()->map(fn ($listing) => [
            'id' => $listing->id, 'latitude' => (float) $listing->latitude, 'longitude' => (float) $listing->longitude,
            'title' => $listing->title, 'price' => number_format((float) $listing->price, 2, ',', '.').' '.$listing->currency,
            'url' => route('listings.show', $listing), 'photo' => $listing->images->first() ? ListingPhoto::url($listing->images->first()) : null,
        ]), 'more' => $page->hasMorePages(), 'page' => $page->currentPage()])->header('Cache-Control', 'private, no-store');
    }
}

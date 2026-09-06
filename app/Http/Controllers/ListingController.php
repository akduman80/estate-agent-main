<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Setting;
use App\Services\TurkeyAddressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $listings = Listing::query()
            ->with(['images', 'eidsVerification'])
            ->published()
            ->when($search !== '', function ($query) use ($search): void {
                if (ctype_digit($search)) {
                    $query->whereKey((int) $search);

                    return;
                }

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('city_id', 'like', '%'.$search.'%')
                        ->orWhere('district_id', 'like', '%'.$search.'%')
                        ->orWhere('neighborhood_id', 'like', '%'.$search.'%')
                        ->orWhere('eids_document_no', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('city'), fn ($query) => $query->where('city_id', (string) $request->string('city')))
            ->when($request->filled('district'), fn ($query) => $query->where('district_id', (string) $request->string('district')))
            ->when($request->filled('neighborhood'), fn ($query) => $query->where('neighborhood_id', (string) $request->string('neighborhood')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->string('type')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', (string) $request->string('category')))
            ->when($request->filled('min_price'), fn ($query) => $query->where('price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($query) => $query->where('price', '<=', $request->input('max_price')))
            ->when($request->filled('min_area'), fn ($query) => $query->where('square_meters', '>=', $request->input('min_area')))
            ->when($request->filled('max_area'), fn ($query) => $query->where('square_meters', '<=', $request->input('max_area')))
            ->when($request->input('sort') === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when(in_array($request->input('sort'), ['price', 'price_desc'], true), fn ($query) => $query->orderByDesc('price'))
            ->when($request->input('sort') === 'area_desc', fn ($query) => $query->orderByDesc('square_meters'))
            ->when(! in_array($request->input('sort'), ['price', 'price_desc', 'price_asc', 'area_desc'], true), fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        $recommendedListings = Listing::query()
            ->with(['images', 'eidsVerification'])
            ->published()
            ->orderByDesc('is_featured')
            ->latest()
            ->take(4)
            ->get();

        $publishedCategories = Listing::query()
            ->published()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $categoryOptions = collect(Setting::get('options_category', ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri']))
            ->merge($publishedCategories)
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return view('listings.index', [
            'listings' => $listings,
            'recommendedListings' => $recommendedListings,
            'cities' => TurkeyAddressService::getCities(),
            'typeOptions' => Setting::get('options_type', ['Satılık', 'Kiralık']),
            'categoryOptions' => $categoryOptions,
        ]);
    }

    public function show(Listing $listing): View
    {
        abort_unless(Listing::query()->published()->whereKey($listing->getKey())->exists(), 404);

        $listing->load(['images', 'eidsVerification', 'businessProfile', 'user.businessProfile']);

        $similarListings = Listing::query()
            ->with(['images', 'eidsVerification'])
            ->published()
            ->where('id', '!=', $listing->getKey())
            ->when($listing->category, fn ($query) => $query->where('category', $listing->category))
            ->orderByDesc('is_featured')
            ->latest()
            ->take(3)
            ->get();

        if ($similarListings->count() < 3) {
            $additionalListings = Listing::query()
                ->with(['images', 'eidsVerification'])
                ->published()
                ->where('id', '!=', $listing->getKey())
                ->whereNotIn('id', $similarListings->modelKeys())
                ->orderByDesc('is_featured')
                ->latest()
                ->take(3 - $similarListings->count())
                ->get();

            $similarListings = $similarListings->concat($additionalListings);
        }

        return view('listings.show', compact('listing', 'similarListings'));
    }
}

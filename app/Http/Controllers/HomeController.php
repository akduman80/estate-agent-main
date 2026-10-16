<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use App\Services\MarketDataService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(MarketDataService $marketData): View
    {
        $recentListings = Listing::query()->with(['images', 'eidsVerification'])->published()
            ->orderByDesc('is_featured')->latest()->take(6)->get();

        $featuredPost = Post::query()->with('category')->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('is_featured')->latest('published_at')->first();

        $featuredProject = Project::query()->published()->orderByDesc('is_featured')->latest('published_at')->first();


        return view('welcome', [
            'recentListings' => $recentListings,
            'featuredPost' => $featuredPost,
            'featuredProject' => $featuredProject,
            'marketData' => $marketData->current(),
            'rentRate' => Setting::get('rent_increase_rate'),
            'rentPeriod' => Setting::get('rent_increase_period'),
            'rentUpdatedAt' => Setting::get('rent_increase_updated_at'),
            'preferredListingCount' => auth()->user()?->featured_listing_count ?? 3,
        ]);
    }
}

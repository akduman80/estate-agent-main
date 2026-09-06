<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Poll;
use App\Models\Post;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $recentListings = Listing::query()->with(['images', 'eidsVerification'])->published()
            ->orderByDesc('is_featured')->latest()->take(6)->get();

        $latestPosts = Post::query()->with('category')->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')->take(3)->get();

        $activePoll = Poll::query()->with('options')->currentlyActive()->latest('starts_at')->first();

        return view('welcome', compact('recentListings', 'latestPosts', 'activePoll'));
    }
}

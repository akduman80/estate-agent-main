<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function show(Post $post): View
    {
        abort_unless($post->status === 'published' && (! $post->published_at || $post->published_at->isPast()), 404);

        $post->load(['author', 'category']);

        $latestPosts = Post::query()->with('category')->where('status', 'published')->whereKeyNot($post->getKey())
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')->take(3)->get();

        $relatedPosts = Post::query()->with('category')->where('status', 'published')->whereKeyNot($post->getKey())
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->when($post->category_id, fn ($query) => $query->where('category_id', $post->category_id))
            ->latest('published_at')->take(3)->get();

        if ($relatedPosts->count() < 3) {
            $fallbackPosts = Post::query()->with('category')->where('status', 'published')->whereKeyNot($post->getKey())
                ->whereNotIn('id', $relatedPosts->pluck('id'))
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->latest('published_at')->take(3 - $relatedPosts->count())->get();

            $relatedPosts = $relatedPosts->concat($fallbackPosts);
        }

        $previousPost = Post::query()->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where('published_at', '<', $post->published_at)->latest('published_at')->first();

        $nextPost = Post::query()->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where('published_at', '>', $post->published_at)->oldest('published_at')->first();

        return view('blogs.show', compact('post', 'previousPost', 'nextPost', 'latestPosts', 'relatedPosts'));
    }
}

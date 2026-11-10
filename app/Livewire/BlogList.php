<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BlogList extends Component
{
    use WithPagination;

    #[Url(as: 'kategori')]
    public $selectedCategory = null;

    #[Url(as: 'ara')]
    public string $search = '';

    #[Url]
    public string $sort = 'newest';

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectCategory($categoryId = null): void
    {
        $this->selectedCategory = $categoryId ?: null;
        $this->resetPage();
    }

    public function render()
    {
        $posts = Post::query()
            ->with(['author', 'category'])
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->when($this->search, function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term);
                });
            })
            ->when($this->selectedCategory, fn ($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->sort === 'likes', fn ($q) => $q->orderByDesc(DB::table('property_likes')->selectRaw('count(*)')->whereColumn('property_likes.post_id', 'posts.id')))
            ->when($this->sort === 'comments', fn ($q) => $q->orderByDesc(DB::table('property_comments')->selectRaw('count(*)')->whereColumn('property_comments.post_id', 'posts.id')->where('status', 'approved')->whereNull('parent_id')))
            ->latest('published_at')
            ->paginate(9);

        // Kategorileri Redis önbelleğinden çekiyoruz
        $categories = Cache::remember('blog_categories', 3600, function () {
            return Category::withCount(['posts' => fn ($q) => $q
                ->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now())),
            ])->get();
        });

        return view('livewire.blog-list', [
            'posts' => $posts,
            'categories' => $categories,
        ])->layout('components.layouts.modern', [
            'title' => 'Tüm Emlak Haberleri | ZYDEMLAK',
            'description' => "Osmaniye ve Türkiye'den güncel emlak, tapu, konut, kira, yatırım ve gayrimenkul haberleri.",
        ]);
    }
}

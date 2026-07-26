<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class BlogList extends Component
{
    use WithPagination;

    public $selectedCategory = null;

    public function render()
    {
        $posts = Post::query()
                     ->with(['author', 'category'])
                     ->where('status', 'published')
                     ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
                     ->latest('published_at')
                     ->paginate(9);

        // Kategorileri Redis önbelleğinden çekiyoruz
        $categories = Cache::remember('blog_categories', 3600, function () {
            return \App\Models\Category::withCount(['posts' => fn($q) => $q->where('status', 'published')])->get();
        });

        return view('livewire.blog-list', [
            'posts'      => $posts,
            'categories' => $categories,
        ]);
    }
}

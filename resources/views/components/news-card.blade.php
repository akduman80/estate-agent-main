@props(['post'])

@php
    $imageUrl = $post->cover_image ? \Illuminate\Support\Facades\Storage::url($post->cover_image) : asset('fe-assets/assets/img/blog2.jpg');
    $date = ($post->published_at ?? $post->created_at)?->locale('tr')->translatedFormat('d F Y');
@endphp

<article class="news-card group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('blogs.show', $post->slug) }}" class="flex h-full flex-col">
        <div class="relative h-52 overflow-hidden">
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
            @if($post->category)
                <span class="absolute left-3 top-3 rounded-lg bg-orange-500 px-2.5 py-1 text-[10px] font-bold uppercase text-white shadow">{{ $post->category->name }}</span>
            @endif
        </div>
        <div class="flex flex-1 flex-col p-5">
            <div class="mb-3 text-[10px] text-slate-400"><i class="fa-regular fa-calendar mr-1"></i>{{ $date }}</div>
            <h2 class="line-clamp-2 text-base font-bold leading-6 text-blue-950 transition group-hover:text-orange-600">{{ $post->title }}</h2>
            <p class="mt-3 line-clamp-3 text-xs leading-5 text-slate-500">{{ strip_tags($post->summary ?? '') ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 145) }}</p>
            <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4 text-xs font-bold text-orange-600">
                <span>Haberi Oku</span><i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>
    </a>
</article>

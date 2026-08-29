@props(['post', 'large' => false])

@php
    $imageUrl = $post->cover_image ? \Illuminate\Support\Facades\Storage::url($post->cover_image) : asset('fe-assets/assets/img/blog2.jpg');
    $date = ($post->published_at ?? $post->created_at)?->locale('tr')->translatedFormat('d F Y');
@endphp

<a href="{{ route('blogs.show', $post->slug) }}" class="group block">
    @if($large)
        <div class="relative mb-2 h-32 overflow-hidden rounded-xl">
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
            <span class="absolute bottom-2 left-2 text-[10px] font-medium text-orange-300">{{ $date }}</span>
        </div>
        <span class="mb-1 block text-[10px] font-bold uppercase text-orange-500">{{ $post->category?->name ?? 'Emlak' }}</span>
        <h3 class="line-clamp-2 text-xs font-semibold leading-5 text-slate-800 transition group-hover:text-orange-600">{{ $post->title }}</h3>
    @else
        <div class="flex gap-3">
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-20 w-24 shrink-0 rounded-lg object-cover" loading="lazy">
            <div>
                <span class="text-[10px] text-orange-500">{{ $date }}</span>
                <h3 class="mt-1 line-clamp-3 text-xs font-bold leading-5 text-slate-700 transition group-hover:text-orange-600">{{ $post->title }}</h3>
            </div>
        </div>
    @endif
</a>

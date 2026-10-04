@props(['listing'])

@php
    $mainImage = $listing->images->first()?->image_path;
    $imageUrl = $mainImage ? \Illuminate\Support\Facades\Storage::url($mainImage) : asset('fe-assets/assets/img/demo/property-1.jpg');
@endphp

<article class="listing-card group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('listings.show', $listing->slug) }}" class="relative block aspect-[4/3] overflow-hidden">
        <img src="{{ $imageUrl }}" alt="{{ $listing->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        <span class="absolute left-3 top-3 rounded-md bg-orange-500 px-2.5 py-1 text-xs font-bold text-white shadow">{{ $listing->type }}</span>
        @if($listing->city_name || $listing->district_name)
            <span class="absolute bottom-3 left-3 rounded bg-blue-950/85 px-2 py-1 text-xs font-medium text-white backdrop-blur">{{ collect([$listing->city_name, $listing->district_name])->filter()->join(' / ') }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-4">
        <div class="mb-1 text-lg font-bold text-orange-600">{{ \App\Support\ListingDecimal::format($listing->price) }} {{ $listing->currency === 'TRY' || ! $listing->currency ? 'TL' : $listing->currency }}</div>
        <h2 class="line-clamp-2 text-sm font-semibold leading-5 text-slate-800 transition group-hover:text-orange-600"><a href="{{ route('listings.show', $listing->slug) }}">{{ $listing->title }}</a></h2>
        <div class="mt-auto flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
            <span><i class="fa-solid fa-ruler-combined mr-1 text-orange-500"></i>{{ $listing->square_meters ? \App\Support\ListingDecimal::format($listing->square_meters).' m²' : '-' }}</span>
            <span class="truncate"><i class="fa-regular fa-building mr-1 text-orange-500"></i>{{ collect([$listing->room_count, $listing->category])->filter()->join(' · ') }}</span>
        </div>
    </div>
</article>

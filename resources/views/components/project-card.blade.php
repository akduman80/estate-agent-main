@props(['project', 'compact' => false])

@php
    $imageUrls = collect([$project->cover_image])
        ->merge($compact ? [] : $project->images->pluck('image_path'))
        ->filter()
        ->unique()
        ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
        ->values();
    $imageUrls = $imageUrls->whenEmpty(fn ($images) => $images->push(asset('fe-assets/assets/img/demo/property-4.jpg')));
    $videoUrl = $project->video_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($project->video_path) : null;
    $youtubeId = $project->youtube_video_id;
    $mediaCount = $imageUrls->count() + (($videoUrl || $youtubeId) ? 1 : 0);
    $statusLabel = match ($project->status) {
        'planning' => 'Planlanıyor',
        'completed' => 'Tamamlandı',
        default => 'Devam Ediyor',
    };
@endphp

<article {{ $attributes->class(['group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    <div data-project-card-gallery class="relative overflow-hidden bg-slate-100">
        <div data-project-card-track class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach($imageUrls as $index => $imageUrl)
                <a href="{{ route('projects.show', $project->slug) }}" class="block min-w-full snap-start" aria-label="{{ $project->title }} detayına git, {{ $index + 1 }}. fotoğraf">
                    <img src="{{ $imageUrl }}" alt="{{ $project->title }} - {{ $index + 1 }}. fotoğraf" class="aspect-[4/3] w-full object-cover" loading="lazy">
                </a>
            @endforeach

            @if($videoUrl)
                <div class="flex aspect-[4/3] min-w-full snap-start items-center justify-center bg-slate-950">
                    <video data-project-card-video controls playsinline preload="metadata" class="h-full w-full object-contain">
                        <source src="{{ $videoUrl }}">
                        Tarayıcınız video oynatmayı desteklemiyor.
                    </video>
                </div>
            @elseif($youtubeId)
                <div class="aspect-[4/3] min-w-full snap-start bg-slate-950">
                    <iframe
                        data-project-card-video
                        data-video-src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0"
                        title="{{ $project->title }} videosu"
                        class="h-full w-full"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            @endif
        </div>

        @if($mediaCount > 1)
            <button type="button" data-project-card-previous class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/85 text-white shadow-lg transition hover:bg-orange-500" aria-label="Önceki proje fotoğrafı">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button type="button" data-project-card-next class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/85 text-white shadow-lg transition hover:bg-orange-500" aria-label="Sonraki proje fotoğrafı">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
            <span data-project-card-counter class="absolute bottom-2 right-2 rounded-full bg-slate-950/80 px-2.5 py-1 text-[10px] font-extrabold text-white">1 / {{ $mediaCount }}</span>
            <span class="absolute bottom-2 left-2 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold text-blue-950 sm:hidden"><i class="fa-solid fa-arrows-left-right mr-1 text-orange-500"></i>Kaydır</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col {{ $compact ? 'p-3' : 'p-5' }}">
        <span class="text-[11px] font-extrabold uppercase tracking-wide text-orange-600">{{ $statusLabel }}</span>
        <h2 class="mt-1 line-clamp-2 font-extrabold text-blue-950"><a href="{{ route('projects.show', $project->slug) }}">{{ $project->title }}</a></h2>
        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $project->developer_name }}</p>
        @if($project->location)
            <p class="mt-2 line-clamp-1 text-xs text-slate-600"><i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>{{ $project->location }}</p>
        @endif
    </div>
</article>

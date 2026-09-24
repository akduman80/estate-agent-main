@props(['project', 'compact' => false])

@php
    $imageUrl = $project->cover_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover_image) : asset('fe-assets/assets/img/demo/property-4.jpg');
    $statusLabel = match ($project->status) {
        'planning' => 'Planlanıyor',
        'completed' => 'Tamamlandı',
        default => 'Devam Ediyor',
    };
@endphp

<article {{ $attributes->class(['group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    <a href="{{ route('projects.show', $project->slug) }}" class="block overflow-hidden">
        <img src="{{ $imageUrl }}" alt="{{ $project->title }}" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
    </a>
    <div class="flex flex-1 flex-col {{ $compact ? 'p-3' : 'p-5' }}">
        <span class="text-[11px] font-extrabold uppercase tracking-wide text-orange-600">{{ $statusLabel }}</span>
        <h2 class="mt-1 line-clamp-2 font-extrabold text-blue-950"><a href="{{ route('projects.show', $project->slug) }}">{{ $project->title }}</a></h2>
        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $project->developer_name }}</p>
        @if($project->location)
            <p class="mt-2 line-clamp-1 text-xs text-slate-600"><i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>{{ $project->location }}</p>
        @endif
    </div>
</article>

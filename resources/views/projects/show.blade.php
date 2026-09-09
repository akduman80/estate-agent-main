@php
    $imageUrl = $project->cover_image ? \Illuminate\Support\Facades\Storage::url($project->cover_image) : asset('fe-assets/assets/img/demo/property-4.jpg');
    $statusLabel = match ($project->status) { 'planning' => 'Planlanıyor', 'completed' => 'Tamamlandı', default => 'Devam Ediyor' };
@endphp
<x-layouts.modern :title="$project->title.' | ZYDEMLAK'" :description="$project->summary">
    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <img src="{{ $imageUrl }}" alt="{{ $project->title }}" class="max-h-[560px] w-full object-cover">
            <div class="p-6 sm:p-9">
                <span class="text-xs font-extrabold uppercase tracking-wide text-orange-600">{{ $statusLabel }}</span>
                <h1 class="mt-2 text-3xl font-extrabold text-blue-950">{{ $project->title }}</h1>
                <p class="mt-2 font-semibold text-slate-600">{{ $project->developer_name }}</p>
                @if($project->location)<p class="mt-3 text-sm text-slate-500"><i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>{{ $project->location }}</p>@endif
                @if($project->summary)<p class="mt-6 text-lg font-semibold leading-8 text-slate-700">{{ $project->summary }}</p>@endif
                @if($project->content)<div class="article-content mt-6 text-slate-700">{!! $project->content !!}</div>@endif
            </div>
        </div>
    </main>
</x-layouts.modern>

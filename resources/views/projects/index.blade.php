<x-layouts.modern title="Projeler | ZYDEMLAK" description="İnşaat firmaları ve müteahhitlerden güncel konut ve ticari gayrimenkul projeleri.">
    <main class="flex-1 bg-slate-50">
        <section class="bg-blue-950 py-10 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-xs font-bold uppercase tracking-[.2em] text-orange-400">Yeni yaşam ve yatırım alanları</p>
                <h1 class="mt-2 text-3xl font-extrabold">Projeler</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100">Müteahhit ve inşaat firmalarının devam eden veya tamamlanan projelerini keşfedin.</p>
            </div>
        </section>
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div id="project-map" class="mb-8 zyd-projects-map" data-zyd-map data-config="{{ json_encode(['projects' => $mapProjects], JSON_UNESCAPED_UNICODE) }}"></div>
            <a href="{{ route('shared-map') }}" class="mb-4 inline-block font-bold text-blue-700">İlan ve proje haritasını aç</a>

            <form method="GET" action="{{ route('projects.index') }}" class="mb-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4" aria-label="Proje konum filtreleri">
                <label class="block text-xs font-bold text-slate-600">
                    <span class="mb-2 block"><i class="fa-solid fa-location-dot mr-1.5 text-slate-500"></i>İl</span>
                    <select name="city" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                        <option value="">Tümü</option>
                        @foreach($filterOptions['cities'] as $city)<option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-xs font-bold text-slate-600">
                    <span class="mb-2 block"><i class="fa-solid fa-building mr-1.5 text-slate-500"></i>İlçe</span>
                    <select name="district" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                        <option value="">Tümü</option>
                        @foreach($filterOptions['districts'] as $district)<option value="{{ $district }}" @selected(($filters['district'] ?? '') === $district)>{{ $district }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-xs font-bold text-slate-600">
                    <span class="mb-2 block"><i class="fa-solid fa-house mr-1.5 text-slate-500"></i>Mahalle / Köy</span>
                    <select name="neighborhood" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                        <option value="">Tümü</option>
                        @foreach($filterOptions['neighborhoods'] as $neighborhood)<option value="{{ $neighborhood }}" @selected(($filters['neighborhood'] ?? '') === $neighborhood)>{{ $neighborhood }}</option>@endforeach
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-orange-600"><i class="fa-solid fa-magnifying-glass"></i>Filtrele</button>
                    @if(array_filter($filters))<a href="{{ route('projects.index') }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600" aria-label="Filtreleri temizle"><i class="fa-solid fa-xmark"></i></a>@endif
                </div>
            </form>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($projects as $project)
                    <x-project-card :project="$project" />
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">Henüz yayınlanmış proje bulunmuyor.</div>
                @endforelse
            </div>
            @if($projects->hasPages())<div class="mt-8">{{ $projects->links() }}</div>@endif
        </section>
    </main>

    @push('head')
        @include('partials.zyd-map-assets')
        <style>
            #project-map.zyd-projects-map .zyd-shared-map {
                display: flex;
                flex-direction: column;
                width: 100%;
                height: 72dvh;
                min-height: 560px;
                max-height: 900px;
            }

            #project-map.zyd-projects-map .zyd-map-top,
            #project-map.zyd-projects-map .zyd-map-controls {
                flex: 0 0 auto;
            }

            #project-map.zyd-projects-map .zyd-map-canvas {
                flex: 1 1 auto;
                width: 100%;
                min-height: 240px;
                height: auto !important;
            }

            #project-map.zyd-projects-map .zyd-map-card {
                position: relative;
                inset: auto;
                flex: 1 1 0;
                width: auto;
                max-width: none;
                max-height: none;
                min-height: 120px;
                margin: 0 12px 12px;
                overflow: auto;
                overscroll-behavior: contain;
            }

            #project-map.zyd-projects-map:has(.zyd-map-card) .zyd-map-canvas {
                flex: 0 0 48%;
                min-height: 230px;
            }

            #project-map.zyd-projects-map .zyd-shared-map.zyd-map-expanded {
                z-index: 10000;
                width: 100vw;
                height: 100dvh;
                min-height: 0;
                max-height: none;
                border-radius: 0;
            }

            @media (max-width: 640px) {
                #project-map.zyd-projects-map .zyd-shared-map {
                    height: 70dvh;
                    min-height: 460px;
                    max-height: none;
                }

                #project-map.zyd-projects-map .zyd-map-top {
                    gap: 8px;
                    padding: 10px;
                }

                #project-map.zyd-projects-map .zyd-map-controls {
                    gap: 6px;
                    padding: 8px;
                }

                #project-map.zyd-projects-map .zyd-map-controls button {
                    min-width: 0;
                    padding: 9px 6px;
                    font-size: 12px;
                }

                #project-map.zyd-projects-map:has(.zyd-map-card) .zyd-map-canvas {
                    flex-basis: 42%;
                    min-height: 190px;
                }

                #project-map.zyd-projects-map .zyd-map-card {
                    min-height: 100px;
                    margin: 0 8px 8px;
                    padding: 10px;
                }
            }
        </style>
        <script>
            (() => {
                const host = document.getElementById('project-map');
                if (!host || !('ResizeObserver' in window)) return;

                let observedCanvas = null;
                const observer = new ResizeObserver(() => window.dispatchEvent(new Event('resize')));
                const syncCanvas = () => {
                    const canvas = host.querySelector('.zyd-map-canvas');
                    if (canvas === observedCanvas) return;
                    observer.disconnect();
                    observedCanvas = canvas;
                    if (canvas) observer.observe(canvas);
                };

                new MutationObserver(syncCanvas).observe(host, { childList: true, subtree: true });
                syncCanvas();
            })();
        </script>
    @endpush
</x-layouts.modern>

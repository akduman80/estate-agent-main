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
            @if($mapProjects->isNotEmpty())
                <section class="mb-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="project-map-heading">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 id="project-map-heading" class="text-lg font-extrabold text-blue-950">Projeler Haritası</h2>
                        <p class="mt-1 text-xs text-slate-500">Yayındaki projelerin uygulamadan gönderilen konumlarını inceleyin.</p>
                    </div>
                    <div class="bg-blue-950 px-4 py-3 text-white sm:flex sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3 font-extrabold">
                            <i class="fa-solid fa-location-dot text-orange-400"></i>
                            <span>{{ $filters['city'] ?? 'Osmaniye' }} Proje Haritası</span>
                        </div>
                        <span class="mt-2 inline-flex items-center gap-2 rounded-lg bg-sky-500 px-3 py-1.5 text-xs font-bold sm:mt-0"><i class="fa-solid fa-map"></i> Normal Harita</span>
                    </div>
                    <div class="relative">
                        <div id="project-map" class="h-[420px] w-full bg-slate-100 sm:h-[520px]" role="application" aria-label="Yayındaki projelerin haritası"></div>
                        <div data-project-map-hint class="pointer-events-none absolute left-14 right-3 top-3 max-w-xs rounded-xl border border-orange-400 bg-blue-950/95 px-4 py-3 text-xs leading-5 text-white shadow-lg" style="z-index: 1000">
                            <i class="fa-regular fa-lightbulb mr-1.5 text-amber-300"></i>İşaretçiye dokunarak proje bilgilerini ve detay sayfasını açabilirsiniz.
                        </div>
                    </div>
                </section>
            @endif

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

    @if($mapProjects->isNotEmpty())
        @push('head')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
            <style>
                .project-map-pin {
                    display: block;
                    width: 36px;
                    height: 48px;
                    filter: drop-shadow(0 4px 5px rgb(15 23 42 / 35%));
                }
                .leaflet-popup-content-wrapper { border-radius: 14px; }
                .leaflet-popup-content { margin: 14px; }
            </style>
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const projects = @json($mapProjects);
                    const map = L.map('project-map', { scrollWheelZoom: false }).setView([projects[0].latitude, projects[0].longitude], 13);
                    const bounds = L.latLngBounds();
                    const mapHint = document.querySelector('[data-project-map-hint]');

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    }).addTo(map);

                    const pinIcon = L.divIcon({
                        className: '',
                        html: '<svg class="project-map-pin" viewBox="0 0 36 48" aria-hidden="true"><path d="M18 1C8.6 1 1 8.6 1 18c0 12.8 17 28.5 17 28.5S35 30.8 35 18C35 8.6 27.4 1 18 1Z" fill="#ef2929" stroke="#fff" stroke-width="2.5"/><circle cx="18" cy="18" r="7" fill="#b91c1c"/></svg>',
                        iconSize: [36, 48],
                        iconAnchor: [18, 47],
                        popupAnchor: [0, -44],
                    });

                    const projectCard = (project) => {
                        const container = document.createElement('article');
                        container.className = 'w-64 max-w-[72vw]';

                        const media = project.image_urls.map((url) => ({ type: 'image', url }));
                        if (project.video_url) media.push({ type: 'video', url: project.video_url });
                        if (project.youtube_id) media.push({ type: 'youtube', id: project.youtube_id });

                        if (media.length) {
                            const gallery = document.createElement('div');
                            gallery.className = 'relative mb-3';

                            const viewer = document.createElement('div');
                            viewer.className = 'h-32 w-full overflow-hidden rounded-lg bg-slate-950';
                            gallery.append(viewer);

                            const renderMedia = (item) => {
                                let element;
                                if (item.type === 'image') {
                                    element = document.createElement('img');
                                    element.src = item.url;
                                    element.alt = project.title;
                                    element.className = 'h-full w-full object-cover';
                                } else if (item.type === 'video') {
                                    element = document.createElement('video');
                                    element.src = item.url;
                                    element.controls = true;
                                    element.playsInline = true;
                                    element.preload = 'metadata';
                                    element.className = 'h-full w-full object-contain';
                                } else {
                                    element = document.createElement('iframe');
                                    element.src = `https://www.youtube-nocookie.com/embed/${item.id}?rel=0`;
                                    element.title = `${project.title} videosu`;
                                    element.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                                    element.allowFullscreen = true;
                                    element.className = 'h-full w-full';
                                }
                                viewer.replaceChildren(element);
                            };

                            renderMedia(media[0]);

                            if (media.length > 1) {
                                const count = document.createElement('span');
                                count.className = 'absolute bottom-2 right-2 rounded-full bg-slate-950/80 px-2 py-1 text-[10px] font-bold text-white';
                                count.textContent = `1 / ${media.length}`;
                                gallery.append(count);

                                const thumbnails = document.createElement('div');
                                thumbnails.className = 'mt-2 flex gap-1.5 overflow-x-auto pb-1';

                                media.forEach((item, index) => {
                                    const thumbnailButton = document.createElement('button');
                                    thumbnailButton.type = 'button';
                                    thumbnailButton.className = `flex h-11 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md border-2 bg-slate-950 ${index === 0 ? 'border-orange-500' : 'border-transparent opacity-70'}`;
                                    thumbnailButton.setAttribute('aria-label', item.type === 'image' ? `${index + 1}. fotoğrafı göster` : 'Proje videosunu göster');

                                    if (item.type === 'image') {
                                        const thumbnail = document.createElement('img');
                                        thumbnail.src = item.url;
                                        thumbnail.alt = '';
                                        thumbnail.className = 'h-full w-full object-cover';
                                        thumbnailButton.append(thumbnail);
                                    } else {
                                        const playIcon = document.createElement('i');
                                        playIcon.className = 'fa-solid fa-play text-sm text-white';
                                        thumbnailButton.append(playIcon);
                                    }
                                    thumbnailButton.addEventListener('click', () => {
                                        renderMedia(item);
                                        count.textContent = `${index + 1} / ${media.length}`;
                                        [...thumbnails.children].forEach((button, buttonIndex) => {
                                            button.classList.toggle('border-orange-500', buttonIndex === index);
                                            button.classList.toggle('border-transparent', buttonIndex !== index);
                                            button.classList.toggle('opacity-70', buttonIndex !== index);
                                        });
                                    });
                                    thumbnails.append(thumbnailButton);
                                });

                                gallery.append(thumbnails);
                            }

                            container.append(gallery);
                        }

                        const title = document.createElement('h3');
                        title.className = 'font-bold text-blue-950';
                        title.textContent = project.title;
                        container.append(title);

                        if (project.location) {
                            const location = document.createElement('p');
                            location.className = 'mt-1 text-xs text-slate-600';
                            location.textContent = project.location;
                            container.append(location);
                        }

                        if (project.summary) {
                            const summary = document.createElement('p');
                            summary.className = 'mt-2 text-xs text-slate-500';
                            summary.textContent = project.summary;
                            container.append(summary);
                        }

                        const link = document.createElement('a');
                        link.href = project.url;
                        link.className = 'mt-3 inline-block font-bold text-orange-600';
                        link.textContent = 'Proje detayını aç';
                        container.append(link);

                        return container;
                    };

                    projects.forEach((project) => {
                        const position = [project.latitude, project.longitude];
                        const marker = L.marker(position, { icon: pinIcon, title: project.title })
                            .addTo(map)
                            .bindPopup(projectCard(project));
                        marker.on('click', () => mapHint?.classList.add('hidden'));
                        bounds.extend(position);
                    });

                    if (projects.length > 1) {
                        map.fitBounds(bounds, { padding: [45, 45], maxZoom: 14 });
                    }
                });
            </script>
        @endpush
    @endif
</x-layouts.modern>

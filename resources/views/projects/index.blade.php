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
                    @if($googleMapsKey)
                        <div id="project-map" class="h-[420px] w-full bg-slate-100 sm:h-[520px]" role="application" aria-label="Yayındaki projelerin Google Maps haritası"></div>
                    @else
                        <div class="flex min-h-40 items-center justify-center px-6 text-center text-sm text-slate-500">
                            Proje konumları hazır; haritanın gösterilebilmesi için web sunucusunda Google Maps tarayıcı anahtarı yapılandırılmalıdır.
                        </div>
                    @endif
                </section>
            @endif

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

    @if($mapProjects->isNotEmpty() && $googleMapsKey)
        @push('head')
            <style>
                .project-map-marker-label {
                    max-width: 180px;
                    overflow: hidden;
                    transform: translateY(-30px);
                    border: 1px solid #991b1b;
                    border-radius: 8px;
                    background: #dc2626;
                    box-shadow: 0 4px 12px rgb(15 23 42 / 25%);
                    padding: 4px 8px;
                    color: #fff !important;
                    font-weight: 800;
                    line-height: 1.2;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
            </style>
        @endpush
        @push('scripts')
            <script>
                window.initProjectMap = function () {
                    const projects = @json($mapProjects);
                    const bounds = new google.maps.LatLngBounds();
                    const map = new google.maps.Map(document.getElementById('project-map'), {
                        center: { lat: projects[0].latitude, lng: projects[0].longitude },
                        zoom: 12,
                        mapTypeControl: false,
                        streetViewControl: false,
                    });
                    const infoWindow = new google.maps.InfoWindow();

                    const openProjectCard = (marker, project) => {
                        const container = document.createElement('article');
                        container.className = 'max-w-64';

                        if (project.image_url) {
                            const image = document.createElement('img');
                            image.src = project.image_url;
                            image.alt = project.title;
                            image.className = 'mb-2 h-28 w-full rounded-lg object-cover';
                            container.append(image);
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

                        infoWindow.setContent(container);
                        infoWindow.open({ map, anchor: marker });
                    };

                    projects.forEach((project) => {
                        const position = { lat: project.latitude, lng: project.longitude };
                        const marker = new google.maps.Marker({
                            map,
                            position,
                            title: project.title,
                            label: {
                                text: project.title,
                                color: '#ffffff',
                                fontSize: '11px',
                                fontWeight: '800',
                                className: 'project-map-marker-label',
                            },
                            animation: google.maps.Animation.DROP,
                        });

                        marker.addListener('click', () => openProjectCard(marker, project));
                        marker.addListener('mouseover', () => openProjectCard(marker, project));
                        bounds.extend(position);
                    });

                    if (projects.length > 1) {
                        map.fitBounds(bounds, 56);
                    }
                };
            </script>
            <script async src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsKey) }}&callback=initProjectMap&loading=async"></script>
        @endpush
    @endif
</x-layouts.modern>

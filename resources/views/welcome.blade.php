<x-layouts.modern title="ZYDEMLAK - Gayrimenkul ve İlan Portalı" description="Osmaniye satılık ve kiralık emlak ilanları, projeler ve güncel emlak haberleri.">
    @php
        $typeOptions = \App\Models\Setting::get('options_type', ['Satılık', 'Kiralık']);
        $cities = \App\Services\TurkeyAddressService::getCities();
        $rentRateNumber = is_numeric(str_replace(',', '.', (string) $rentRate)) ? (float) str_replace(',', '.', (string) $rentRate) : null;
    @endphp

    <div class="home-dashboard">
        <section class="home-search-section border-b border-blue-900 bg-blue-950 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <form method="GET" action="{{ route('listings.index') }}" class="home-search-form grid items-end gap-3 rounded-2xl bg-white p-4 shadow-lg sm:grid-cols-2 lg:grid-cols-8" data-location-selects>
                    <div class="home-search-keyword lg:col-span-2"><label for="home-q" class="mb-1 block text-xs font-bold text-blue-950">Kelime / İlan No</label><div class="relative"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-blue-900"></i><input id="home-q" name="q" type="search" placeholder="Örn: 3+1 Daire, Arsa..." class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-3 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200"></div></div>
                    <div class="home-search-type"><label for="home-category" class="mb-1 block text-xs font-bold text-blue-950">Gayrimenkul Tipi</label><select id="home-category" name="category" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700"><option value="">Tümü</option>@foreach(\App\Models\Setting::get('options_category', ['Konut', 'Arsa', 'İşyeri']) as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="home-search-status"><label for="home-type" class="mb-1 block text-xs font-bold text-blue-950">İlan Durumu</label><select id="home-type" name="type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700"><option value="">Hepsi</option>@foreach($typeOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="home-search-city"><label for="home-city" class="mb-1 block text-xs font-bold text-blue-950">İl</label><select id="home-city" name="city" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700" data-city-select data-districts-url="{{ route('locations.districts') }}" data-neighborhoods-url="{{ route('locations.neighborhoods') }}"><option value="">Tüm İller</option>@foreach($cities as $key => $city)<option value="{{ $key }}">{{ $city }}</option>@endforeach</select></div>
                    <div class="home-search-district"><label for="home-district" class="mb-1 block text-xs font-bold text-blue-950">İlçe</label><select id="home-district" name="district" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700" data-district-select disabled><option value="">Önce il seçin</option></select></div>
                    <div class="home-search-neighborhood"><label for="home-neighborhood" class="mb-1 block text-xs font-bold text-blue-950">Mahalle / Köy</label><select id="home-neighborhood" name="neighborhood" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700" data-neighborhood-select disabled><option value="">Önce ilçe seçin</option></select></div>
                    <button type="submit" class="home-search-button flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-4 py-3 font-extrabold text-white shadow-sm transition hover:bg-orange-600"><i class="fa-solid fa-magnifying-glass"></i><span>İlan Ara</span></button>
                </form>
            </div>
        </section>

        @if($rentRateNumber !== null || !empty($marketData))
            <section id="piyasalar" class="home-market-section px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-7xl grid-cols-2 gap-3 lg:grid-cols-[1fr_1.7fr]">
                    @if($rentRateNumber !== null)
                        <article @class(['flex min-h-36 flex-col rounded-2xl border border-blue-100 bg-white p-4 shadow-sm lg:min-h-0 lg:flex-row lg:items-center lg:gap-7', 'col-span-2 lg:col-span-1' => empty($marketData)])>
                            <div class="min-w-0 flex-1"><h2 class="text-sm font-extrabold text-blue-950"><span class="phone-rent-title">Kira Artış Oranı</span><span class="desktop-rent-title">{{ $rentPeriod }} Kira Artış Oranı</span></h2><p class="mt-2 text-3xl font-black text-orange-600">%{{ number_format($rentRateNumber, 2, ',', '.') }}</p><p class="phone-rent-title mt-1 text-xs font-semibold text-blue-950">{{ $rentPeriod }}</p></div>
                            <div class="mt-auto pt-3 text-right text-[11px] text-slate-500 lg:mt-0 lg:pt-0">@if($rentUpdatedAt)<span class="block">Güncelleme: {{ \Illuminate\Support\Carbon::parse($rentUpdatedAt)->format('d.m.Y') }}</span>@endif<a href="{{ route('blogs.index', ['ara' => 'kira']) }}" class="mt-1 inline-flex items-center gap-1 font-bold text-orange-600">Detaylar <i class="fa-solid fa-arrow-right"></i></a></div>
                        </article>
                    @endif
                    @if(!empty($marketData))
                        <article @class(['rounded-2xl border border-blue-100 bg-white p-4 shadow-sm', 'col-span-2 lg:col-span-1' => $rentRateNumber === null])>
                            <div class="flex items-center justify-between"><h2 class="text-sm font-extrabold text-blue-950">Piyasalar</h2><span class="text-[10px] font-semibold text-slate-400">Güncel</span></div>
                            <div class="mt-2 divide-y divide-slate-100">@foreach($marketData as $item)<div class="flex items-center justify-between gap-2 py-1.5 text-xs"><span class="min-w-0 font-semibold text-blue-950"><i class="{{ $item['icon'] }} mr-1.5 text-orange-500"></i>{{ $item['label'] }}</span><strong class="whitespace-nowrap text-blue-950">{{ number_format($item['value'], 2, ',', '.') }} TL</strong></div>@endforeach</div>
                        </article>
                    @endif
                </div>
            </section>
        @endif

        <main id="ilanlar" class="mx-auto w-full max-w-7xl flex-grow px-4 py-7 sm:px-6 lg:px-8">
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <section class="min-w-0">
                    <div class="mb-5 flex items-end justify-between gap-3 border-b border-blue-100 pb-3">
                        <div><h1 class="text-xl font-extrabold text-blue-950 sm:text-2xl">Öne Çıkan İlanlar</h1><p class="mt-1 hidden text-xs text-slate-500 sm:block">En son eklenen güncel gayrimenkul fırsatları</p></div>
                        <details class="relative" data-display-settings><summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-blue-950 shadow-sm [&::-webkit-details-marker]:hidden"><i class="fa-solid fa-sliders text-orange-500"></i><span>Gösterim Ayarları</span></summary><div class="absolute right-0 top-full z-20 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-3 shadow-xl"><p class="mb-2 text-xs font-bold text-slate-600">Gösterilecek ilan</p><div class="grid grid-cols-2 gap-2"><button type="button" data-listing-count="3" class="rounded-lg border px-3 py-2 text-xs font-bold">3 ilan</button><button type="button" data-listing-count="6" class="rounded-lg border px-3 py-2 text-xs font-bold">6 ilan</button></div></div></details>
                    </div>

                    <div class="featured-listing-grid grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3" data-featured-listings data-default-count="{{ $preferredListingCount }}" data-authenticated="{{ auth()->check() ? '1' : '0' }}" data-save-url="{{ auth()->check() ? route('account.display-preference') : '' }}">
                        @forelse($recentListings as $index => $listing)
                            <div data-listing-index="{{ $index }}" @class(['hidden' => $index >= $preferredListingCount])><x-modern-listing-card :listing="$listing" /></div>
                        @empty
                            <div class="col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 lg:col-span-3">Henüz yayınlanmış bir ilan bulunmuyor.</div>
                        @endforelse
                    </div>
                </section>

                <aside class="home-editorial-grid grid grid-cols-2 gap-3 lg:grid-cols-1 lg:gap-5">
                    <section class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <h2 class="mb-3 text-sm font-extrabold text-blue-950"><i class="fa-solid fa-newspaper mr-1.5 text-orange-500"></i>Emlak Haberleri</h2>
                        @if($featuredPost)@php $postImage = $featuredPost->cover_image ? \Illuminate\Support\Facades\Storage::url($featuredPost->cover_image) : asset('fe-assets/assets/img/blog2.jpg'); @endphp<a href="{{ route('blogs.show', $featuredPost->slug) }}" class="group"><img src="{{ $postImage }}" alt="{{ $featuredPost->title }}" class="aspect-[4/3] w-full rounded-xl object-cover"><p class="mt-2 text-[10px] font-bold uppercase text-orange-600">{{ ($featuredPost->published_at ?? $featuredPost->created_at)?->locale('tr')->translatedFormat('d F Y') }}</p><h3 class="mt-1 line-clamp-2 text-sm font-extrabold text-blue-950 group-hover:text-orange-600">{{ $featuredPost->title }}</h3></a>@else<p class="py-5 text-xs text-slate-500">Henüz yayınlanmış haber yok.</p>@endif
                        <a href="{{ route('blogs.index') }}" class="mt-auto block rounded-lg bg-orange-50 px-2 py-2.5 text-center text-xs font-bold text-orange-600">Tüm Haberleri Oku <i class="fa-solid fa-arrow-right ml-1"></i></a>
                    </section>
                    <section class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <h2 class="mb-3 text-sm font-extrabold text-blue-950"><i class="fa-regular fa-building mr-1.5 text-orange-500"></i>Projeler</h2>
                        @if($featuredProject)<x-project-card :project="$featuredProject" compact class="mb-3 border-0 shadow-none" />@else<p class="py-5 text-xs text-slate-500">Henüz yayınlanmış proje yok.</p>@endif
                        <a href="{{ route('projects.index') }}" class="mt-auto block rounded-lg bg-orange-50 px-2 py-2.5 text-center text-xs font-bold text-orange-600">Tüm Projeleri Gör <i class="fa-solid fa-arrow-right ml-1"></i></a>
                    </section>
                </aside>
            </div>
        </main>
    </div>

    @if($activePoll)<x-active-poll :poll="$activePoll" />@endif
</x-layouts.modern>

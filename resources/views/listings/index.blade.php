@php
    $activeFilters = collect([
        'q' => request('q') ? 'Arama: '.request('q') : null,
        'city' => request('city') ? 'İl: '.request('city') : null,
        'district' => request('district') ? 'İlçe: '.request('district') : null,
        'neighborhood' => request('neighborhood') ? 'Mahalle: '.request('neighborhood') : null,
        'category' => request('category') ? 'Tür: '.request('category') : null,
        'type' => request('type') ? 'Durum: '.request('type') : null,
        'min_price' => request('min_price') ? 'Min. fiyat: '.number_format((float) request('min_price'), 0, ',', '.').' TL' : null,
        'max_price' => request('max_price') ? 'Maks. fiyat: '.number_format((float) request('max_price'), 0, ',', '.').' TL' : null,
        'min_area' => request('min_area') ? 'Min. alan: '.request('min_area').' m²' : null,
        'max_area' => request('max_area') ? 'Maks. alan: '.request('max_area').' m²' : null,
    ])->filter();

    $inputClass = 'mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500';
@endphp

<x-layouts.modern title="Tüm İlanlar | ZYDEMLAK" description="EİDS doğrulamalı satılık ve kiralık gayrimenkul ilanlarını konum, fiyat ve özelliklerine göre inceleyin.">
    <main class="flex-1">
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="hover:text-orange-600">Anasayfa</a>
                <i class="fa-solid fa-chevron-right mx-2 text-[9px]"></i>
                <span class="font-semibold text-slate-700">Tüm İlanlar</span>
            </div>
        </div>

        <section class="relative overflow-hidden bg-blue-950 py-10 text-white sm:py-12">
            <div class="absolute -right-20 -top-32 h-80 w-80 rounded-full bg-orange-500/10"></div>
            <div class="absolute -bottom-40 left-1/3 h-72 w-72 rounded-full bg-white/5"></div>
            <div class="relative mx-auto flex max-w-7xl flex-col justify-between gap-6 px-4 sm:px-6 md:flex-row md:items-end lg:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-orange-400">Güvenilir gayrimenkul ilanları</p>
                    <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">Tüm İlanlar</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">Satılık ve kiralık taşınmazları karşılaştırın; konum, fiyat ve özellik filtreleriyle aradığınız ilana daha hızlı ulaşın.</p>
                </div>
                <div class="flex shrink-0 items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/20 text-xl text-emerald-300"><i class="fa-solid fa-shield-check"></i></span>
                    <div><strong class="block text-lg">{{ number_format($listings->total(), 0, ',', '.') }}</strong><span class="text-xs text-slate-300">Yayındaki ilan</span></div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid items-start gap-8 lg:grid-cols-[300px_minmax(0,1fr)]">
                <aside class="space-y-6 lg:sticky lg:top-28">
                    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between bg-blue-950 px-5 py-4 text-sm font-extrabold text-white">
                            <span><i class="fa-solid fa-sliders mr-2 text-orange-400"></i>İlanları Filtrele</span>
                            <i class="fa-solid fa-chevron-down text-xs transition group-open:rotate-180"></i>
                        </summary>
                        <form action="{{ route('listings.index') }}" method="get" class="space-y-5 p-5" data-location-selects>
                            <label class="block">
                                <span class="text-xs font-bold text-slate-700">Kelime / İlan No</span>
                                <span class="relative block"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 mt-0.5 -translate-y-1/2 text-xs text-slate-400"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Örn: 3+1, Kadirli, 123..." class="{{ $inputClass }} pl-9"></span>
                            </label>

                            <label class="block"><span class="text-xs font-bold text-slate-700">İl</span><select name="city" class="{{ $inputClass }}" data-city-select data-districts-url="{{ route('locations.districts') }}" data-neighborhoods-url="{{ route('locations.neighborhoods') }}"><option value="">Tüm İller</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>@endforeach</select></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">İlçe</span><select name="district" class="{{ $inputClass }}" data-district-select data-selected="{{ request('district') }}" disabled><option value="">Önce il seçin</option></select></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">Mahalle / Köy</span><select name="neighborhood" class="{{ $inputClass }}" data-neighborhood-select data-selected="{{ request('neighborhood') }}" disabled><option value="">Önce ilçe seçin</option></select></label>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="block"><span class="text-xs font-bold text-slate-700">Gayrimenkul Tipi</span><select name="category" class="{{ $inputClass }}"><option value="">Tümü</option>@foreach($categoryOptions as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></label>
                                <label class="block"><span class="text-xs font-bold text-slate-700">İlan Durumu</span><select name="type" class="{{ $inputClass }}"><option value="">Tümü</option>@foreach($typeOptions as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach</select></label>
                            </div>

                            <fieldset>
                                <legend class="text-xs font-bold text-slate-700">Fiyat aralığı (TL)</legend>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="number" min="0" name="min_price" value="{{ request('min_price') }}" placeholder="En az" aria-label="En düşük fiyat" class="{{ $inputClass }}">
                                    <input type="number" min="0" name="max_price" value="{{ request('max_price') }}" placeholder="En çok" aria-label="En yüksek fiyat" class="{{ $inputClass }}">
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend class="text-xs font-bold text-slate-700">Alan aralığı (m²)</legend>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="number" min="0" name="min_area" value="{{ request('min_area') }}" placeholder="En az" aria-label="En düşük metrekare" class="{{ $inputClass }}">
                                    <input type="number" min="0" name="max_area" value="{{ request('max_area') }}" placeholder="En çok" aria-label="En yüksek metrekare" class="{{ $inputClass }}">
                                </div>
                            </fieldset>

                            @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow transition hover:bg-orange-600"><i class="fa-solid fa-magnifying-glass"></i>İlanları göster</button>
                            @if($activeFilters->isNotEmpty())<a href="{{ route('listings.index') }}" class="block text-center text-xs font-bold text-slate-500 hover:text-orange-600"><i class="fa-solid fa-rotate-left mr-1"></i>Filtreleri temizle</a>@endif
                        </form>
                    </details>

                    @if($recommendedListings->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Öne çıkanlar</p>
                            <h2 class="mt-1 text-lg font-extrabold text-blue-950">Önerilen İlanlar</h2>
                            <div class="mt-4 divide-y divide-slate-100">
                                @foreach($recommendedListings->take(3) as $recommended)
                                    @php
                                        $recommendedImage = $recommended->images->first();
                                        $recommendedImageUrl = $recommendedImage ? \App\Services\ListingPhoto::url($recommendedImage) : asset('fe-assets/assets/img/demo/property-1.jpg');
                                    @endphp
                                    <a href="{{ route('listings.show', $recommended->slug) }}" class="group flex gap-3 py-3 first:pt-0 last:pb-0">
                                        <img draggable="false" data-listing-photo src="{{ $recommendedImageUrl }}" alt="{{ $recommended->title }}" class="h-16 w-20 shrink-0 rounded-lg object-cover" loading="lazy">
                                        <span class="min-w-0"><strong class="line-clamp-2 text-xs leading-5 text-slate-800 transition group-hover:text-orange-600">{{ $recommended->title }}</strong><span class="mt-1 block text-xs font-extrabold text-orange-600">{{ \App\Support\ListingDecimal::format($recommended->price) }} {{ $recommended->currency === 'TRY' || ! $recommended->currency ? 'TL' : $recommended->currency }}</span></span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </aside>

                <section class="min-w-0">
                    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-extrabold text-blue-950">{{ $activeFilters->isEmpty() ? 'Güncel ilanlar' : 'Arama sonuçları' }}</h2>
                            <p class="mt-1 text-xs text-slate-500"><strong class="text-slate-700">{{ number_format($listings->total(), 0, ',', '.') }}</strong> ilan bulundu</p>
                        </div>
                        <form action="{{ route('listings.index') }}" method="get" class="flex items-center gap-2">
                            @foreach(request()->except(['sort', 'page']) as $key => $value)
                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                            @endforeach
                            <label for="listing-sort" class="whitespace-nowrap text-xs font-bold text-slate-500">Sırala:</label>
                            <select id="listing-sort" name="sort" onchange="this.form.submit()" class="rounded-xl border-slate-300 py-2 pl-3 pr-9 text-sm font-semibold text-slate-700 focus:border-orange-500 focus:ring-orange-500">
                                <option value="" @selected(! request('sort'))>En yeni</option>
                                <option value="price_desc" @selected(in_array(request('sort'), ['price', 'price_desc']))>Fiyat: yüksekten düşüğe</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Fiyat: düşükten yükseğe</option>
                                <option value="area_desc" @selected(request('sort') === 'area_desc')>Metrekare: büyükten küçüğe</option>
                            </select>
                        </form>
                    </div>

                    @if($activeFilters->isNotEmpty())
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            @foreach($activeFilters as $key => $label)
                                <a href="{{ route('listings.index', request()->except([$key, 'page'])) }}" class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">{{ $label }}<i class="fa-solid fa-xmark"></i></a>
                            @endforeach
                            <a href="{{ route('listings.index') }}" class="px-2 py-1.5 text-xs font-bold text-slate-500 hover:text-orange-600">Tümünü temizle</a>
                        </div>
                    @endif

                    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse($listings as $listing)
                            <x-modern-listing-card :listing="$listing" />
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center sm:col-span-2 xl:col-span-3">
                                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 text-2xl text-orange-500"><i class="fa-solid fa-house-circle-xmark"></i></span>
                                <h2 class="mt-5 text-xl font-extrabold text-blue-950">Aradığınız kriterlerde ilan bulunamadı</h2>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Filtrelerden birini kaldırarak veya fiyat ve alan aralığını genişleterek yeniden deneyebilirsiniz.</p>
                                <a href="{{ route('listings.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white hover:bg-orange-600"><i class="fa-solid fa-rotate-left"></i>Tüm ilanları göster</a>
                            </div>
                        @endforelse
                    </div>

                    @if($listings->hasPages())
                        <div class="mt-9 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">{{ $listings->onEachSide(1)->links() }}</div>
                    @endif
                </section>
            </div>
        </div>
    </main>
</x-layouts.modern>

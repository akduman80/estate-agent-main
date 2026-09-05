<x-layouts.modern title="ZYDEMLAK - Gayrimenkul ve İlan Portalı" description="Osmaniye satılık ve kiralık emlak ilanları, EİDS doğrulamalı gayrimenkuller ve güncel emlak haberleri.">
    @php
        $typeOptions = \App\Models\Setting::get('options_type', ['Satılık', 'Kiralık']);
        $cities = \App\Services\TurkeyAddressService::getCities();
    @endphp

    <section class="border-b border-slate-800 bg-blue-950 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <form method="GET" action="{{ route('listings.index') }}" class="grid grid-cols-1 items-end gap-4 rounded-2xl bg-white p-4 shadow-2xl sm:grid-cols-2 sm:p-6 lg:grid-cols-4 xl:grid-cols-8" data-location-selects>
                <div class="xl:col-span-2">
                    <label for="home-q" class="mb-1 block text-xs font-semibold text-slate-500">Kelime / İlan No</label>
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="home-q" name="q" type="search" placeholder="Örn: 3+1 Daire, Arsa..." class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-orange-500 focus:bg-white focus:ring-2 focus:ring-orange-200">
                    </div>
                </div>
                <div>
                    <label for="home-category" class="mb-1 block text-xs font-semibold text-slate-500">Gayrimenkul Tipi</label>
                    <select id="home-category" name="category" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200">
                        <option value="">Tümü</option>
                        @foreach(\App\Models\Setting::get('options_category', ['Konut', 'Arsa', 'İşyeri']) as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="home-type" class="mb-1 block text-xs font-semibold text-slate-500">İlan Durumu</label>
                    <select id="home-type" name="type" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200">
                        <option value="">Hepsi</option>
                        @foreach($typeOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="home-city" class="mb-1 block text-xs font-semibold text-slate-500">Konum</label>
                    <select id="home-city" name="city" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-sm text-slate-700 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200" data-city-select data-districts-url="{{ route('locations.districts') }}" data-neighborhoods-url="{{ route('locations.neighborhoods') }}">
                        <option value="">Tüm Konumlar</option>
                        @foreach($cities as $key => $city)
                            <option value="{{ $key }}">{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="home-district" class="mb-1 block text-xs font-semibold text-slate-500">İlçe</label>
                    <select id="home-district" name="district" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-sm text-slate-700 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200" data-district-select disabled>
                        <option value="">Önce il seçin</option>
                    </select>
                </div>
                <div>
                    <label for="home-neighborhood" class="mb-1 block text-xs font-semibold text-slate-500">Mahalle / Köy</label>
                    <select id="home-neighborhood" name="neighborhood" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-left text-sm text-slate-700 outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200" data-neighborhood-select disabled>
                        <option value="">Önce ilçe seçin</option>
                    </select>
                </div>
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-2.5 font-bold text-white shadow-md transition hover:bg-orange-600">
                    <i class="fa-solid fa-search"></i><span>İlan Ara</span>
                </button>
            </form>
        </div>
    </section>

    <main id="ilanlar" class="mx-auto w-full max-w-7xl flex-grow px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
            <section class="space-y-6 lg:col-span-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-950">Öne Çıkan İlanlar</h1>
                        <p class="mt-1 text-xs text-slate-500">En son eklenen güncel gayrimenkul fırsatları</p>
                    </div>
                    <a href="{{ route('listings.index') }}" class="flex items-center gap-1 text-sm font-semibold text-orange-600 hover:text-orange-700">Tümünü Gör <i class="fa-solid fa-arrow-right text-xs"></i></a>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($recentListings as $listing)
                        <x-modern-listing-card :listing="$listing" />
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                            <i class="fa-regular fa-building mb-3 block text-3xl text-orange-400"></i>
                            Henüz yayınlanmış bir ilan bulunmuyor.
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="space-y-6 lg:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                        <a href="{{ route('blogs.index') }}" class="text-lg font-bold text-blue-950 transition hover:text-orange-600"><i class="fa-solid fa-newspaper mr-2 text-orange-500"></i>Emlak Haberleri</a>
                    </div>
                    <div class="space-y-4 divide-y divide-slate-100">
                        @forelse($latestPosts as $post)
                            <div class="pt-4 first:pt-0"><x-news-mini-card :post="$post" large /></div>
                        @empty
                            <p class="py-4 text-center text-xs text-slate-500">Henüz yayınlanmış haber bulunmuyor.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('blogs.index') }}" class="mt-5 block rounded-lg bg-orange-50 py-2 text-center text-xs font-bold text-orange-600 transition hover:bg-orange-100">Tüm Haberleri Oku</a>
                </div>
            </aside>
        </div>
    </main>

    @if($activePoll)
        <x-active-poll :poll="$activePoll" />
    @endif
</x-layouts.modern>

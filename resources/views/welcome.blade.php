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

        <section id="piyasalar" class="home-market-section px-4 py-4 sm:px-6 lg:px-8">
            <div class="home-overview home-tools-overview mx-auto max-w-7xl">
                @if($rentRateNumber !== null)
                    <article class="home-overview-card home-rent">
                        <div class="min-w-0 flex-1"><h2 class="text-sm font-extrabold text-blue-950"><span class="phone-rent-title">Kira Artış Oranı</span><span class="desktop-rent-title">{{ $rentPeriod }} Kira Artış Oranı</span></h2><p class="mt-2 text-3xl font-black text-orange-600">%{{ number_format($rentRateNumber, 2, ',', '.') }}</p><p class="phone-rent-title mt-1 text-xs font-semibold text-blue-950">{{ $rentPeriod }}</p></div>
                        <div class="mt-auto pt-3 text-[11px] text-slate-500">@if($rentUpdatedAt)<span class="block">Güncelleme: {{ \Illuminate\Support\Carbon::parse($rentUpdatedAt)->format('d.m.Y') }}</span>@endif<a href="{{ route('blogs.index', ['ara' => 'kira']) }}" class="mt-1 inline-flex items-center gap-1 font-bold text-orange-600">Detaylar <i class="fa-solid fa-arrow-right"></i></a></div>
                    </article>
                @endif
                <article class="home-overview-card home-rayic">
    <h2 class="home-tool-title">Arsa Rayiç Değeri</h2>
    <div class="home-rayic-body">
        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
        <p class="home-rayic-location">Osmaniye / Merkez</p>
        <p>Osmaniye / Merkez arsa rayiç sorgulama</p>
    </div>
    <a class="home-tool-button" href="https://www.turkiye.gov.tr/osmaniye-belediyesi-arsa-rayic-degeri-sorgulama" target="_blank" rel="noopener noreferrer">
        Rayiç Sorgula <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
        <span class="sr-only">(Yeni sekmede açılır)</span>
    </a>

    <details class="home-notice-toggle"><summary>Belediye teyidi gerekir.</summary><p class="home-tool-notice">Bu sorgulama Osmaniye ili, Merkez ilçesi kapsamındadır. Gösterilen bilgiler bilgilendirme amaçlıdır ve resmî belge yerine geçmez. Güncel ve taşınmazınıza esas alınacak rayiç değerini Osmaniye Belediyesinden teyit ediniz.</p></details>
</article>

<article class="home-overview-card home-capital-gain">
    <h2 class="home-tool-title">Değer Artışı Kazancı</h2>
    <p class="home-tool-link-note">Gayrimenkul satışınızın tahmini vergisi</p>
    <button class="home-tool-button" type="button" data-capital-gain-open aria-haspopup="dialog" aria-controls="capital-gain-dialog" disabled>Değer Artışı Hesapla</button>
    <dialog id="capital-gain-dialog" class="home-gain-dialog" aria-labelledby="capital-gain-dialog-title">
        <div class="home-gain-dialog-header"><h2 id="capital-gain-dialog-title">Değer Artışı Kazancı Hesaplama</h2><button type="button" data-capital-gain-close aria-label="Hesaplama penceresini kapat">✕</button></div>
        <p class="home-tool-link-note">2025–2026 satışları için. Yİ-ÜFE verisi: Eylül 2026.</p>
    <form data-capital-gain-form>
        <div class="home-tool-fields">
            <label>Alış Tarihi<input name="purchase_date" type="date" required></label>
            <label>Satış Tarihi<input name="sale_date" type="date" required></label>
            <label>Alış Bedeli (TL)<input name="purchase_price" type="text" inputmode="decimal" placeholder="Örn: 1.000.000,00" required></label>
            <label>Satış Bedeli (TL)<input name="sale_price" type="text" inputmode="decimal" placeholder="Örn: 2.000.000,00" required></label>
            <label class="home-tool-full">Satışa İlişkin Belgeli Giderler (TL)<input name="expenses" type="text" inputmode="decimal" value="0"></label>
            <label class="home-tool-full">Edinme Şekli<select name="acquisition"><option value="purchase">Satın alma</option><option value="gratuitous">Miras / bağış</option><option value="special">Ticari faaliyet / diğer özel durum</option></select></label>
        </div>
        <button class="home-tool-button" type="submit" disabled>Hesaplama yükleniyor…</button>
        <noscript>Hesaplama için tarayıcınızda JavaScript etkin olmalıdır.</noscript>
        <div data-capital-gain-result role="status" aria-live="polite" hidden></div>
    </form>
    <p class="home-tool-notice">Sonuç bilgilendirme amaçlıdır; kesin vergi tutarı değildir. Bağlı bulunduğunuz vergi dairesinden teyit alınız.</p>
    </dialog>
    <details class="home-notice-toggle"><summary>Sonuç kesin değildir; vergi dairenize danışın.</summary><p class="home-tool-notice">Hesaplama sonucu bilgilendirme amaçlıdır; kesin vergi tutarı veya resmî değerlendirme niteliği taşımaz. Durumunuza ilişkin doğru bilgi için bağlı bulunduğunuz vergi dairesiyle iletişime geçiniz.</p></details>
</article>
                @if(!empty($marketData))
                    <article class="home-overview-card home-rates">
                        <div class="flex items-center justify-between"><h2 class="text-sm font-extrabold text-blue-950">Piyasalar</h2><span class="text-[10px] font-semibold text-slate-400">TL</span></div>
                        <div class="mt-2 divide-y divide-slate-100">@foreach($marketData as $item)<div data-market-item class="flex items-center justify-between gap-2 py-1.5 text-xs"><span class="min-w-0 font-semibold text-blue-950"><i class="{{ $item['icon'] }} mr-1.5 text-orange-500"></i>{{ $item['label'] }}</span><strong class="whitespace-nowrap text-blue-950">{{ number_format($item['value'], 2, ',', '.') }} TL</strong></div>@endforeach</div>
                    </article>
                @endif
            </div>
        </section>

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
    @push('scripts')
    <style>

    .home-overview.home-tools-overview { align-items: stretch; }
    .home-tools-overview .home-overview-card { min-height: 215px; padding: 1rem; }
    .home-tools-overview .home-tool-title { font-size: .95rem; line-height: 1.4; margin-bottom: .6rem; }
    .home-tools-overview .home-rayic-body { gap: .3rem; margin-bottom: .6rem; flex: 1; justify-content: center; }
    .home-tools-overview .home-rayic-body > i { display: none; }
    .home-tools-overview .home-rayic-body p { font-size: .78rem; line-height: 1.4; }
    .home-tools-overview .home-capital-gain > .home-tool-link-note { flex: 1; display: flex; align-items: center; justify-content: center; font-size: .8rem; margin: .2rem 0 .8rem; }
    .home-tools-overview .home-tool-button { min-height: 44px; font-size: .85rem; }
    .home-tools-overview .home-tool-button:disabled { cursor: wait; opacity: .65; }
    .home-tools-overview .home-notice-toggle { margin-top: .6rem; border-top: 1px solid #e2e8f0; padding-top: .5rem; color: #475569; }
    .home-tools-overview .home-notice-toggle > summary { cursor: pointer; font-size: .7rem; line-height: 1.4; }
    .home-tools-overview .home-notice-toggle .home-tool-notice { border: 0; padding-top: 0; }
    .home-tools-overview .home-rates [data-market-item] { flex-direction: row; align-items: center; justify-content: space-between; padding: .6rem 0; gap: .35rem; }
    .home-tools-overview .home-rates [data-market-item] strong { font-size: .85rem; }
    .home-tools-overview .home-rates [data-market-item] span { font-size: .72rem; }
    .home-tools-overview .home-rent > div:last-child { padding-top: .6rem; }
    .home-tools-overview .home-gain-dialog { position: fixed; inset: 0; margin: auto; width: min(560px, calc(100vw - 24px)); max-height: calc(100dvh - 32px); overflow-y: auto; border: 0; border-radius: 1rem; padding: 1.25rem; background: white; color: #172554; box-shadow: 0 24px 80px rgb(15 23 42 / .3); }
    .home-tools-overview .home-gain-dialog::backdrop { background: rgb(15 23 42 / .55); }
    .home-tools-overview .home-gain-dialog-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
    .home-tools-overview .home-gain-dialog-header h2 { font-size: 1rem; font-weight: 800; }
    .home-tools-overview .home-gain-dialog-header button { flex-shrink: 0; width: 44px; height: 44px; border-radius: .6rem; background: #f1f5f9; cursor: pointer; }
    .home-tools-overview .home-gain-dialog form { margin-top: .8rem; }
    @media (min-width: 1024px) {
        .home-overview.home-tools-overview { grid-template-columns: minmax(150px, .8fr) minmax(0, 1.25fr) minmax(0, 1.25fr) minmax(220px, 1fr); gap: .85rem; }
    }
    @media (max-width: 1023px) {
        .home-tools-overview .home-overview-card { min-height: 160px; }
        .home-tools-overview .home-rates [data-market-item] { flex-wrap: wrap; }
    }
    @media (max-width: 480px) {
        .home-tools-overview .home-gain-dialog .home-tool-notice { font-size: .75rem; line-height: 1.5; }
        .home-tools-overview .home-gain-dialog .home-tool-link-note { font-size: .75rem; }
    }
    </style>
    <script>
(() => {
    const indices = {"2005-01":114.83092745636917,"2005-02":114.80514419443672,"2005-03":117.24628137365562,"2005-04":119.61544602491335,"2005-05":119.23228193448413,"2005-06":119.63916111075497,"2005-07":119.33458771177759,"2005-08":121.40069562652457,"2005-09":123.4044825017343,"2005-10":124.21617267818512,"2005-11":121.40469410030022,"2005-12":121.13638272211551,"2006-01":123.51354804127395,"2006-02":123.83050341716174,"2006-03":124.13755393755304,"2006-04":126.54443382320098,"2006-05":130.0507526689597,"2006-06":135.28051637110826,"2006-07":136.44928931969446,"2006-08":135.42908920355563,"2006-09":135.11213382766786,"2006-10":135.72623486845046,"2006-11":135.3300406485907,"2006-12":135.16165810515034,"2007-01":135.09232411667486,"2007-02":136.37005047572254,"2007-03":137.69730111225266,"2007-04":138.79674007236343,"2007-05":139.34150712467058,"2007-06":139.19293429222319,"2007-07":139.28207799169164,"2007-08":140.47066065127083,"2007-09":141.8969598427659,"2007-10":141.70876758833253,"2007-11":142.9765890918837,"2007-12":143.19449591280656,"2008-01":143.7986920980927,"2008-02":147.48329834278826,"2008-03":152.15839013713318,"2008-04":159.00264528521018,"2008-05":162.37029615401798,"2008-06":162.89525349533213,"2008-07":164.92574887211327,"2008-08":161.0727600839773,"2008-09":159.62665118148925,"2008-10":160.53789788716668,"2008-11":160.4883736096842,"2008-12":154.80298655469693,"2009-01":155.1595613525707,"2009-02":156.972149908429,"2009-03":157.4277732612677,"2009-04":158.44797337740653,"2009-05":158.36873453343458,"2009-06":159.86436771340513,"2009-07":158.73521418680485,"2009-08":159.39883950506993,"2009-09":160.37942019922278,"2009-10":160.83504355206148,"2009-11":162.9150632063251,"2009-12":163.9847875999464,"2010-01":164.93565372760978,"2010-02":167.67929870013845,"2010-03":170.93799615848482,"2010-04":174.95936749006114,"2010-05":172.948681824273,"2010-06":172.0770545405815,"2010-07":171.80962344217622,"2010-08":173.79059454147492,"2010-09":174.6721266806628,"2010-10":176.7818609014159,"2010-11":176.2271889936123,"2010-12":178.5350203242953,"2011-01":182.7544887658015,"2011-02":185.90423281368643,"2011-03":188.17244472238343,"2011-04":189.32140795997668,"2011-05":189.608648769375,"2011-06":189.6185536248715,"2011-07":189.56902934738903,"2011-08":192.90696564970736,"2011-09":195.8883271541519,"2011-10":199.02816634654036,"2011-11":200.31579756108454,"2011-12":202.3264832268727,"2012-01":203.0990619555992,"2012-02":202.91086970116586,"2012-03":203.64382900790636,"2012-04":203.81221155134676,"2012-05":204.89184080046456,"2012-06":201.83124045204806,"2012-07":201.19732970027246,"2012-08":201.71238218609014,"2012-09":203.79240184035376,"2012-10":204.14897663822754,"2012-11":207.5364372180283,"2012-12":207.288815830616,"2013-01":206.91243132174924,"2013-02":206.6450002233439,"2013-03":208.3288256577478,"2013-04":207.269006119623,"2013-05":209.33912091839014,"2013-06":212.38981641131014,"2013-07":214.49955063206326,"2013-08":214.5886943315317,"2013-09":216.48052173136196,"2013-10":217.96625005583599,"2013-11":219.31331040335908,"2013-12":221.74,"2014-01":229.1,"2014-02":232.27,"2014-03":233.98,"2014-04":234.18,"2014-05":232.96,"2014-06":233.09,"2014-07":234.79,"2014-08":235.78,"2014-09":237.79,"2014-10":239.97,"2014-11":237.65,"2014-12":235.84,"2015-01":236.61,"2015-02":239.46,"2015-03":241.97,"2015-04":245.42,"2015-05":248.15,"2015-06":248.78,"2015-07":247.99,"2015-08":250.43,"2015-09":254.25,"2015-10":253.74,"2015-11":250.13,"2015-12":249.31,"2016-01":250.67,"2016-02":250.16,"2016-03":251.17,"2016-04":252.47,"2016-05":256.21,"2016-06":257.27,"2016-07":257.81,"2016-08":258.01,"2016-09":258.77,"2016-10":260.94,"2016-11":266.16,"2016-12":274.09,"2017-01":284.99,"2017-02":288.59,"2017-03":291.58,"2017-04":293.79,"2017-05":295.31,"2017-06":295.52,"2017-07":297.65,"2017-08":300.18,"2017-09":300.9,"2017-10":306.04,"2017-11":312.21,"2017-12":316.48,"2018-01":319.6,"2018-02":328.17,"2018-03":333.21,"2018-04":341.88,"2018-05":354.85,"2018-06":365.6,"2018-07":372.06,"2018-08":396.62,"2018-09":439.78,"2018-10":443.78,"2018-11":432.55,"2018-12":422.94,"2019-01":424.86,"2019-02":425.26,"2019-03":431.98,"2019-04":444.85,"2019-05":456.74,"2019-06":457.16,"2019-07":452.63,"2019-08":449.96,"2019-09":450.55,"2019-10":451.31,"2019-11":450.97,"2019-12":454.08,"2020-01":462.42,"2020-02":464.64,"2020-03":468.69,"2020-04":474.69,"2020-05":482.02,"2020-06":485.37,"2020-07":490.33,"2020-08":501.85,"2020-09":515.13,"2020-10":533.44,"2020-11":555.18,"2020-12":568.27,"2021-01":583.38,"2021-02":590.52,"2021-03":614.93,"2021-04":641.63,"2021-05":666.79,"2021-06":693.54,"2021-07":710.61,"2021-08":730.28,"2021-09":741.58,"2021-10":780.45,"2021-11":858.43,"2021-12":1022.25,"2022-01":1129.03,"2022-02":1210.6,"2022-03":1321.9,"2022-04":1423.27,"2022-05":1548.01,"2022-06":1652.75,"2022-07":1738.21,"2022-08":1780.05,"2022-09":1865.09,"2022-10":2011.13,"2022-11":2026.08,"2022-12":2021.19,"2023-01":2105.17,"2023-02":2138.04,"2023-03":2147.44,"2023-04":2164.94,"2023-05":2179.02,"2023-06":2320.72,"2023-07":2511.75,"2023-08":2659.6,"2023-09":2749.98,"2023-10":2803.29,"2023-11":2882.04,"2023-12":2915.02,"2024-01":3035.59,"2024-02":3149.03,"2024-03":3252.79,"2024-04":3369.98,"2024-05":3435.96,"2024-06":3483.25,"2024-07":3550.88,"2024-08":3610.51,"2024-09":3659.84,"2024-10":3707.1,"2024-11":3731.43,"2024-12":3746.52,"2025-01":3861.33,"2025-02":3943.01,"2025-03":4017.3,"2025-04":4128.19,"2025-05":4230.69,"2025-06":4334.94,"2025-07":4409.73,"2025-08":4518.89,"2025-09":4632.89,"2025-10":4708.2,"2025-11":4747.63,"2025-12":4783.04,"2026-01":4910.53,"2026-02":5029.76,"2026-03":5145.36,"2026-04":5308.46,"2026-05":5454.58,"2026-06":5552.52,"2026-07":5637.14,"2026-08":5781.74,"2026-09":5901.26};
    const tariffs = {
        2025: { exemption: 120000, limits: [158000, 330000, 800000, 4300000] },
        2026: { exemption: 150000, limits: [190000, 400000, 1000000, 5300000] }
    };
    const money = value => new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(value);
    function amount(value) {
        const s = String(value).trim();
        if (!/^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/.test(s)) throw new Error('Tutarları 1000000 veya 1.000.000,00 biçiminde yazın.');
        const n = Number(s.replaceAll('.', '').replace(',', '.'));
        if (!Number.isFinite(n) || n > 1000000000000) throw new Error('Geçerli bir tutar yazın.');
        return n;
    }
    function date(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) throw new Error('Alış ve satış tarihlerini girin.');
        const d = new Date(value + 'T00:00:00Z');
        if (Number.isNaN(d.getTime()) || d.toISOString().slice(0, 10) !== value) throw new Error('Geçerli bir tarih girin.');
        return d;
    }
    function previousMonth(d) {
        return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() - 1, 1)).toISOString().slice(0, 7);
    }
    function tax(base, tariff) {
        const limits = [...tariff.limits, Infinity], rates = [.15, .20, .27, .35, .40];
        let total = 0, lower = 0;
        for (let i = 0; i < limits.length; i++) {
            total += Math.max(0, Math.min(base, limits[i]) - lower) * rates[i];
            lower = limits[i];
        }
        return total;
    }
    function calculate(v, today = new Date().toLocaleDateString('en-CA', { timeZone: 'Europe/Istanbul' })) {
        const purchase = date(v.purchase_date), sale = date(v.sale_date);
        if (sale < purchase) throw new Error('Satış tarihi alış tarihinden önce olamaz.');
        if (v.sale_date > today) throw new Error('Gelecekteki satışlar için hesaplama yapılmaz.');
        if (v.acquisition === 'special') throw new Error('Ticari faaliyet, kat karşılığı, kooperatif veya diğer özel edinimlerde vergi dairenizden bilgi alın.');
        if (v.acquisition === 'gratuitous') return { message: 'Miras veya bağışla bedelsiz edinilen taşınmazların satışı genel olarak değer artışı kazancı kapsamında değildir. Özel durumları vergi dairenizle teyit edin.' };
        if (v.acquisition !== 'purchase') throw new Error('Edinme şeklini seçin.');
        const anniversaryYear = purchase.getUTCFullYear() + 5;
        const anniversaryDay = Math.min(purchase.getUTCDate(), new Date(Date.UTC(anniversaryYear, purchase.getUTCMonth() + 1, 0)).getUTCDate());
        const anniversary = new Date(Date.UTC(anniversaryYear, purchase.getUTCMonth(), anniversaryDay));
        if (+sale === +anniversary) return { message: 'Satış tarihi beş yıllık sürenin sınırına denk geliyor. Süre ve edinim tarihini vergi dairenizle teyit edin.' };
        if (sale > anniversary) return { message: 'Girilen tarihlere göre beş yıllık süre aşılmıştır. Ticari faaliyet ve özel edinimler hariç, genel olarak değer artışı kazancı vergisi doğmaz.' };
        const tariff = tariffs[sale.getUTCFullYear()];
        if (!tariff) throw new Error('Bu araç yalnızca 2025 ve 2026 satışları için güncel vergi tarifesi içerir.');
        const purchasePrice = amount(v.purchase_price), salePrice = amount(v.sale_price), expenses = amount(v.expenses || '0');
        if (purchasePrice <= 0 || salePrice <= 0) throw new Error('Alış ve satış bedelleri sıfırdan büyük olmalıdır.');
        const a = previousMonth(purchase), b = previousMonth(sale);
        if (!indices[a] || !indices[b]) throw new Error('Gerekli aylık Yİ-ÜFE verisi henüz mevcut değil. Veri güncellendiğinde tekrar deneyin.');
        const ratio = indices[b] / indices[a];
        const indexed = ratio >= 1.1 - 1e-12;
        const cost = purchasePrice * (indexed ? ratio : 1);
        const gain = salePrice - cost - expenses;
        const base = Math.max(0, gain - tariff.exemption);
        return { cost, gain, base, tax: tax(base, tariff), exemption: tariff.exemption, indexed, a, b, year: sale.getUTCFullYear() };
    }
    function init() {
        const dialog = document.getElementById('capital-gain-dialog');
        const opener = document.querySelector('[data-capital-gain-open]');
        if (dialog && opener && typeof dialog.showModal === 'function' && !dialog.dataset.initialized) {
            dialog.dataset.initialized = '1';
            opener.disabled = false;
            let priorOverflow = '';
            opener.addEventListener('click', () => {
                priorOverflow = document.body.style.overflow;
                dialog.showModal();
                document.body.style.overflow = 'hidden';
            });
            dialog.querySelector('[data-capital-gain-close]')?.addEventListener('click', () => dialog.close());
            dialog.addEventListener('close', () => {
                document.body.style.overflow = priorOverflow;
                opener.focus();
            });
            dialog.addEventListener('click', event => {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
            });
        }
        document.querySelectorAll('[data-capital-gain-form]').forEach(form => {
            const result = form.querySelector('[data-capital-gain-result]');
            const button = form.querySelector('button[type="submit"]');
            if (!result || !button || form.dataset.initialized) return;
            form.dataset.initialized = '1';
            button.disabled = false;
            button.textContent = 'Hesapla';
            const show = lines => {
                result.replaceChildren(...lines.map(line => {
                    const p = document.createElement('p'); p.textContent = line; return p;
                }));
                result.hidden = false;
            };
            form.addEventListener('input', () => { result.hidden = true; });
            form.addEventListener('change', () => { result.hidden = true; });
            form.addEventListener('submit', event => {
                event.preventDefault();
                try {
                    const r = calculate(Object.fromEntries(new FormData(form)));
                    if (r.message) return show([r.message]);
                    show([
                        'Tahmini gelir vergisi: ' + money(r.tax),
                        'Safi değer artışı kazancı: ' + money(r.gain),
                        'Vergiye tabi tutar: ' + money(r.base),
                        r.year + ' yılı istisnası: ' + money(r.exemption),
                        (r.indexed ? 'Endekslenmiş alış bedeli: ' : '%10 artış şartı sağlanmadığı için alış bedeli endekslenmedi: ') + money(r.cost),
                        'Kullanılan Yİ-ÜFE ayları: ' + r.a + ' / ' + r.b + '. Veri: Eylül 2026.',
                        'Tek satış ve başka beyana tabi gelir bulunmadığı varsayılmıştır. Yıllık istisna yalnızca bir kez uygulanır. Diğer gelirler, satışlar ve indirimler sonucu değiştirebilir. Damga vergisi dahil değildir.'
                    ]);
                } catch (error) { show([error.message || 'Bilgileri kontrol edip tekrar deneyin.']); }
            });
        });
    }
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
        else init();
    }
    if (typeof module !== 'undefined') module.exports = { calculate, amount, tax, tariffs };
})();

    </script>
    @endpush
</x-layouts.modern>

@php
    $eidsCard = \App\Support\ListingEidsCard::forListing($listing);
    $socialPreview = app(\App\Services\ListingSocialPreview::class);
    $socialImage = $socialPreview->image($listing);
    $socialDescription = $socialPreview->description($listing);
    $canonicalUrl = $socialPreview->url('listings.show', ['listing' => $listing->slug]);
    $images = $listing->images->map(fn ($image) => [
        'url' => \App\Services\ListingPhoto::url($image),
        'alt' => $image->alt_text ?: $listing->title,
    ])->values();

    if ($images->isEmpty()) {
        $images = collect([[
            'url' => asset('fe-assets/assets/img/demo/property-1.jpg'),
            'alt' => $listing->title,
        ]]);
    }

    $profile = $listing->businessProfile ?: $listing->user?->businessProfile;
    $contactName = $profile?->office_name ?: $listing->user?->name ?: \App\Models\Setting::get('contact_company_name', 'ZYDEMLAK');
    $details = $listing->form_details ?? [];
    $details['latitude'] ??= $listing->latitude;
    $details['longitude'] ??= $listing->longitude;
    $contactPhone = !empty($details['hide_phone']) ? null : ($profile?->business_phone ?: $listing->user?->phone ?: (\App\Models\Setting::get('site_phone', '0530 360 77 69') ?: '0530 360 77 69'));
    $phoneHref = preg_replace('/[^0-9+]/', '', (string) $contactPhone);
    $whatsappNumber = preg_replace('/\D/', '', (string) $contactPhone);
    $whatsappNumber = str_starts_with($whatsappNumber, '0') ? '90'.substr($whatsappNumber, 1) : $whatsappNumber;

    $location = collect([
        $listing->eidsVerification?->neighborhood ?: $listing->neighborhood_id,
        $listing->district_name,
        $listing->city_name,
    ])->filter()->join(', ');

    $currency = $listing->currency === 'TRY' || ! $listing->currency ? 'TL' : $listing->currency;
    $features = collect([
        ['Brüt Alan', $listing->square_meters ? \App\Support\ListingDecimal::format($listing->square_meters).' m²' : null, 'fa-ruler-combined'],
        ['Net Alan', $listing->net_square_meters ? \App\Support\ListingDecimal::format($listing->net_square_meters).' m²' : null, 'fa-vector-square'],
        ['Oda Sayısı', $listing->room_count, 'fa-door-open'],
        ['Bulunduğu Kat', $listing->floor_number, 'fa-building'],
        ['Kat Sayısı', $listing->total_floors, 'fa-layer-group'],
        ['Bina Yaşı', $listing->building_age, 'fa-calendar'],
        ['Isıtma', $listing->heating_type, 'fa-temperature-half'],
        ['Eşya Durumu', $listing->furniture_status, 'fa-couch'],
        ['Kullanım Durumu', $listing->usage_status, 'fa-key'],
        ['Cephe', implode(' / ', $listing->selected_facades), 'fa-compass'],
        ['Site Adı', $listing->site_name, 'fa-city'],
        ['İmar Durumu', $listing->zoning_status, 'fa-map'],
        ['Tapu Durumu', $listing->deed_status, 'fa-file-signature'],
        ['Krediye Uygunluk', $listing->credit_eligibility, 'fa-landmark'],
        ['Kimden', $listing->from_who, 'fa-user-tag'],
        ['Takas', $listing->swap_option, 'fa-right-left'],
        ['Ada', $listing->block_number, 'fa-border-all'],
        ['Parsel', $listing->parcel_number, 'fa-border-all'],
        ['Aidat', $listing->dues_price ? \App\Support\ListingDecimal::format($listing->dues_price).' '.$currency : null, 'fa-receipt'],
        ['Depozito', $listing->deposit_price ? \App\Support\ListingDecimal::format($listing->deposit_price).' '.$currency : null, 'fa-wallet'],
    ])->filter(fn ($feature) => filled($feature[1]));

    $youtubeId = null;
    if ($listing->video_url && preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $listing->video_url, $matches)) {
        $youtubeId = $matches[1];
    }
@endphp

<x-layouts.modern :hide-contact="!empty($details['hide_phone'])" :title="$listing->title.' | ZİYADE EMLAK'" :description="$socialDescription">
    @push('head')
        <meta property="og:type" content="product">
        <meta property="og:title" content="{{ $listing->title }}">
        <meta property="og:description" content="{{ $socialDescription }}">
        <meta property="og:image" content="{{ $socialImage['url'] }}">
        <meta property="og:image:secure_url" content="{{ $socialImage['url'] }}">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $listing->title }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="ZİYADE EMLAK">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $listing->title }}">
        <meta name="twitter:description" content="{{ $socialDescription }}">
        <meta name="twitter:image" content="{{ $socialImage['url'] }}">
        <meta name="twitter:image:alt" content="{{ $listing->title }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endpush

    <main class="flex-1">
        <nav class="mx-auto flex max-w-7xl flex-wrap gap-4 px-4 py-3 text-sm" aria-label="İlanı paylaş">
            <a href="https://wa.me/?text={{ rawurlencode($listing->title.' '.$canonicalUrl) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($canonicalUrl) }}" target="_blank" rel="noopener noreferrer">Facebook</a>
            <a href="https://t.me/share/url?url={{ rawurlencode($canonicalUrl) }}&amp;text={{ rawurlencode($listing->title) }}" target="_blank" rel="noopener noreferrer">Telegram</a>
            <a href="https://twitter.com/intent/tweet?url={{ rawurlencode($canonicalUrl) }}&amp;text={{ rawurlencode($listing->title) }}" target="_blank" rel="noopener noreferrer">X</a>
            <button type="button" id="listing-native-share" hidden>Paylaş / BİP</button>
            <button type="button" id="listing-copy-link">Bağlantıyı kopyala</button>
            <span id="listing-share-status" role="status"></span>
        </nav>
        @push('scripts')
            <script>
                (() => {
                    const url = @json($canonicalUrl);
                    const title = @json($listing->title);
                    const status = document.getElementById('listing-share-status');
                    const native = document.getElementById('listing-native-share');
                    native.hidden = !navigator.share;
                    native.addEventListener('click', async () => {
                        try { await navigator.share({ title, url }); }
                        catch (error) { if (error.name !== 'AbortError') status.textContent = 'Paylaşım açılamadı. Bağlantıyı kopyalayabilirsiniz.'; }
                    });
                    document.getElementById('listing-copy-link').addEventListener('click', async () => {
                        try { await navigator.clipboard.writeText(url); status.textContent = 'Bağlantı kopyalandı.'; }
                        catch { status.textContent = url; }
                    });
                })();
            </script>
        @endpush
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-3 text-xs text-slate-500 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="hover:text-orange-600">Anasayfa</a>
                <i class="fa-solid fa-chevron-right mx-2 text-[9px]"></i>
                <a href="{{ route('listings.index') }}" class="hover:text-orange-600">Tüm İlanlar</a>
                <i class="fa-solid fa-chevron-right mx-2 text-[9px]"></i>
                <span class="text-slate-700">{{ \Illuminate\Support\Str::limit($listing->title, 55) }}</span>
            </div>
        </div>

        <section class="bg-blue-950 py-7 text-white">
            <div class="mx-auto flex max-w-7xl flex-col justify-between gap-5 px-4 sm:px-6 lg:flex-row lg:items-end lg:px-8">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2 text-xs font-bold">
                        <span class="rounded bg-orange-500 px-2.5 py-1">{{ $listing->type }}</span>
                        <span class="rounded border border-white/25 bg-white/10 px-2.5 py-1">{{ $listing->source_label }}</span>
                        <span class="rounded bg-emerald-500/20 px-2.5 py-1 text-emerald-200"><i class="fa-solid fa-shield-check mr-1"></i>{{ !empty($isPreview) ? 'Önizleme — yayınlanmadı' : ($listing->eids_exempt ? 'Tapusuz İlan' : 'EİDS Doğrulandı') }}</span>
                    </div>
                    <h1 class="max-w-4xl text-2xl font-extrabold leading-tight sm:text-3xl">{{ $listing->title }}</h1>
                    @if($location)
                        <p class="mt-3 text-sm text-slate-300"><i class="fa-solid fa-location-dot mr-2 text-orange-400"></i>{{ $location }}</p>
                    @endif
                </div>
                <div class="shrink-0 lg:text-right">
                    <div class="text-3xl font-extrabold text-orange-400">{{ \App\Support\ListingDecimal::format($listing->price) }} {{ $currency }}</div>
                    <p class="mt-1 text-xs text-slate-400">İlan No: {{ str_pad((string) $listing->getKey(), 8, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0 space-y-7">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-listing-gallery>
                        <p class="px-4 py-2 text-xs text-slate-500">Görseller izinsiz kullanılamaz</p>
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100 sm:aspect-[16/9]">
                            <img draggable="false" data-listing-photo data-gallery-main src="{{ $images->first()['url'] }}" alt="{{ $images->first()['alt'] }}" class="h-full w-full object-cover">
                            @if($images->count() > 1)
                                <button type="button" data-gallery-prev class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/80 text-white shadow transition hover:bg-orange-500" aria-label="Önceki fotoğraf"><i class="fa-solid fa-chevron-left"></i></button>
                                <button type="button" data-gallery-next class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/80 text-white shadow transition hover:bg-orange-500" aria-label="Sonraki fotoğraf"><i class="fa-solid fa-chevron-right"></i></button>
                            @endif
                            <span data-gallery-counter class="absolute bottom-3 right-3 rounded-lg bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-white">1 / {{ $images->count() }}</span>
                        </div>
                        @if($images->count() > 1)
                            <div class="flex gap-2 overflow-x-auto p-3">
                                @foreach($images as $index => $image)
                                    <button type="button" data-gallery-thumb data-index="{{ $index }}" data-src="{{ $image['url'] }}" data-alt="{{ $image['alt'] }}" class="h-20 w-28 shrink-0 overflow-hidden rounded-lg border-2 {{ $index === 0 ? 'border-orange-500' : 'border-transparent' }}" aria-label="{{ $index + 1 }}. fotoğrafı göster">
                                        <img draggable="false" data-listing-photo src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="h-full w-full object-cover" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @if($features->isNotEmpty())
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <h2 class="mb-5 text-xl font-extrabold text-blue-950">İlan özellikleri</h2>
                            <dl class="grid gap-x-6 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($features as [$label, $value, $icon])
                                    <div class="flex items-center gap-3 border-b border-slate-100 py-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-500"><i class="fa-solid {{ $icon }}"></i></span>
                                        <div class="min-w-0">
                                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                                            <dd class="truncate text-sm font-bold text-slate-800">{{ $value }}</dd>
                                        </div>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h2 class="mb-5 text-xl font-extrabold text-blue-950">İlan açıklaması</h2>
                        <div class="listing-description prose max-w-none text-sm leading-7 text-slate-600">{!! \App\Support\ListingDescriptionHtml::render($listing->description) !!}</div>
                    </section>

                    <x-listing-eids-card :card="$eidsCard" />
                    <x-listing-extra-details :listing="$listing" />

                    @if($listing->video_path)
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                            <h2 class="mb-5 text-xl font-extrabold text-blue-950">İlan videosu</h2>
                            <video controls playsinline preload="metadata" class="aspect-video w-full rounded-xl bg-slate-950">
                                <source src="{{ \App\Services\ListingMediaService::publicUrl($listing->video_path) }}">
                                Tarayıcınız video oynatmayı desteklemiyor.
                            </video>
                        </section>
                    @endif

                    @if($youtubeId || $listing->video_url)
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                            <h2 class="mb-5 text-xl font-extrabold text-blue-950">İlan videosu</h2>
                            @if($youtubeId)
                                <div class="aspect-video overflow-hidden rounded-xl bg-slate-950">
                                    <iframe class="h-full w-full" src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}" title="{{ $listing->title }} videosu" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            @else
                                <a href="{{ $listing->video_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-3 text-sm font-bold text-white hover:bg-orange-600"><i class="fa-solid fa-play"></i>Videoyu aç</a>
                            @endif
                        </section>
                    @endif
                </div>

                <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wider text-orange-500">İlan sahibi</p>
                        <h2 class="mt-2 text-xl font-extrabold text-blue-950">{{ $contactName }}</h2>
                        @if($profile?->license_number)
                            <p class="mt-1 text-xs text-slate-500">Yetki belgesi: {{ $profile->license_number }}</p>
                        @endif
                        <div class="mt-5 space-y-3">
                            @if($contactPhone)
                                <a href="tel:{{ $phoneHref }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-orange-600"><i class="fa-solid fa-phone"></i>{{ $contactPhone }}</a>
                            @endif
                            @if($whatsappNumber)
                                <a href="https://wa.me/{{ $whatsappNumber }}?text={{ urlencode($listing->title.' ilanı hakkında bilgi almak istiyorum: '.route('listings.show', $listing->slug)) }}" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-600"><i class="fa-brands fa-whatsapp text-lg"></i>WhatsApp ile yaz</a>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-base font-extrabold text-blue-950">Konum</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600"><i class="fa-solid fa-location-dot mr-2 text-orange-500"></i>{{ $location ?: 'Konum bilgisi paylaşılmamış.' }}</p>
                        @if($location)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($location) }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-orange-600 hover:text-orange-700"><i class="fa-solid fa-map-location-dot"></i>Haritada görüntüle</a>
                        @endif
                        @if(empty($details['hide_location']) && isset($details['latitude'], $details['longitude']))
                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($details['latitude'].','.$details['longitude']) }}" target="_blank" rel="noopener noreferrer" class="mt-3 block text-sm font-bold text-orange-600">Seçilen harita noktasını aç</a>
                        @endif
                    </section>

                    @if(empty($isPreview))
                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                        <div class="flex gap-3">
                            <i class="fa-solid fa-shield-check mt-0.5 text-xl text-emerald-600"></i>
                            <div><strong class="block">{{ $listing->eids_exempt ? 'Tapusuz ilan' : 'EİDS doğrulamalı ilan' }}</strong><span class="mt-1 block text-xs leading-5 text-emerald-700">{{ $listing->eids_exempt ? 'Bu ilan tapusuz olduğu için EİDS doğrulaması uygulanmadan yönetici onayıyla yayınlanmıştır.' : 'Bu ilanın yetki ve taşınmaz bilgileri yayınlanmadan önce doğrulanmıştır.' }}</span></div>
                        </div>
                    </section>
                    @endif
                </aside>
            </div>

            @if($similarListings->isNotEmpty())
                <section class="mt-12 border-t border-slate-200 pt-10">
                    <div class="mb-6 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Benzer seçenekler</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-blue-950">İlginizi çekebilecek ilanlar</h2>
                        </div>
                        <a href="{{ route('listings.index') }}" class="hidden text-sm font-bold text-orange-600 hover:text-orange-700 sm:block">Tümünü gör <i class="fa-solid fa-arrow-right ml-1"></i></a>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($similarListings as $similarListing)
                            <x-modern-listing-card :listing="$similarListing" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
        @includeIf('partials.mobile-selected-details', ['item' => $listing])
    </main>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-listing-gallery]').forEach((gallery) => {
                const main = gallery.querySelector('[data-gallery-main]');
                const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
                const counter = gallery.querySelector('[data-gallery-counter]');
                let current = 0;

                const show = (index) => {
                    if (!thumbs.length) return;
                    current = (index + thumbs.length) % thumbs.length;
                    const thumb = thumbs[current];
                    main.src = thumb.dataset.src;
                    main.alt = thumb.dataset.alt;
                    counter.textContent = `${current + 1} / ${thumbs.length}`;
                    thumbs.forEach((item, itemIndex) => {
                        item.classList.toggle('border-orange-500', itemIndex === current);
                        item.classList.toggle('border-transparent', itemIndex !== current);
                    });
                    thumb.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
                };

                thumbs.forEach((thumb, index) => thumb.addEventListener('click', () => show(index)));
                gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => show(current - 1));
                gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(current + 1));
            });
        </script>
    @endpush
</x-layouts.modern>

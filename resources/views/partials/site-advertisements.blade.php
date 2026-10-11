@php
    $edgeAds = \App\Models\Advertisement::query()->where('is_active', true)
        ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
        ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
        ->orderBy('placement')->get();
    $horizontalAds = $edgeAds->filter(fn ($ad) => filled($ad->horizontal_image_path))
        ->sortBy('rotation_interval_seconds')->values();
    $adLogoUrl = app(\App\Services\SiteLogoService::class)->url('fe-assets/assets/img/zydemlak-logo-transparent.png');
@endphp

        <div class="home-edge-ads" aria-label="Reklam alanları">
            @foreach(['left' => 'Sol', 'right' => 'Sağ'] as $side => $label)
                @php $sideAds = $edgeAds->where('placement', $side)->values(); @endphp
                <aside class="home-edge-ad home-edge-ad-{{ $side }}" data-ad-slot="{{ $side }}">
                    @if($sideAds->isNotEmpty())
                        @foreach($sideAds as $ad)
                            <a href="{{ $ad->target_url ?: '#' }}" @if($ad->target_url) target="_blank" rel="sponsored noopener noreferrer" @else aria-disabled="true" @endif data-ad-interval="{{ $ad->rotation_interval_seconds }}" @if(!$loop->first) hidden @endif>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ad->image_path) }}" alt="{{ $ad->label ?: $label.' reklamı' }}" loading="lazy">
                            </a>
                        @endforeach
                    @else
                        <div class="home-edge-ad-empty">
                            <img src="{{ $adLogoUrl }}" alt="ZYDEMLAK logosu" loading="lazy">
                            <strong>ZYDEMLAK.COM</strong>
                            <span>Markanız burada öne çıksın.</span>
                            <a href="{{ route('contact') }}">Reklam için iletişim</a>
                        </div>
                    @endif
                </aside>
            @endforeach
        </div>
    @if($horizontalAds->isNotEmpty())
        @php $horizontalAdSlots = $horizontalAds->take(2); @endphp
        <section class="home-horizontal-ad-band" aria-label="Reklamlar">
            <div class="home-horizontal-ad-grid {{ $horizontalAdSlots->count() === 1 ? 'home-horizontal-ad-grid-single' : '' }}" data-horizontal-ad-rotation>
                @foreach($horizontalAdSlots as $initialAd)
                    <div class="home-horizontal-ad-slot" data-horizontal-ad-slot>
                        @foreach($horizontalAds as $ad)
                            <a href="{{ $ad->target_url ?: '#' }}" @if($ad->target_url) target="_blank" rel="sponsored noopener noreferrer" @else aria-disabled="true" @endif data-horizontal-choice data-ad-id="{{ $ad->id }}" data-ad-interval="{{ $ad->rotation_interval_seconds }}" @if($ad->id !== $initialAd->id) hidden @endif>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ad->horizontal_image_path) }}" alt="{{ $ad->label }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section class="home-horizontal-ad-band" aria-label="Reklam alanları">
            <div class="home-horizontal-ad-grid">
                @foreach(range(1, 2) as $placeholder)
                    <div class="home-horizontal-ad-slot home-horizontal-ad-placeholder">
                        <img src="{{ $adLogoUrl }}" alt="ZYDEMLAK logosu" loading="lazy">
                        <div>
                            <strong>ZYDEMLAK.COM</strong>
                            <p>Markanız burada öne çıksın.</p>
                            <a href="{{ route('contact') }}">Reklam için iletişim</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif


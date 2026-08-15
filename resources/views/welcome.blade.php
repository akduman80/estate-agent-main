<x-layouts.app>
    @section('title', 'Zyd Emlak')

    @php
        // Redis belleğinden yüksek hızda verileri çekiyoruz
        $sliders     = \App\Models\Setting::get('home_sliders', []);
        $sliderTitle = \App\Models\Setting::get('home_slider_title', 'PROPERTY SEARCHING JUST GOT SO EASY');
        $eidsText    = \App\Models\Setting::get('eids_text', 'Ticaret Bakanlığı Elektronik İlan Doğrulama Sistemi (EİDS) güvencesiyle onaylı taşınmaz ilanları.');
        // Redis belleğinden son aktif 12 ilanı ilişkili resimleriyle çekiyoruz
        $recentListings = \Illuminate\Support\Facades\Cache::remember('home_recent_listings', 3600, function () {
            return \App\Models\Listing::with(['images', 'eidsVerification'])
                ->where('is_active', true)
                ->latest()
                ->take(12)
                ->get();
        });
    @endphp

    <!-- Slider Area -->
    <div class="slider-area">
        <div class="slider">
            <div id="bg-slider" class="owl-carousel owl-theme">
                @forelse($sliders as $slide)
                    <div class="item">
                        <img src="{{ Storage::url($slide['image']) }}" alt="{{ $slide['title'] ?? 'Estate Slide' }}">
                    </div>
                @empty
                    <div class="item">
                        <img src="{{ asset('fe-assets/assets/img/slide1/slider-image-2.jpg') }}" alt="Garo Estate Slide 1">
                    </div>
                @endforelse
            </div>
        </div>
        <div class="container slider-content">
            <div class="row">
                <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 text-center">
                    <h2>{{ $sliderTitle }}</h2>
                    <p>{{ $eidsText }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Livewire Arama ve İlan Listesi Bileşeni -->
    @livewire('property-search')
    <!-- Property Area -->
    <div class="content-area recent-property">
        <div class="container">
            <div class="row">
                <div class="col-md-12 padding-top-40 properties-page">

                    {{-- Başlık ve Sıralama Barları --}}
                    <div class="col-md-12">
                        <div class="col-xs-10 page-subheader sorting pl0">
                            <ul class="sort-by-list">
                                <li class="active">
                                    <a href="javascript:void(0);" class="order_by_date">
                                        Güncel İlanlar <i class="fa fa-sort-amount-asc"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="col-xs-2 layout-switcher">
                            <a class="layout-list" href="javascript:void(0);" id="btn-list-view">
                                <i class="fa fa-th-list"></i>
                            </a>
                            <a class="layout-grid active" href="javascript:void(0);" id="btn-grid-view">
                                <i class="fa fa-th"></i>
                            </a>
                        </div>
                    </div>

                    {{-- İlan Kartları Izgarası --}}
                    <div class="col-md-12">
                        <div id="list-type" class="proerty-th">
                            @forelse($recentListings as $listing)
                                {{-- DİNAMİK BLADE COMPONENT --}}
                                <x-listing-card :listing="$listing" />
                            @empty
                                <div class="col-md-12 text-center py-5">
                                    <h4>Henüz eklenmiş bir ilan bulunamadı.</h4>
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

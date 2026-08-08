<x-layouts.app>
    @section('title', 'Zyd Emlak')

    @php
        // Redis belleğinden yüksek hızda verileri çekiyoruz
        $sliders     = \App\Models\Setting::get('home_sliders', []);
        $sliderTitle = \App\Models\Setting::get('home_slider_title', 'PROPERTY SEARCHING JUST GOT SO EASY');
        $eidsText    = \App\Models\Setting::get('eids_text', 'Ticaret Bakanlığı Elektronik İlan Doğrulama Sistemi (EİDS) güvencesiyle onaylı taşınmaz ilanları.');
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

</x-layouts.app>

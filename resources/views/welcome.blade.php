<x-layouts.app>
    @section('title', 'Zyd Emlak')

    <!-- Slider Area -->
    <div class="slider-area">
        <div class="slider">
            <div id="bg-slider" class="owl-carousel owl-theme">
                <div class="item">
                    <img src="{{ asset('fe-assets/assets/img/slide1/slider-image-2.jpg') }}" alt="Garo Estate Slide 1">
                </div>
                <div class="item">
                    <img src="{{ asset('fe-assets/assets/img/slide1/slider-image-4.jpg') }}" alt="Garo Estate Slide 2">
                </div>
            </div>
        </div>
        <div class="container slider-content">
            <div class="row">
                <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 text-center">
                    <h2>PROPERTY SEARCHING JUST GOT SO EASY</h2>
                    <p>Ticaret Bakanlığı Elektronik İlan Doğrulama Sistemi (EİDS) güvencesiyle onaylı taşınmaz ilanları.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Livewire Arama ve İlan Listesi Bileşeni -->
    @livewire('property-search')

</x-layouts.app>

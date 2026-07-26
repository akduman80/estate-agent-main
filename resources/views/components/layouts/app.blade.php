<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name', 'GARO ESTATE'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700,800' rel='stylesheet' type='text/css'>

    <!-- CSS Varlıkları -->
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/normalize.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/fontello.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/fonts/icon-7-stroke/css/pe-icon-7-stroke.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/fonts/icon-7-stroke/css/helper.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/icheck.min_all.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/price-range.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/owl.theme.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/owl.transitions.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('fe-assets/assets/css/responsive.css') }}">

    @livewireStyles
</head>
<body>

<!-- Preloader (Devre Dışı / Sabit Gizli) -->
<div id="preloader" style="display: none !important;">
    <div id="status">&nbsp;</div>
</div>

<!-- Header Connect -->
<div class="header-connect">
    <div class="container">
        <div class="row">
            <div class="col-md-5 col-sm-8 col-xs-12">
                <div class="header-half header-call">
                    <p>
                        <span><i class="pe-7s-call"></i> +90 (850) 000 00 00</span>
                        <span><i class="pe-7s-mail"></i> info@estate.com</span>
                    </p>
                </div>
            </div>
            <div class="col-md-2 col-md-offset-5 col-sm-3 col-sm-offset-1 col-xs-12">
                <div class="header-half header-social">
                    <ul class="treeform-social">
                        @auth
                            <li>
                                <a href="{{ auth()->user()->role->getRedirectPath() }}" class="btn btn-xs btn-primary text-white">
                                    <i class="fa fa-dashboard"></i> Panelim
                                </a>
                            </li>
                        @else
                            <li><a href="{{ route('login') }}"><i class="fa fa-user"></i> Giriş Yap</a></li>
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navigation">
                <span class="sr-only">Menü</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('fe-assets/assets/img/logo.png') }}" alt="Garo Estate Logo">
            </a>
        </div>

        <div class="collapse navbar-collapse yamm" id="navigation">
            <ul class="main-nav nav navbar-nav navbar-right">
                <li class="{{ request()->is('/') ? 'active' : '' }}"><a href="{{ url('/') }}">Ana Sayfa</a></li>
                <li class="{{ request()->is('ilanlar*') ? 'active' : '' }}"><a href="{{ url('/ilanlar') }}">İlanlar</a></li>
                <li class="{{ request()->is('haberler*') ? 'active' : '' }}"><a href="{{ url('/haberler') }}">Haberler / Blog</a></li>
                <li><a href="{{ url('/iletisim') }}">İletişim</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Dinamik İçerik Kapsayıcısı -->
<main>
    {{ $slot }}
</main>

<!-- Footer Area (Animasyon sınıfları kaldırılarak sabitlendi) -->
<div class="footer-area">
    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <div class="single-footer">
                        <h4>Hakkımızda</h4>
                        <div class="footer-title-line"></div>
                        <img src="{{ asset('fe-assets/assets/img/footer-logo.png') }}" alt="Logo">
                        <p>EİDS Onaylı Güvenilir Emlak Platformu.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="single-footer">
                        <h4>Hızlı Bağlantılar</h4>
                        <div class="footer-title-line"></div>
                        <ul class="footer-menu">
                            <li><a href="{{ url('/ilanlar') }}">İlanlar</a></li>
                            <li><a href="{{ url('/haberler') }}">Haberler</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="single-footer news-letter">
                        <h4>EİDS Bilgilendirme</h4>
                        <div class="footer-title-line"></div>
                        <p>Tüm ilanlarımız Ticaret Bakanlığı Elektronik İlan Doğrulama Sistemi üzerinden doğrulanmaktadır.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS Varlıkları -->
<script src="{{ asset('fe-assets/assets/js/modernizr-2.6.2.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/jquery-1.10.2.min.js') }}"></script>
<script src="{{ asset('fe-assets/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/bootstrap-hover-dropdown.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/easypiechart.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/jquery.easypiechart.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/icheck.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/price-range.js') }}"></script>

@livewireScripts

<script src="{{ asset('fe-assets/assets/js/main.js') }}"></script>

<!-- Slider ve Preloader Garanti Tetikleyicisi -->
<script>
    $(document).ready(function() {
        $("#preloader").hide();

        if ($.fn.owlCarousel && $("#bg-slider").length) {
            $("#bg-slider").owlCarousel({
                navigation: false,
                slideSpeed: 300,
                paginationSpeed: 400,
                singleItem: true,
                autoPlay: 5000
            });
        }
    });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Zyd Emlak')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        // Redis belleğinden genel site ayarlarını çekiyoruz
        $sitePhone = \App\Models\Setting::get('site_phone', '+90 (850) 000 00 00');
        $siteEmail = \App\Models\Setting::get('site_email', 'info@estate.com');
        $logoUrl   = app(\App\Services\SiteLogoService::class)->url('fe-assets/assets/img/logo.png');
        $headerDocuments = collect(\App\Models\Setting::get('user_documents', []))
            ->filter(fn ($document) => is_array($document) && ($document['is_active'] ?? true) && filled($document['title'] ?? null) && filled($document['file'] ?? null))
            ->sortBy(fn ($document) => (int) ($document['sort'] ?? 0))
            ->values();
    @endphp

        <!-- Favicon -->
    <link rel="shortcut icon" href="{{ $logoUrl }}" type="image/x-icon">
    <link rel="icon" href="{{ $logoUrl }}" type="image/x-icon">
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
    @include('partials.pwa', ['pwaAdmin' => false])
</head>
<body>

<!-- Preloader (Devre Dışı / Sabit Gizli) -->
<div id="preloader">
    <div id="status">&nbsp;</div>
</div>

<!-- Header Connect -->
<div class="header-connect">
    <div class="container">
        <div class="row">
            <!-- Sol Taraf: Telefon ve E-posta -->
            <div class="col-md-5 col-sm-8 col-xs-12">
                <div class="header-half header-call">
                    <p>
                        <span><i class="pe-7s-call"></i> {{ $sitePhone }}</span>
                        <span><i class="pe-7s-mail"></i> {{ $siteEmail }}</span>
                    </p>
                </div>
            </div>

            <!-- Sağ Taraf: Dokümanlar ve Panelim / Giriş Yap Butonu -->
            <div class="col-md-7 col-sm-4 col-xs-12">
                <div class="header-half header-social">
                    <ul class="treeform-social list-inline text-right mb-0 pt-0">
                        @if($headerDocuments->isNotEmpty())
                            <li class="dropdown header-documents-dropdown">
                                <a href="#" class="dropdown-toggle header-documents-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-file-text-o"></i> Dokümanlar <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    @foreach($headerDocuments as $index => $document)
                                        <li>
                                            <a href="{{ route('documents.download', ['index' => $index]) }}">
                                                <i class="fa fa-download"></i> {{ $document['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif

                        @auth
                            <li>
                                <a href="{{ auth()->user()->role->getRedirectPath() }}" class="btn btn-primary btn-sm padding-5">
                                    <i class="fa fa-dashboard"></i> Panelim
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}">
                                    <i class="fa fa-user"></i> Giriş Yap
                                </a>
                            </li>
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
                <img src="{{ $logoUrl }}" alt="Zyd Emlak">
            </a>
        </div>

        <div class="collapse navbar-collapse yamm" id="navigation">
            <ul class="main-nav nav navbar-nav navbar-right">
                <li class="{{ request()->is('/') ? 'active' : '' }}"><a href="{{ url('/') }}">Ana Sayfa</a></li>
                <li class="{{ request()->is('ilanlar*') ? 'active' : '' }}"><a href="{{ route('listings.index') }}">İlanlar</a></li>
                <li class="{{ request()->is('haberler*') ? 'active' : '' }}"><a href="{{ url('/haberler') }}">Haberler / Blog</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Dinamik İçerik Kapsayıcısı -->
<main>
    {{ $slot }}
</main>

<!-- Footer Area -->
<div class="footer-area">
    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <div class="single-footer">
                        <h4>Hakkımızda</h4>
                        <div class="footer-title-line"></div>
                        <img src="{{ $logoUrl }}" alt="Logo" class="wow pulse">
                        <p>EİDS Onaylı Güvenilir Emlak Platformu.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="single-footer">
                        <h4>Hızlı Bağlantılar</h4>
                        <div class="footer-title-line"></div>
                        <ul class="footer-menu">
                            <li><a href="{{ route('contact') }}">İletişim</a></li>
                            <li><a href="{{ route('listings.index') }}">İlanlar</a></li>
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

<!-- JS Varlıkları (SIRALAMA KRİTİKTİR) -->
<script src="{{ asset('fe-assets/assets/js/modernizr-2.6.2.min.js') }}"></script>

<!-- 1. ÖNCE JQUERY YÜKLENİR -->
<script src="{{ asset('fe-assets/assets/js/jquery-1.10.2.min.js') }}"></script>

<!-- 2. BOOTSTRAP VE DİĞER EKLENTİLER YÜKLENİR -->
<script src="{{ asset('fe-assets/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/bootstrap-hover-dropdown.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/easypiechart.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/jquery.easypiechart.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/wow.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/icheck.min.js') }}"></script>
<script src="{{ asset('fe-assets/assets/js/price-range.js') }}"></script>

@livewireScripts

<!-- 3. EN SON MAIN.JS YÜKLENİR -->
<script src="{{ asset('fe-assets/assets/js/main.js') }}"></script>

<!-- 4. JQUERY VE SWİTCHER KODLARI EN ALTTA ÇALIŞTIRILIR -->
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

        // --- LISTE / KART GEÇİŞİ ---
        $('#btn-list-view').on('click', function(e) {
            e.preventDefault();
            $('#btn-grid-view').removeClass('active');
            $(this).addClass('active');

            $('#list-type').removeClass('proerty-th').addClass('proerty-format');
            $('#list-type .listing-card-item').removeClass('col-sm-6 col-md-3').addClass('col-md-12');
            $('#list-type .item-entry p').show();
        });

        $('#btn-grid-view').on('click', function(e) {
            e.preventDefault();
            $('#btn-list-view').removeClass('active');
            $(this).addClass('active');

            $('#list-type').removeClass('proerty-format').addClass('proerty-th');
            $('#list-type .listing-card-item').removeClass('col-md-12').addClass('col-sm-6 col-md-3');
            $('#list-type .item-entry p').hide();
        });
    });
</script>
</body>
</html>

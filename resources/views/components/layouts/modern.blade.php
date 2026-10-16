<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ZYDEMLAK' }}</title>
    <meta name="description" content="{{ $description ?? 'Osmaniye emlak ilanları ve güncel gayrimenkul haberleri.' }}">

    @php
        $sitePhone = \App\Models\Setting::get('site_phone', '0530 360 77 69');
        $siteEmail = \App\Models\Setting::get('site_email', 'info@zydemlak.com');
        $logoUrl = app(\App\Services\SiteLogoService::class)->url('fe-assets/assets/img/zydemlak-logo-transparent.png');
        $headerDocuments = collect(\App\Models\Setting::get('user_documents', []))
            ->filter(fn ($document) => is_array($document) && ($document['is_active'] ?? true) && filled($document['title'] ?? null) && filled($document['file'] ?? null))
            ->sortBy(fn ($document) => (int) ($document['sort'] ?? 0))
            ->values();
    @endphp

    <link rel="icon" href="{{ $logoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @if(request()->routeIs('home'))
        <link rel="stylesheet" href="{{ asset('css/home-welcome.css') }}">
    @endif
    @stack('head')
    @include('partials.pwa', ['pwaAdmin' => false])
</head>
<body class="flex min-h-screen flex-col overflow-x-hidden bg-slate-50 font-sans text-slate-800 antialiased {{ auth()->check() ? 'has-phone-bottom-nav' : '' }}">
    @if(request()->routeIs('home'))
        <x-home-welcome />
    @endif
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex h-20 max-w-screen-2xl items-center justify-between gap-3 px-4 sm:h-20 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center rounded bg-white" aria-label="ZYDEMLAK ana sayfa">
                <img src="{{ $logoUrl }}" alt="ZYDEMLAK" class="h-14 w-auto max-w-36 object-contain transition duration-300 group-hover:scale-105 sm:h-16 sm:max-w-44">
            </a>

            <nav class="hidden items-center gap-1 text-sm font-bold xl:flex xl:gap-2" aria-label="Ana menü">
                <a href="{{ route('home') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('home') ? 'bg-slate-100 font-bold text-blue-950' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-500' }}">Anasayfa</a>
                <a href="{{ route('listings.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 font-bold text-slate-600 transition hover:bg-slate-50 hover:text-orange-500">Tüm İlanlar</a>
                <a href="{{ route('blogs.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('blogs.*') ? 'bg-slate-100 font-bold text-blue-950' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-500' }}">Emlak Haberleri</a>
                <a href="{{ route('projects.index') }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 font-extrabold shadow-[0_10px_22px_rgb(15_23_42_/_0.14),0_2px_5px_rgb(15_23_42_/_0.10)] transition {{ request()->routeIs('projects.*') ? 'bg-blue-950 text-white ring-2 ring-orange-300 ring-offset-2' : 'bg-blue-950 text-white hover:-translate-y-0.5 hover:bg-blue-900 hover:shadow-[0_14px_26px_rgb(15_23_42_/_0.18),0_3px_7px_rgb(15_23_42_/_0.12)]' }}">
                    <x-project-nav-icon class="h-5 w-5 shrink-0 text-orange-300" />
                    <span>Projeler</span>
                    <span class="rounded bg-orange-400 px-1.5 py-0.5 text-[10px] font-black uppercase leading-none text-blue-950">Yeni</span>
                </a>
            </nav>

            <div class="header-actions flex shrink-0 items-center gap-2">
                @if($headerDocuments->isNotEmpty())
                    <button type="button" data-documents-open aria-haspopup="dialog" aria-controls="documents-panel" aria-expanded="false" class="hidden lg:block cursor-pointer rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700"> <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Dokümanlar <span aria-hidden="true">⌄</span></button>
                @endif
                @auth
                    <a href="{{ auth()->user()->role?->getRedirectPath() ?? '/portal' }}" class="hidden items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:flex">
                        <i class="fa-solid fa-gauge-high"></i><span>Panelim</span>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="hidden items-center gap-2 whitespace-nowrap rounded-lg bg-blue-950 px-3 py-2 text-sm font-bold text-white transition hover:bg-blue-900 xl:flex">
                        <i class="fa-solid fa-user-plus"></i><span>Ücretsiz Üye Ol</span>
                    </a>
                    <a href="{{ route('login') }}" class="hidden items-center gap-2 whitespace-nowrap rounded-lg border-2 border-blue-950 bg-amber-300 px-3 py-2 text-sm font-bold text-blue-950 transition hover:bg-amber-400 sm:flex">
                        <i class="fa-solid fa-right-to-bracket"></i><span>Giriş Yap</span>
                    </a>
                @endauth
                <a href="{{ url('/portal/listings/create') }}" class="flex items-center gap-2 whitespace-nowrap rounded-lg bg-orange-500 px-3 py-2 text-sm font-bold text-white shadow transition hover:bg-orange-600 sm:px-4">
                    <i class="fa-solid fa-circle-plus"></i><span class="hidden sm:inline">Ücretsiz İlan Ver</span>
                </a>
                <button type="button" data-mobile-menu-button class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 xl:hidden" aria-label="Menüyü aç" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <div class="phone-actions px-3 pb-3">
            <a href="{{ url('/portal/listings/create') }}" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-orange-500 font-extrabold text-white shadow-sm"><i class="fa-solid fa-circle-plus"></i>Ücretsiz İlan Ver</a>
            <div class="mt-2 grid {{ auth()->check() ? 'grid-cols-1' : 'grid-cols-2' }} gap-2">
                @guest<a href="{{ route('register') }}" class="flex h-11 items-center justify-center gap-2 rounded-xl border border-blue-300 bg-blue-950 font-bold text-white"><i class="fa-solid fa-user-plus"></i>Ücretsiz Üye Ol</a>@endguest
                <a href="{{ auth()->check() ? (auth()->user()->role?->getRedirectPath() ?? '/portal') : route('login') }}" class="flex h-11 items-center justify-center gap-2 rounded-xl border-2 border-blue-950 bg-amber-300 font-bold text-blue-950">
                    <i class="fa-solid {{ auth()->check() ? 'fa-user' : 'fa-right-to-bracket' }}"></i>{{ auth()->check() ? 'Hesabım' : 'Giriş Yap' }}
                </a>
            </div>
        </div>

        <nav data-mobile-menu class="hidden border-t border-slate-100 bg-white px-4 py-3 xl:hidden" aria-label="Mobil menü">
            <div class="mx-auto grid max-w-7xl gap-1 text-sm font-bold">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 hover:bg-orange-50 hover:text-orange-600">Anasayfa</a>
                <a href="{{ route('listings.index') }}" class="rounded-lg px-3 py-2 font-bold hover:bg-orange-50 hover:text-orange-600">Tüm İlanlar</a>
                <a href="{{ route('blogs.index') }}" class="rounded-lg px-3 py-2 font-bold hover:bg-orange-50 hover:text-orange-600">Emlak Haberleri</a>
                <a href="{{ route('projects.index') }}" class="flex items-center justify-between rounded-lg bg-blue-950 px-3 py-3 font-extrabold text-white shadow-sm hover:bg-blue-900">
                    <span class="flex items-center gap-2"><x-project-nav-icon class="h-6 w-6 text-orange-300" /> Projeler</span>
                    <span class="rounded bg-orange-400 px-1.5 py-0.5 text-[10px] font-black uppercase leading-none text-blue-950">Yeni</span>
                </a>
                @if($headerDocuments->isNotEmpty())
                    <button type="button" data-documents-open aria-haspopup="dialog" aria-controls="documents-panel" aria-expanded="false" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-left font-bold text-orange-700">Dokümanlar <span aria-hidden="true">⌄</span></button>
                @endif
            </div>
        </nav>
    </header>

    {{ $slot }}



    <footer class="site-footer mt-auto border-t border-slate-800 bg-blue-950 text-blue-100">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <div>
                    <div class="flex items-center">
                        <img src="{{ $logoUrl }}" alt="ZYDEMLAK" class="h-20 w-auto max-w-52 rounded bg-white object-contain">
                    </div>
                    <p class="mt-4 text-xs leading-6 text-slate-400">Osmaniye'nin Emlak İlan ve Haber Sitesi. Güvenli, Şeffaf Ve Profesyonel Gayrimenkul Hizmetleri.</p>
                </div>
                <div>
                    <h2 class="mb-4 border-l-2 border-orange-500 pl-2 text-sm font-bold text-white">Hızlı Menü</h2>
                    <div class="space-y-2 text-xs">
                        <a href="{{ route('home') }}" class="block hover:text-orange-400">Anasayfa</a>
                        <a href="{{ route('listings.index') }}" class="block hover:text-orange-400">Tüm İlanlar</a>
                        <a href="{{ route('blogs.index') }}" class="block hover:text-orange-400">Emlak Haberleri</a>
                        <a href="{{ route('projects.index') }}" class="block hover:text-orange-400">Projeler</a>
                    </div>
                </div>
                <div>
                    <h2 class="mb-4 border-l-2 border-orange-500 pl-2 text-sm font-bold text-white">Kurumsal</h2>
                    <div class="space-y-2 text-xs">
                        <a href="{{ route('contact') }}" class="block hover:text-orange-400">İletişim</a>
                        <a href="{{ route('register') }}" class="block hover:text-orange-400">Üye Ol</a>
                        <a href="{{ route('login') }}" class="block hover:text-orange-400">Giriş Yap</a>
                    </div>
                </div>
                <div>
                    <h2 class="mb-4 border-l-2 border-orange-500 pl-2 text-sm font-bold text-white">İletişim</h2>
                    <div class="space-y-3 text-xs text-slate-400">
                        <p><i class="fa-solid fa-location-dot mr-2 text-orange-500"></i>Osmaniye, Türkiye</p>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sitePhone) }}" class="block hover:text-orange-400"><i class="fa-solid fa-phone mr-2 text-orange-500"></i>{{ $sitePhone }}</a>
                        <a href="mailto:{{ $siteEmail }}" class="block hover:text-orange-400"><i class="fa-solid fa-envelope mr-2 text-orange-500"></i>{{ $siteEmail }}</a>
                    </div>
                </div>
            </div>
            <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-800 pt-6 text-xs text-slate-500 sm:flex-row">
                <p>&copy; {{ now()->year }} ZYDEMLAK. Tüm hakları saklıdır.</p>
                @if(request()->routeIs('home'))
                    <x-eids-info footer />
                @else
                    <p>EİDS doğrulamalı güvenilir emlak platformu</p>
                @endif
            </div>
        </div>
    </footer>

    @auth
        <nav class="phone-bottom-nav" aria-label="Mobil hızlı menü">
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}"><i class="fa-solid fa-house"></i><span>Anasayfa</span></a>
            <a href="{{ route('listings.index') }}" class="{{ request()->routeIs('listings.*') ? 'is-active' : '' }}"><i class="fa-solid fa-list"></i><span>İlanlar</span></a>
            <a href="{{ url('/portal/listings/create') }}" class="phone-bottom-create"><i class="fa-solid fa-plus"></i><span>İlan Ver</span></a>
            <a href="{{ route('projects.index') }}" class="phone-bottom-project {{ request()->routeIs('projects.*') ? 'is-active' : '' }}"><span class="project-bottom-icon"><x-project-nav-icon class="h-5 w-5" /></span><span>Projeler</span></a>
        </nav>
    @endauth

    @livewireScripts
    @stack('scripts')
@include('partials.documents-panel', ['documentDownload' => true])
</body>
</html>

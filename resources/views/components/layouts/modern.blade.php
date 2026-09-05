<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ZYDEMLAK' }}</title>
    <meta name="description" content="{{ $description ?? 'Osmaniye emlak ilanları ve güncel gayrimenkul haberleri.' }}">

    @php
        $siteLogo = \App\Models\Setting::get('site_logo');
        $sitePhone = \App\Models\Setting::get('site_phone', '0530 360 77 69');
        $siteEmail = \App\Models\Setting::get('site_email', 'info@zydemlak.com');
        $logoUrl = $siteLogo ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteLogo) : asset('fe-assets/assets/img/zydemlak-logo.png');
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
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex h-24 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center" aria-label="ZYDEMLAK ana sayfa">
                <img src="{{ $logoUrl }}" alt="ZYDEMLAK" class="h-16 w-auto max-w-40 object-contain transition duration-300 group-hover:scale-105 sm:h-20 sm:max-w-52">
            </a>

            <nav class="hidden items-center gap-1 text-sm font-medium lg:flex" aria-label="Ana menü">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 transition {{ request()->routeIs('home') ? 'bg-slate-100 font-bold text-blue-950' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-500' }}">Anasayfa</a>
                <a href="{{ route('listings.index') }}" class="rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-orange-500">Tüm İlanlar</a>
                <a href="{{ route('blogs.index') }}" class="rounded-lg px-3 py-2 transition {{ request()->routeIs('blogs.*') ? 'bg-slate-100 font-bold text-blue-950' : 'text-slate-600 hover:bg-slate-50 hover:text-orange-500' }}">Emlak Haberleri</a>
                <a href="{{ route('contact') }}" class="rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-orange-500">İletişim</a>
            </nav>

            <div class="flex items-center gap-2">
                @if($headerDocuments->isNotEmpty())
                    <details class="group relative hidden lg:block">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 [&::-webkit-details-marker]:hidden">
                            <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                            <span>Dokümanlar</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition group-open:rotate-180" aria-hidden="true"></i>
                        </summary>
                        <div class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-2xl">
                            <p class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">İndirmeye hazır dokümanlar</p>
                            @foreach($headerDocuments as $index => $document)
                                <a href="{{ route('documents.download', ['index' => $index]) }}" download class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-700 transition hover:bg-orange-50 hover:text-orange-700">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1 font-semibold">{{ $document['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
                @auth
                    <a href="{{ auth()->user()->role->getRedirectPath() }}" class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:flex">
                        <i class="fa-solid fa-gauge-high"></i><span>Panelim</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 sm:flex">
                        <i class="fa-solid fa-right-to-bracket"></i><span>Giriş Yap</span>
                    </a>
                @endauth
                <a href="{{ url('/portal/listings/create') }}" class="flex items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-bold text-white shadow transition hover:bg-orange-600 sm:px-4">
                    <i class="fa-solid fa-circle-plus"></i><span class="hidden sm:inline">İlan Ver</span>
                </a>
                <button type="button" data-mobile-menu-button class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 lg:hidden" aria-label="Menüyü aç" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <nav data-mobile-menu class="hidden border-t border-slate-100 bg-white px-4 py-3 lg:hidden" aria-label="Mobil menü">
            <div class="mx-auto grid max-w-7xl gap-1 text-sm font-semibold">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 hover:bg-orange-50 hover:text-orange-600">Anasayfa</a>
                <a href="{{ route('listings.index') }}" class="rounded-lg px-3 py-2 hover:bg-orange-50 hover:text-orange-600">Tüm İlanlar</a>
                <a href="{{ route('blogs.index') }}" class="rounded-lg px-3 py-2 hover:bg-orange-50 hover:text-orange-600">Emlak Haberleri</a>
                @if($headerDocuments->isNotEmpty())
                    <details class="group rounded-lg border border-orange-200 bg-orange-50">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-orange-700 [&::-webkit-details-marker]:hidden">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Dokümanlar</span>
                            <i class="fa-solid fa-chevron-down text-xs transition group-open:rotate-180" aria-hidden="true"></i>
                        </summary>
                        <div class="space-y-1 border-t border-orange-100 bg-white p-2">
                            @foreach($headerDocuments as $index => $document)
                                <a href="{{ route('documents.download', ['index' => $index]) }}" download class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-700 hover:bg-orange-50 hover:text-orange-700">
                                    <i class="fa-solid fa-download text-orange-500" aria-hidden="true"></i>
                                    <span>{{ $document['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
                <a href="{{ route('contact') }}" class="rounded-lg px-3 py-2 hover:bg-orange-50 hover:text-orange-600">İletişim</a>
            </div>
        </nav>
    </header>

    {{ $slot }}

    <footer class="mt-auto border-t border-slate-800 bg-blue-950 text-slate-300">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <div>
                    <div class="flex items-center">
                        <img src="{{ $logoUrl }}" alt="ZYDEMLAK" class="h-20 w-auto max-w-52 rounded bg-white object-contain">
                    </div>
                    <p class="mt-4 text-xs leading-6 text-slate-400">Osmaniye'nin emlak ilan ve haber sitesi. Güvenli, şeffaf ve profesyonel gayrimenkul hizmetleri.</p>
                </div>
                <div>
                    <h2 class="mb-4 border-l-2 border-orange-500 pl-2 text-sm font-bold text-white">Hızlı Menü</h2>
                    <div class="space-y-2 text-xs">
                        <a href="{{ route('home') }}" class="block hover:text-orange-400">Anasayfa</a>
                        <a href="{{ route('listings.index') }}" class="block hover:text-orange-400">Tüm İlanlar</a>
                        <a href="{{ route('blogs.index') }}" class="block hover:text-orange-400">Emlak Haberleri</a>
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
                <p>EİDS doğrulamalı güvenilir emlak platformu</p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>
</html>

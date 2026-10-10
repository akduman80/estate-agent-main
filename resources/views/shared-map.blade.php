<x-layouts.modern title="İlan ve Proje Haritası | ZYDEMLAK">
    <main class="mx-auto w-full max-w-7xl px-4 py-8">
        <h1 class="mb-4 text-2xl font-bold">İlan ve Proje Haritası</h1>
        <p class="mb-4">İlanları ve projeleri birlikte inceleyebilir veya harita üzerindeki filtrelerle ayrı ayrı gösterebilirsiniz.</p>
        <div data-zyd-map data-config="{{ json_encode(['projects' => $mapRecords], JSON_UNESCAPED_UNICODE) }}"></div>
    </main>
    @push('head') @include('partials.zyd-map-assets') @endpush
</x-layouts.modern>

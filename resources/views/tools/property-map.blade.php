<x-layouts.modern title="Haritada İlan Ara | ZYDEMLAK">
    <main class="mx-auto w-full max-w-7xl bg-slate-50 px-4 py-6 text-slate-800" data-property-map data-endpoint="{{ route('tools.map.records') }}">
        <div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold text-blue-950">Haritada İlan Ara</h1><a data-map-list href="{{ route('listings.index') }}" class="rounded-xl border p-3 font-bold">Liste Görünümü</a></div>
        <p class="my-3 text-sm">Konumunu paylaşan yayındaki ilanlar gösterilir. Koordinatı bulunmayan veya konumu gizlenen ilanları liste görünümünde arayabilirsiniz.</p>
        <form data-map-filters data-location-selects class="my-4 grid grid-cols-2 gap-3 md:grid-cols-4">
            <label>İl<select name="city" data-city-select data-districts-url="{{ route('locations.districts') }}" data-neighborhoods-url="{{ route('locations.neighborhoods') }}" class="block w-full rounded-lg border p-3"><option value="">Tüm iller</option>@foreach($cities as $key=>$city)<option value="{{ $key }}">{{ $city }}</option>@endforeach</select></label>
            <label>İlçe<select name="district" data-district-select disabled class="block w-full rounded-lg border p-3"><option value="">Önce il seçin</option></select></label>
            <label>Mahalle / Köy<select name="neighborhood" data-neighborhood-select disabled class="block w-full rounded-lg border p-3"><option value="">Önce ilçe seçin</option></select></label>
            <label>Durum<select name="type" class="block w-full rounded-lg border p-3"><option value="">Tümü</option>@foreach($types as $value)<option>{{ $value }}</option>@endforeach</select></label>
            <label>Kategori<select name="category" class="block w-full rounded-lg border p-3"><option value="">Tümü</option>@foreach($categories as $value)<option>{{ $value }}</option>@endforeach</select></label>
            @foreach(['min_price'=>'En az fiyat (TL)','max_price'=>'En çok fiyat (TL)','min_area'=>'En az alan (m²)','max_area'=>'En çok alan (m²)'] as $key=>$label)<label>{{ $label }}<input name="{{ $key }}" type="number" min="0" step="0.01" class="block w-full rounded-lg border p-3"></label>@endforeach
            <button class="rounded-xl bg-orange-500 p-3 font-bold text-white">Filtreleri Uygula</button>
        </form>
        <p role="status" data-map-status class="my-3 text-sm"></p>
        <div data-map-stage class="rounded-xl border bg-white">
            <button type="button" data-map-fullscreen class="m-2 rounded-lg border p-3">Tam Ekran</button>
            <div data-map-canvas style="height:60vh; min-height:320px" aria-label="İlan haritası"></div>
            <div data-map-cards class="flex gap-3 overflow-x-auto p-3" aria-label="Haritadaki ilanlar"></div>
        </div>
        <button type="button" data-map-more hidden class="my-3 rounded-xl border p-3">Bu bölgedeki diğer ilanları yükle</button>
        <p class="my-3 text-xs text-slate-500">Harita: OpenStreetMap katkıcıları. Harita hizmeti üçüncü taraf sunuculardan yüklenir. Toplu indirme veya çevrimdışı kullanım sunulmaz.</p>
    </main>
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/leaflet-markercluster/MarkerCluster.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/leaflet-markercluster/MarkerCluster.Default.css') }}">
        <style>.property-map-expanded{position:fixed;inset:0;z-index:9999;overflow:auto;background:white}.property-map-expanded [data-map-canvas], [data-map-stage]:fullscreen [data-map-canvas]{height:70vh!important}[data-map-stage]:fullscreen{overflow:auto;background:white}</style>
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}" defer></script>
        <script src="{{ asset('vendor/leaflet-markercluster/leaflet.markercluster.js') }}" defer></script>
        <script src="{{ asset('js/property-map.js') }}" defer></script>
    @endpush
</x-layouts.modern>

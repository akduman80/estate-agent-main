<section id="projects-map-explorer" class="project-explorer mb-8" aria-label="Projeler Haritası"
    data-records="{{ json_encode($mapProjects, JSON_UNESCAPED_UNICODE) }}"
    data-parcel-url="{{ route('projects.parcel.lookup') }}" data-regions-url="{{ route('projects.map.regions') }}"
    data-transfer-url="{{ route('projects.map.transfer') }}" data-address-url="{{ route('projects.map.address') }}" data-csrf="{{ csrf_token() }}">
    <div class="project-explorer__header">
        <h2>Projeler Haritası</h2>
        <button type="button" data-action="expand" aria-pressed="false">Tam ekran</button>
    </div>
    <div class="project-explorer__toolbar" aria-label="Harita araçları">
        <button type="button" data-action="address" aria-expanded="false">Adrese Git</button>
        <button type="button" data-action="parcel" aria-expanded="false">Ada/Parselle Git</button>
        <button type="button" data-action="location">Konumum</button>
        <button type="button" data-action="selected" hidden>Seçilen konum</button>
        <label><input type="checkbox" data-layer="ilan" checked> İlanları Göster</label>
        <label><input type="checkbox" data-layer="proje" checked> Projeleri Göster</label>
    </div>
    <div class="project-explorer__toolbar" aria-label="Harita görünümü">
        <button type="button" data-basemap="normal" aria-pressed="true">Normal</button>
        <button type="button" data-basemap="satellite" aria-pressed="false">Uydu</button>
        <label><input type="checkbox" data-boundary checked> Parsel sınırı</label>
        <span data-count></span>
    </div>
    <div class="project-explorer__body">
        <div id="project-map" class="project-explorer__map" aria-label="İlan ve proje konumları"></div>
        <aside class="project-explorer__panel" data-panel hidden>
            <button type="button" class="project-explorer__close" data-action="close" aria-label="Harita panelini kapat">×</button>
            <form data-address-form hidden>
                <h3>Adrese Git</h3>
                <label for="project-address-query">İl, ilçe, mahalle/köy, sokak ve kapı numarası</label>
                <div class="project-explorer__search-row">
                    <input id="project-address-query" name="q" type="search" maxlength="250" placeholder="Osmaniye Merkez Raufbey" required>
                    <button type="button" data-action="voice" aria-label="Türkçe sesli arama">🎙</button>
                    <button type="submit">Ara</button>
                </div>
                <p class="project-explorer__hint">Adres araması OpenStreetMap Nominatim üzerinden yapılır.</p>
            </form>
            <form data-parcel-form hidden>
                <h3>Ada/Parselle Git</h3>
                <label>İl<select name="province" required><option value="">İl seçin</option></select></label>
                <label>İlçe<select name="district" required disabled><option value="">Önce il seçin</option></select></label>
                <label>Mahalle / Köy<select name="neighborhood" required disabled><option value="">Önce ilçe seçin</option></select></label>
                <div class="project-explorer__search-row">
                    <label>Ada<input name="block" inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" required></label>
                    <label>Parsel<input name="parcel" inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" required></label>
                </div>
                <button type="submit">Parseli Bul</button>
            </form>
            <div data-results hidden aria-label="Adres sonuçları"></div>
            <section data-location-details hidden>
                <h3>Seçilen konum</h3>
                <dl>
                    @foreach(['address' => 'Adres', 'city' => 'İl', 'district' => 'İlçe', 'neighborhood' => 'Mahalle / Köy', 'street' => 'Sokak', 'door' => 'Kapı numarası', 'block' => 'Ada', 'parcel' => 'Parsel', 'area' => 'Alan (m²)', 'quality' => 'Nitelik', 'coordinates' => 'Koordinatlar'] as $field => $label)
                        <div><dt>{{ $label }}</dt><dd data-field="{{ $field }}">Bulunamadı</dd></div>
                    @endforeach
                </dl>
                <label data-door-choice-label hidden>Kapı kaydı seçin<select data-door-choice aria-label="Kayıtlı sokak ve kapı numarası"></select></label>
                <p class="project-explorer__hint" data-address-source></p>
                <a data-google target="_blank" rel="noopener noreferrer">Google Haritalar’da Aç ↗</a>
                <div class="project-explorer__search-row">
                    <button type="button" data-transfer="listing">İlana Aktar</button>
                    <button type="button" data-transfer="project">Projeye Aktar</button>
                </div>
            </section>
            <section data-record-details hidden></section>
        </aside>
    </div>
    <p class="project-explorer__status" data-status role="status" aria-live="polite">Harita yükleniyor…</p>
    <p class="project-explorer__hint project-explorer__attribution">Adres bilgileri OpenStreetMap, parsel bilgileri TKGM üzerinden sorgulanır. Kadastro bilgileri resmî işlem belgesi değildir.</p>
</section>

(() => {
    'use strict';
    const root = document.getElementById('projects-map-explorer');
    if (!root) return;
    const find = selector => root.querySelector(selector);
    const status = message => { find('[data-status]').textContent = message; };
    if (!window.L) {
        status('Harita yüklenemedi. Sayfayı yenileyin. Proje listesi aşağıda kullanılabilir.');
        return;
    }
    const L = window.L;
    const records = JSON.parse(root.dataset.records || '[]');
    const map = L.map('project-map').setView([37.074, 36.246], 12);
    const basemaps = {
        normal: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxNativeZoom: 19, maxZoom: 21, attribution: 'Tiles &copy; Esri',
        }),
    };
    let basemap = basemaps.normal.addTo(map);
    const groups = { ilan: L.layerGroup().addTo(map), proje: L.layerGroup().addTo(map) };
    const addressLabels = L.layerGroup().addTo(map);
    let doorChoices = [];
    let choicesInParcel = false;
    let selected = null;
    let selectedMarker = null;
    let boundary = null;
    let generation = 0;
    let controller = null;
    let geolocationGeneration = 0;
    let lastGeocode = 0;
    let geocodeQueue = Promise.resolve();
    const panel = find('[data-panel]');
    const addressForm = find('[data-address-form]');
    const parcelForm = find('[data-parcel-form]');
    const results = find('[data-results]');
    const locationDetails = find('[data-location-details]');
    const recordDetails = find('[data-record-details]');

    const pin = (kind, label) => L.divIcon({
        className: `project-explorer__pin project-explorer__pin--${kind}`,
        html: label, iconSize: [30, 30], iconAnchor: [15, 15],
    });
    const validPoint = (lat, lon) => Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180;
    const resize = () => requestAnimationFrame(() => map.invalidateSize());
    new ResizeObserver(resize).observe(find('#project-map'));
    const setPanel = name => {
        panel.hidden = !name;
        addressForm.hidden = name !== 'address';
        parcelForm.hidden = name !== 'parcel';
        results.hidden = name !== 'results';
        locationDetails.hidden = name !== 'location';
        recordDetails.hidden = name !== 'record';
        for (const mode of ['address', 'parcel']) {
            find(`[data-action="${mode}"]`).setAttribute('aria-expanded', String(name === mode));
        }
        resize();
    };
    const beginRequest = () => {
        generation += 1;
        geolocationGeneration += 1;
        controller?.abort();
        controller = new AbortController();
        return { id: generation, signal: controller.signal };
    };
    const json = async (url, options = {}) => {
        const response = await fetch(url, { ...options, signal: options.signal || AbortSignal.timeout(15000), headers: { Accept: 'application/json', ...options.headers } });
        const body = await response.json().catch(() => null);
        if (!response.ok) {
            if (response.status === 401 || response.status === 419) throw new Error('Aktarmak için hesabınıza giriş yapın ve tekrar deneyin.');
            if (response.status === 403) throw new Error('Hesabınızın bu formu açma yetkisi bulunmuyor.');
            if (response.status === 429) throw new Error('Çok sık sorgu yapıldı. Biraz bekleyip tekrar deneyin.');
            throw new Error(body?.message || 'Servise ulaşılamıyor. Tekrar deneyin.');
        }
        if (!body) throw new Error('Servisten geçerli yanıt alınamadı.');
        return body;
    };
    const geocode = (path, params, signal) => {
        const request = geocodeQueue.catch(() => {}).then(async () => {
            const delay = Math.max(0, 1100 - (Date.now() - lastGeocode));
            if (delay) await new Promise(resolve => setTimeout(resolve, delay));
            signal.throwIfAborted();
            lastGeocode = Date.now();
            return json(`https://nominatim.openstreetmap.org/${path}?${new URLSearchParams({ format: 'jsonv2', addressdetails: '1', 'accept-language': 'tr', ...params })}`, { signal: AbortSignal.any([signal, AbortSignal.timeout(12000)]) });
        });
        geocodeQueue = request;
        return request;
    };
    const renderLocation = () => {
        if (!selected) return;
        const fields = { ...selected, coordinates: `${selected.latitude.toFixed(6)}, ${selected.longitude.toFixed(6)}` };
        root.querySelectorAll('[data-field]').forEach(node => {
            node.textContent = fields[node.dataset.field] ?? 'Bulunamadı';
            if (node.textContent === '') node.textContent = 'Bulunamadı';
            if (node.dataset.field === 'door' && !fields.door && choicesInParcel && doorChoices.length > 1) {
                node.textContent = `${doorChoices.length} kapı kaydı var. Aşağıdan seçin.`;
            }
        });
        find('[data-google]').href = `https://www.google.com/maps?q=${encodeURIComponent(`${selected.latitude},${selected.longitude}`)}`;
    };
    const useAddress = result => {
        const address = result?.address || {};
        Object.assign(selected, {
            address: result?.display_name || null,
            city: address.province || address.state || address.city || null,
            district: address.town || address.county || address.city_district || address.city || null,
            neighborhood: address.neighbourhood || address.quarter || address.suburb || address.village || null,
            street: address.road || address.pedestrian || address.residential || address.footway || null, door: address.house_number || null,
        });
    };
    const pointInRing = (ring, lat, lon) => {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const a = ring[i], b = ring[j];
            if ((a[1] > lat) !== (b[1] > lat) && lon < (b[0] - a[0]) * (lat - a[1]) / (b[1] - a[1]) + a[0]) inside = !inside;
        }
        return inside;
    };
    const inParcel = (geometry, lat, lon) => {
        const polygons = geometry?.type === 'Polygon' ? [geometry.coordinates] : geometry?.type === 'MultiPolygon' ? geometry.coordinates : [];
        return polygons.some(polygon => polygon.length && pointInRing(polygon[0], lat, lon)
            && !polygon.slice(1).some(hole => pointInRing(hole, lat, lon)));
    };
    const applyDoor = door => {
        selected.street = door.street;
        selected.door = door.door;
        selected.address = [door.street, `No ${door.door}`, selected.neighborhood, selected.district, selected.city].filter(Boolean).join(', ');
        find('[data-address-source]').textContent = 'Sokak ve kapı numarası TKGM MAKS numarataj kaydından alındı.';
        renderLocation();
    };
    const showOfficialAddresses = (reply, geometry) => {
        if (!reply) return;
        addressLabels.clearLayers();
        const candidates = reply.candidates || [];
        for (const street of reply.streets || []) {
            const label = document.createElement('span');
            label.textContent = street.name;
            L.marker([street.latitude, street.longitude], { interactive: false, keyboard: false,
                icon: L.divIcon({ className: 'project-explorer__street-label', html: label, iconSize: [0, 0] }) }).addTo(addressLabels);
        }
        for (const door of candidates) {
            const label = document.createElement('span');
            label.textContent = door.door;
            L.marker([door.latitude, door.longitude], { title: `${door.street} No ${door.door}`, alt: `${door.street} No ${door.door}`,
                icon: L.divIcon({ className: 'project-explorer__door-label', html: label, iconSize: [0, 0] }) }).addTo(addressLabels)
                .on('click', () => selectPoint(door.latitude, door.longitude));
        }
        const exact = candidates.filter(door => door.distance <= 3);
        const matching = candidates.filter(door => inParcel(geometry, door.latitude, door.longitude));
        choicesInParcel = matching.length > 0;
        const applicable = exact.length ? exact : matching;
        const unique = new Map();
        for (const door of applicable.length ? applicable : candidates) {
            const key = `${door.street}\n${door.door}`;
            if (!unique.has(key)) unique.set(key, door);
        }
        doorChoices = [...unique.values()];
        const choice = find('[data-door-choice]');
        choice.replaceChildren(new Option('Sokak ve kapı kaydı seçin', ''));
        doorChoices.forEach((door, index) => choice.add(new Option(`${door.street} · No ${door.door}`, String(index))));
        find('[data-door-choice-label]').hidden = doorChoices.length === 0 || (applicable.length > 0 && doorChoices.length === 1);
        find('[data-door-choice-label]').firstChild.textContent = choicesInParcel || exact.length ? 'Konumdaki kapı kaydını seçin' : 'Yakındaki kapı kaydını haritada açın';
        if (applicable.length && doorChoices.length === 1) {
            applyDoor(doorChoices[0]);
        } else {
            const streets = [...new Set(applicable.map(door => door.street))];
            if (streets.length === 1) selected.street = streets[0];
            else if (!selected.street && reply.street) selected.street = reply.street;
            find('[data-address-source]').textContent = applicable.length
                ? 'Bu konumda birden fazla numarataj kaydı var. Kapı kaydını seçin.'
                : candidates.length ? 'Yakındaki gerçek kapı numaraları haritada gösteriliyor. Adresi açmak için numaraya dokunun.'
                    : 'TKGM bu konum çevresinde kayıtlı kapı numarası döndürmedi.';
        }
        renderLocation();
    };
    find('[data-door-choice]').addEventListener('change', event => {
        if (event.target.value === '') return;
        const door = doorChoices[Number(event.target.value)];
        if (!door || !selected) return;
        if (choicesInParcel || door.distance <= 3) applyDoor(door);
        else selectPoint(door.latitude, door.longitude);
    });
    const useParcel = result => {
        if (!result?.found || !result.parcel) return;
        const parcel = result.parcel;
        for (const [field, value] of Object.entries({ city: parcel.province, district: parcel.district, neighborhood: parcel.neighborhood,
            block: parcel.block, parcel: parcel.parcel, area: parcel.area, quality: parcel.quality })) {
            if (value !== null && value !== undefined && value !== '') selected[field] = value;
        }
        if (result.geometry) {
            try {
                boundary?.remove();
                boundary = L.geoJSON(result.geometry, { interactive: false, style: { color: '#db8b19', weight: 3, fillOpacity: 0.15 } });
                if (find('[data-boundary]').checked) boundary.addTo(map);
            } catch { status('Parsel sınırı görüntülenemedi. Konum bilgilerini kullanabilirsiniz.'); }
        }
    };
    const selectPoint = async (lat, lon, address = null, parcel = null) => {
        if (!validPoint(lat, lon)) return;
        const request = beginRequest();
        selected = { latitude: lat, longitude: lon };
        doorChoices = [];
        choicesInParcel = false;
        addressLabels.clearLayers();
        find('[data-door-choice-label]').hidden = true;
        find('[data-address-source]').textContent = 'TKGM sokak ve kapı numarası sorgulanıyor…';
        selectedMarker?.remove();
        boundary?.remove();
        boundary = null;
        selectedMarker = L.marker([lat, lon], { icon: pin('selected', '●'), title: 'Seçilen konum', alt: 'Seçilen konum' }).addTo(map);
        selectedMarker.on('click', () => { setPanel('location'); renderLocation(); });
        map.setView([lat, lon], Math.max(18, map.getZoom()));
        find('[data-action="selected"]').hidden = false;
        setPanel('location');
        if (address) useAddress(address);
        if (parcel) useParcel(parcel);
        renderLocation();
        status('Konum bilgileri sorgulanıyor…');
        const addressRequest = (address ? Promise.resolve(address) : geocode('reverse', { lat, lon }, request.signal)).then(reply => {
            if (request.id === generation) { useAddress(reply); renderLocation(); }
            return reply;
        });
        const parcelRequest = parcel ? Promise.resolve(parcel) : json(`${root.dataset.parcelUrl}?${new URLSearchParams({ lat: lat.toFixed(7), lon: lon.toFixed(7) })}`, { signal: AbortSignal.any([request.signal, AbortSignal.timeout(12000)]) });
        const progressiveParcel = parcelRequest.then(reply => {
            if (request.id === generation) { useParcel(reply); renderLocation(); }
            return reply;
        });
        const officialRequest = json(`${root.dataset.addressUrl}?${new URLSearchParams({ lat: lat.toFixed(7), lon: lon.toFixed(7) })}`, {
            signal: AbortSignal.any([request.signal, AbortSignal.timeout(45000)]),
        });
        const replies = await Promise.allSettled([addressRequest, progressiveParcel, officialRequest]);
        if (request.id !== generation) return;
        if (replies[0].status === 'fulfilled') useAddress(replies[0].value);
        if (replies[1].status === 'fulfilled') useParcel(replies[1].value);
        if (replies[2].status === 'fulfilled') showOfficialAddresses(replies[2].value, replies[1].status === 'fulfilled' ? replies[1].value.geometry : null);
        else find('[data-address-source]').textContent = 'TKGM sokak ve kapı servisine ulaşılamadı. Yeniden konum seçerek tekrar deneyin.';
        renderLocation();
        const messages = [];
        if (replies[0].status === 'rejected') messages.push('Adres servisine ulaşılamadı.');
        if (replies[1].status === 'rejected') messages.push(replies[1].reason.message || 'Kadastro servisine ulaşılamadı.');
        else if (!replies[1].value.found) messages.push(replies[1].value.message || 'Bu noktada parsel bulunamadı.');
        if (replies[2].status === 'rejected') messages.push('Sokak ve kapı numarası sorgusu tamamlanamadı.');
        if (replies[2].status === 'fulfilled' && replies[2].value.partial) messages.push('Bazı numarataj kayıtlarına ulaşılamadı. Tekrar deneyebilirsiniz.');
        status(messages.join(' ') || 'Seçilen konum bilgileri hazır.');
    };
    const showRecord = record => {
        beginRequest();
        recordDetails.replaceChildren();
        const title = document.createElement('h3');
        title.textContent = record.title;
        const locationText = document.createElement('p');
        locationText.textContent = record.location;
        recordDetails.append(title, locationText);
        const safeUrl = value => {
            try { const url = new URL(value, window.location.origin); return ['http:', 'https:'].includes(url.protocol) ? url.href : null; }
            catch { return null; }
        };
        for (const photo of record.photos || []) {
            const source = safeUrl(photo);
            if (!source) continue;
            const img = document.createElement('img');
            img.src = source; img.alt = record.title; img.loading = 'lazy';
            recordDetails.append(img);
        }
        for (const video of record.videos || []) {
            const source = safeUrl(video);
            if (!source) continue;
            const element = document.createElement('video');
            element.src = source; element.controls = true; element.preload = 'none';
            recordDetails.append(element);
        }
        if (/^[\w-]{11}$/.test(record.youtube_id || '')) {
            const iframe = document.createElement('iframe');
            iframe.src = `https://www.youtube-nocookie.com/embed/${record.youtube_id}`;
            iframe.title = record.title; iframe.loading = 'lazy'; iframe.allowFullscreen = true;
            recordDetails.append(iframe);
        }
        const url = safeUrl(record.url);
        if (url) {
            const link = document.createElement('a');
            link.href = url; link.textContent = record.kind === 'ilan' ? 'İlanı aç' : 'Projeyi aç';
            recordDetails.append(link);
        }
        setPanel('record');
        status(record.kind === 'ilan' ? 'İlan seçildi.' : 'Proje seçildi.');
    };
    const bounds = [];
    for (const record of records) {
        const lat = Number(record.latitude), lon = Number(record.longitude);
        if (!validPoint(lat, lon) || !groups[record.kind]) continue;
        L.marker([lat, lon], { icon: pin(record.kind, record.kind === 'ilan' ? 'İ' : 'P'), title: record.title, alt: record.title })
            .addTo(groups[record.kind]).on('click', () => showRecord(record));
        bounds.push([lat, lon]);
    }
    if (bounds.length) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
    const updateCount = () => {
        const count = Object.values(groups).filter(group => map.hasLayer(group)).reduce((total, group) => total + group.getLayers().length, 0);
        find('[data-count]').textContent = `${count} kayıt haritada`;
    };
    updateCount();
    status('Konum seçmek için haritaya dokunun veya arama yapın.');
    map.on('click', event => { selectPoint(event.latlng.lat, event.latlng.lng); });
    root.querySelectorAll('[data-layer]').forEach(input => input.addEventListener('change', () => {
        const group = groups[input.dataset.layer];
        if (input.checked) group.addTo(map); else group.remove();
        updateCount();
    }));
    root.querySelectorAll('[data-basemap]').forEach(button => button.addEventListener('click', () => {
        basemap.remove();
        basemap = basemaps[button.dataset.basemap].addTo(map);
        root.querySelectorAll('[data-basemap]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    }));
    find('[data-boundary]').addEventListener('change', event => {
        if (boundary) { if (event.target.checked) boundary.addTo(map); else boundary.remove(); }
        else if (event.target.checked) status('Seçilen noktada gerçek parsel geometrisi bulunduğunda sınır gösterilir.');
    });
    addressForm.addEventListener('submit', async event => {
        event.preventDefault();
        const request = beginRequest();
        const query = addressForm.elements.q.value.trim();
        if (!query) return;
        results.replaceChildren();
        status('Adres aranıyor…');
        try {
            const matches = await geocode('search', { q: query, countrycodes: 'tr', limit: 8 }, request.signal);
            if (request.id !== generation) return;
            if (!matches.length) { status('Adres bulunamadı. İl, ilçe veya mahalle ekleyerek tekrar deneyin.'); return; }
            for (const match of matches) {
                const button = document.createElement('button');
                button.type = 'button'; button.textContent = match.display_name;
                button.addEventListener('click', () => selectPoint(Number(match.lat), Number(match.lon), match));
                results.append(button);
            }
            setPanel('results');
            status(`${matches.length} adres bulundu. Haritada açmak için bir sonuç seçin.`);
        } catch (error) { if (request.id === generation) status(error.message || 'Adres araması başarısız oldu.'); }
    });
    let regionsController = null;
    const resetSelect = (name, text) => {
        const select = parcelForm.elements[name];
        select.replaceChildren(new Option(text, '')); select.disabled = true;
    };
    const loadRegions = async (level, parent = '') => {
        regionsController?.abort();
        regionsController = new AbortController();
        const signal = regionsController.signal;
        const select = parcelForm.elements[level];
        resetSelect(level, 'Yükleniyor…');
        try {
            const reply = await json(`${root.dataset.regionsUrl}?${new URLSearchParams({ level, parent })}`, { signal: AbortSignal.any([signal, AbortSignal.timeout(15000)]) });
            if (signal.aborted) return;
            select.replaceChildren(new Option('Seçin', ''));
            reply.regions.forEach(region => select.add(new Option(region.name, region.id)));
            select.disabled = false;
        } catch (error) {
            if (!signal.aborted) { resetSelect(level, 'Yüklenemedi'); status(error.message); }
        }
    };
    parcelForm.elements.province.addEventListener('change', event => {
        regionsController?.abort();
        resetSelect('district', 'Önce il seçin'); resetSelect('neighborhood', 'Önce ilçe seçin');
        if (event.target.value) loadRegions('district', event.target.value);
    });
    parcelForm.elements.district.addEventListener('change', event => {
        regionsController?.abort(); resetSelect('neighborhood', 'Önce ilçe seçin');
        if (event.target.value) loadRegions('neighborhood', event.target.value);
    });
    parcelForm.addEventListener('submit', async event => {
        event.preventDefault();
        const request = beginRequest();
        const params = new URLSearchParams({ neighborhood_id: parcelForm.elements.neighborhood.value,
            block: parcelForm.elements.block.value, parcel: parcelForm.elements.parcel.value });
        status('Parsel aranıyor…');
        try {
            const parcel = await json(`${root.dataset.parcelUrl}?${params}`, { signal: AbortSignal.any([request.signal, AbortSignal.timeout(12000)]) });
            if (request.id !== generation) return;
            if (!parcel.found) { status(parcel.message || 'Parsel bulunamadı.'); return; }
            if (!parcel.geometry) { status('Parsel bilgisi bulundu ancak servis konum geometrisi vermedi. Haritada konumlandırılamadı.'); return; }
            const outline = L.geoJSON(parcel.geometry);
            const parcelBounds = outline.getBounds();
            if (!parcelBounds.isValid()) throw new Error('Parselin konum geometrisi okunamadı.');
            const center = parcelBounds.getCenter();
            await selectPoint(center.lat, center.lng, null, parcel);
            if (selected && selected.latitude === center.lat && selected.longitude === center.lng) map.fitBounds(parcelBounds, { padding: [30, 30], maxZoom: 19 });
        } catch (error) { if (request.id === generation) status(error.message); }
    });
    root.addEventListener('click', async event => {
        const button = event.target.closest('button');
        if (!button) return;
        const action = button.dataset.action;
        if (action === 'close') { beginRequest(); setPanel(null); }
        if (action === 'address') { beginRequest(); setPanel('address'); addressForm.elements.q.focus(); }
        if (action === 'parcel') {
            beginRequest(); setPanel('parcel');
            if (parcelForm.elements.province.options.length < 2) loadRegions('province');
        }
        if (action === 'selected' && selected) { setPanel('location'); renderLocation(); }
        if (action === 'expand') {
            const expanded = root.classList.toggle('project-explorer--expanded');
            button.setAttribute('aria-pressed', String(expanded)); button.textContent = expanded ? 'Küçült' : 'Tam ekran'; resize();
        }
        if (action === 'location') {
            if (!navigator.geolocation) { status('Tarayıcınız konum özelliğini desteklemiyor. Haritadan seçim yapabilirsiniz.'); return; }
            beginRequest();
            const id = ++geolocationGeneration;
            status('Konumunuz alınıyor…');
            navigator.geolocation.getCurrentPosition(position => {
                if (id === geolocationGeneration) selectPoint(position.coords.latitude, position.coords.longitude);
            }, () => { if (id === geolocationGeneration) status('Konum alınamadı. İzni kontrol edin veya haritadan seçim yapın.'); }, { enableHighAccuracy: true, timeout: 15000 });
        }
        if (action === 'voice') {
            const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!Speech) { status('Sesli arama bu tarayıcıda desteklenmiyor. Adresi yazarak arayabilirsiniz.'); return; }
            const recognition = new Speech();
            recognition.lang = 'tr-TR'; recognition.interimResults = false;
            let heard = false;
            recognition.onresult = reply => { heard = true; addressForm.elements.q.value = reply.results[0][0].transcript; status('Adres yazıldı. Aramak için Ara düğmesine basın.'); };
            recognition.onerror = () => { heard = true; status('Ses alınamadı veya mikrofon izni verilmedi. Adresi yazarak arayabilirsiniz.'); };
            recognition.onend = () => { button.disabled = false; if (!heard) status('Ses algılanmadı. Tekrar deneyin veya adresi yazın.'); };
            try { recognition.start(); button.disabled = true; status('Dinleniyor…'); }
            catch { button.disabled = false; status('Mikrofon başlatılamadı. Adresi yazarak arayabilirsiniz.'); }
        }
        if (button.dataset.transfer && selected) {
            button.disabled = true;
            const snapshot = { ...selected };
            const allowed = ['latitude', 'longitude', 'address', 'city', 'district', 'neighborhood', 'block', 'parcel', 'area', 'quality'];
            const payload = { target: button.dataset.transfer };
            allowed.forEach(key => { if (snapshot[key] !== undefined && snapshot[key] !== null) payload[key] = snapshot[key]; });
            try {
                const reply = await json(root.dataset.transferUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify(payload) });
                window.location.assign(reply.url);
            } catch (error) { status(error.message); }
            finally { button.disabled = false; }
        }
    });
    root.addEventListener('keydown', event => { if (event.key === 'Escape') { beginRequest(); setPanel(null); } });
})();

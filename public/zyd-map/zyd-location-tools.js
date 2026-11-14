(() => {
    const install = () => {
        const L = window.L;
        if (!L?.Map || !L?.map) return false;
        if (window.__zydLocationToolsInstalled) return true;
        window.__zydLocationToolsInstalled = true;

        let activeMap = null;
        let selectedMarker = null;
        let selectedBoundary = null;
        let lastLookup = 0;
        let lookupQueue = Promise.resolve();
        let lookupSequence = 0;
        const attachedMaps = new WeakSet();

        const throttledLookup = task => {
            const request = lookupQueue.then(async () => {
                const wait = Math.max(0, 1100 - (Date.now() - lastLookup));
                if (wait) await new Promise(resolve => window.setTimeout(resolve, wait));
                lastLookup = Date.now();
                return task();
            });
            lookupQueue = request.catch(() => {});
            return request;
        };

        const lookupAddress = (lat, lon, expectedSequence) => throttledLookup(async () => {
            const url = new URL('https://nominatim.openstreetmap.org/reverse');
            url.search = new URLSearchParams({ format: 'jsonv2', addressdetails: '1', lat: String(lat), lon: String(lon) });
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Adres servisi yanıt vermedi.');
            const result = await response.json();
            return expectedSequence === lookupSequence ? result : null;
        });

        const lookupQuery = query => throttledLookup(async () => {
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.search = new URLSearchParams({ format: 'jsonv2', addressdetails: '1', limit: '1', countrycodes: 'tr', q: query });
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Adres servisi yanıt vermedi.');
            return (await response.json())[0] ?? null;
        });

        const setValue = (root, selector, value) => {
            const node = root.querySelector(selector);
            if (node) node.textContent = value || '—';
        };

        const showCard = root => {
            root.querySelector('[data-location-card]').hidden = false;
            root.querySelector('[data-location-card-open]').hidden = true;
        };

        const presentAddress = (root, lat, lon, result) => {
            const address = result?.address ?? {};
            setValue(root, '[data-location-address]', result?.display_name || 'Adres bilgisi bulunamadı');
            setValue(root, '[data-location-district]', [address.city || address.town || address.village || address.county, address.state].filter(Boolean).join(' · '));
            setValue(root, '[data-location-neighborhood]', address.neighbourhood || address.suburb || address.quarter);
            setValue(root, '[data-location-street]', [address.road || address.pedestrian, address.house_number].filter(Boolean).join(' '));
            setValue(root, '[data-location-coordinates]', `${Number(lat).toFixed(6)}, ${Number(lon).toFixed(6)}`);
            const google = root.querySelector('[data-google-maps]');
            google.href = `https://www.google.com/maps?q=${encodeURIComponent(`${lat},${lon}`)}`;
            google.hidden = false;
        };

        const presentParcel = (root, result) => {
            if (!result?.found || !result.parcel) {
                setValue(root, '[data-location-parcel-status]', result?.message || 'Bu noktada parsel bulunamadı.');
                for (const field of ['province', 'district', 'neighborhood', 'block', 'parcel', 'area', 'quality', 'sheet', 'locality']) {
                    setValue(root, `[data-parcel-${field}]`, '—');
                }
                return;
            }
            setValue(root, '[data-location-parcel-status]', 'Kadastro bilgisi bulundu. Kamuya açık veridir; resmî işlem belgesi değildir.');
            for (const field of ['province', 'district', 'neighborhood', 'block', 'parcel', 'area', 'quality', 'sheet', 'locality']) {
                setValue(root, `[data-parcel-${field}]`, result.parcel[field]);
            }
            const cadastralDistrict = [result.parcel.province, result.parcel.district].filter(Boolean).join(' · ');
            if (cadastralDistrict) setValue(root, '[data-location-district]', cadastralDistrict);
            if (result.parcel.neighborhood) setValue(root, '[data-location-neighborhood]', result.parcel.neighborhood);
            if (result.geometry && activeMap) {
                selectedBoundary?.remove();
                selectedBoundary = L.geoJSON(result.geometry, {
                    interactive: false,
                    style: { color: '#f97316', weight: 3, fillColor: '#fb923c', fillOpacity: 0.18 },
                }).addTo(activeMap);
            }
        };

        const selectLocation = async (map, lat, lon, root) => {
            const sequence = ++lookupSequence;
            activeMap = map;
            map.setView([lat, lon], Math.max(map.getZoom(), 16));
            selectedMarker?.remove();
            selectedBoundary?.remove();
            selectedBoundary = null;
            selectedMarker = L.marker([lat, lon], { keyboard: true, title: 'Seçilen konum' }).addTo(map);
            showCard(root);
            setValue(root, '[data-location-address]', 'Adres sorgulanıyor…');
            setValue(root, '[data-location-district]', '—');
            setValue(root, '[data-location-neighborhood]', '—');
            setValue(root, '[data-location-street]', '—');
            setValue(root, '[data-location-coordinates]', `${lat.toFixed(6)}, ${lon.toFixed(6)}`);
            setValue(root, '[data-location-parcel-status]', 'Parsel sorgulanıyor…');
            for (const field of ['province', 'district', 'neighborhood', 'block', 'parcel', 'area', 'quality', 'sheet', 'locality']) {
                setValue(root, `[data-parcel-${field}]`, '—');
            }

            const parcelUrl = root.dataset.parcelUrl;
            const parcelRequest = fetch(`${parcelUrl}?${new URLSearchParams({ lat: String(lat), lon: String(lon) })}`, {
                headers: { Accept: 'application/json' },
            }).then(async response => {
                const body = await response.json().catch(() => null);
                if (!response.ok) throw new Error(body?.message || 'Kadastro servisi şu anda yanıt vermiyor.');
                return body;
            });

            const addressRequest = lookupAddress(lat, lon, sequence);
            const [addressResult, parcelResult] = await Promise.allSettled([addressRequest, parcelRequest]);
            if (sequence !== lookupSequence) return;
            if (addressResult.status === 'fulfilled') presentAddress(root, lat, lon, addressResult.value);
            else presentAddress(root, lat, lon, null);
            if (parcelResult.status === 'fulfilled') presentParcel(root, parcelResult.value);
            else setValue(root, '[data-location-parcel-status]', parcelResult.reason?.message || 'Kadastro sorgusu başarısız oldu.');
        };

        const attachMap = map => {
            if (!map || attachedMaps.has(map)) return;
            const root = document.querySelector('[data-location-tools]');
            const mapHost = map.getContainer?.().closest('[data-zyd-map]');
            if (!root?.isConnected || !mapHost || !root.parentElement?.contains(mapHost)) return;
            attachedMaps.add(map);
            activeMap = map;
            map.on('click', event => {
                const target = event.originalEvent?.target;
                if (target?.closest?.('.leaflet-marker-icon, .leaflet-control')) return;
                selectLocation(map, event.latlng.lat, event.latlng.lng, root);
            });
        };

        L.Map.addInitHook(function () { attachMap(this); });
        const originalMapFactory = L.map;
        if (!originalMapFactory.__zydLocationToolsWrapped) {
            const wrappedMapFactory = function (...args) {
                const map = originalMapFactory.apply(this, args);
                attachMap(map);
                return map;
            };
            wrappedMapFactory.__zydLocationToolsWrapped = true;
            L.map = wrappedMapFactory;
        }
        const attachExistingMaps = () => Object.values(L.Map._instances || {}).forEach(attachMap);
        attachExistingMaps();
        new MutationObserver(attachExistingMaps).observe(document.documentElement, { childList: true, subtree: true });

        document.addEventListener('submit', async event => {
            const form = event.target.closest('[data-address-search]');
            if (!form) return;
            event.preventDefault();
            const root = form.closest('[data-location-tools]');
            const input = form.elements.q;
            const button = form.querySelector('[type="submit"]');
            button.disabled = true;
            button.textContent = 'Aranıyor…';
            try {
                const result = await lookupQuery(input.value.trim());
                if (!result) throw new Error('Adres bulunamadı. Daha geniş bir adres deneyin.');
                const lat = Number(result.lat), lon = Number(result.lon);
                if (!activeMap || !Number.isFinite(lat) || !Number.isFinite(lon) || Math.abs(lat) > 90 || Math.abs(lon) > 180) throw new Error('Harita hazır değil.');
                await selectLocation(activeMap, lat, lon, root);
                form.hidden = true;
                root.querySelector('[data-location-open]').setAttribute('aria-expanded', 'false');
            } catch (error) {
                showCard(root);
                setValue(root, '[data-location-address]', error.message || 'Adres bulunamadı.');
            } finally {
                button.disabled = false;
                button.textContent = 'Git';
            }
        });

        document.addEventListener('click', event => {
            const root = event.target.closest('[data-location-tools]');
            if (!root) return;
            const toggle = event.target.closest('[data-location-open]');
            if (toggle) {
                const form = root.querySelector('[data-address-search]');
                form.hidden = !form.hidden;
                toggle.setAttribute('aria-expanded', String(!form.hidden));
                if (!form.hidden) root.querySelector('[name="q"]').focus();
            }
            if (event.target.closest('[data-location-card-open]')) showCard(root);
            const close = event.target.closest('[data-location-close]');
            if (!close) return;
            if (close.closest('[data-address-search]')) {
                root.querySelector('[data-address-search]').hidden = true;
                root.querySelector('[data-location-open]').setAttribute('aria-expanded', 'false');
            }
            if (close.closest('[data-location-card]')) {
                root.querySelector('[data-location-card]').hidden = true;
                root.querySelector('[data-location-card-open]').hidden = false;
            }
        });

        document.querySelectorAll('[data-voice-input]').forEach(button => {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;
            button.hidden = false;
            button.addEventListener('click', () => {
                const recognition = new SpeechRecognition();
                recognition.lang = 'tr-TR';
                recognition.onresult = result => { button.closest('form').elements.q.value = result.results[0][0].transcript; };
                recognition.start();
            });
        });

        const mapHost = document.querySelector('[data-zyd-map]');
        if (mapHost) {
            const closeObserver = new MutationObserver(() => {
                mapHost.querySelectorAll('.zyd-map-card button[aria-label="Proje bilgisini kapat"]').forEach(button => {
                    if (button.textContent.trim() !== '×') button.textContent = '×';
                    button.setAttribute('aria-label', 'Harita kaydı bilgi kartını kapat');
                });
            });
            closeObserver.observe(mapHost, { childList: true, subtree: true });
        }

        return true;
    };

    const watchLeaflet = () => {
        if (install()) return;
        const descriptor = Object.getOwnPropertyDescriptor(window, 'L');
        if (!descriptor || descriptor.configurable) {
            let current = descriptor?.get ? descriptor.get.call(window) : descriptor?.value;
            try {
                Object.defineProperty(window, 'L', {
                    configurable: true,
                    enumerable: descriptor?.enumerable ?? true,
                    get: () => current,
                    set: value => {
                        current = value;
                        if (value?.Map) install();
                    },
                });
            } catch {
                // A timed retry below handles a locked global.
            }
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install, { once: true });
    };

    watchLeaflet();
    let attempts = 0;
    const retry = window.setInterval(() => {
        if (install() || ++attempts > 80) window.clearInterval(retry);
    }, 100);
})();

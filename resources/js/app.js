import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-mobile-menu-button]');
    const menu = document.querySelector('[data-mobile-menu]');

    button?.addEventListener('click', () => {
        menu?.classList.toggle('hidden');
        button.setAttribute('aria-expanded', String(! menu?.classList.contains('hidden')));
    });

    document.querySelectorAll('[data-location-selects]').forEach((container) => {
        const citySelect = container.querySelector('[data-city-select]');
        const districtSelect = container.querySelector('[data-district-select]');
        const neighborhoodSelect = container.querySelector('[data-neighborhood-select]');

        if (! citySelect || ! districtSelect || ! neighborhoodSelect) return;

        const setPlaceholder = (select, text) => {
            select.disabled = true;
            select.innerHTML = `<option value="">${text}</option>`;
        };

        const loadNeighborhoods = async () => {
            const city = citySelect.value;
            const district = districtSelect.value;
            const selected = neighborhoodSelect.dataset.selected || '';

            setPlaceholder(neighborhoodSelect, district ? 'Mahalleler yükleniyor...' : 'Önce ilçe seçin');
            if (! city || ! district) return;

            try {
                const url = new URL(citySelect.dataset.neighborhoodsUrl, window.location.origin);
                url.searchParams.set('city', city);
                url.searchParams.set('district', district);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error('Mahalleler alınamadı.');

                const payload = await response.json();
                neighborhoodSelect.innerHTML = '<option value="">Mahalle / köy seçin</option>';
                Object.entries(payload.data).forEach(([value, label]) => {
                    neighborhoodSelect.add(new Option(label, value, false, value === selected));
                });
                neighborhoodSelect.disabled = false;
                neighborhoodSelect.dataset.selected = '';
            } catch (error) {
                setPlaceholder(neighborhoodSelect, 'Mahalleler yüklenemedi');
            }
        };

        const loadDistricts = async () => {
            const city = citySelect.value;
            const selected = districtSelect.dataset.selected || '';

            setPlaceholder(districtSelect, city ? 'İlçeler yükleniyor...' : 'Önce il seçin');
            setPlaceholder(neighborhoodSelect, 'Önce ilçe seçin');
            if (! city) return;

            try {
                const url = new URL(citySelect.dataset.districtsUrl, window.location.origin);
                url.searchParams.set('city', city);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error('İlçeler alınamadı.');

                const payload = await response.json();
                districtSelect.innerHTML = '<option value="">İlçe seçin</option>';
                Object.entries(payload.data).forEach(([value, label]) => {
                    districtSelect.add(new Option(label, value, false, value === selected));
                });
                districtSelect.disabled = false;
                districtSelect.dataset.selected = '';
                await loadNeighborhoods();
            } catch (error) {
                setPlaceholder(districtSelect, 'İlçeler yüklenemedi');
            }
        };

        citySelect.addEventListener('change', () => {
            districtSelect.dataset.selected = '';
            neighborhoodSelect.dataset.selected = '';
            loadDistricts();
        });

        districtSelect.addEventListener('change', () => {
            neighborhoodSelect.dataset.selected = '';
            loadNeighborhoods();
        });

        loadDistricts();
    });
});

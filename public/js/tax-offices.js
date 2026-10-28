(() => {
    const normalize = value => value.toLocaleLowerCase('tr-TR').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ı/g, 'i');
    document.querySelectorAll('[data-tax-offices]').forEach(root => {
        const city = root.querySelector('[data-tax-city]');
        const office = root.querySelector('[data-tax-office]');
        const citySearch = root.querySelector('[data-tax-city-search]');
        const officeSearch = root.querySelector('[data-tax-office-search]');
        const status = root.querySelector('[data-tax-status]');
        const cities = Array.from(city.options).slice(1).map(o => ({code: o.value, name: o.textContent}));
        const offices = JSON.parse(root.querySelector('[data-tax-directory]').textContent);
        const render = (select, rows, query, selected, placeholder) => {
            select.replaceChildren(new Option(placeholder, ''));
            rows.filter(row => row.code === selected || normalize(row.name).includes(normalize(query))).forEach(row => {
                select.add(new Option(row.name, row.code, false, row.code === selected));
            });
            select.value = selected;
        };
        const updateOffices = (selected = '') => {
            const rows = offices.filter(row => row.city_code === city.value).sort((a, b) => a.name.localeCompare(b.name, 'tr'));
            render(office, rows, officeSearch.value, selected, city.value ? 'Vergi dairesi seçin' : 'Önce il seçin');
            office.disabled = officeSearch.disabled = !city.value;
            status.textContent = city.value ? `${rows.length} vergi dairesi listelendi. GİB kaynak tarihi: 18.06.2026.` : 'Önce vergi dairesi ilini seçin.';
        };
        citySearch.addEventListener('input', () => render(city, cities, citySearch.value, city.value, 'İl seçin'));
        city.addEventListener('change', () => {
            officeSearch.value = '';
            updateOffices();
        });
        officeSearch.addEventListener('input', () => updateOffices(office.value));
        updateOffices(office.dataset.selected || '');
    });
})();

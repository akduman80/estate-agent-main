(() => {
    const panel = document.getElementById('documents-panel');
    if (!panel || panel.dataset.ready) return;
    panel.dataset.ready = 'true';
    const search = panel.querySelector('input');
    const links = [...panel.querySelectorAll('.documents-link')];
    const viewport = window.visualViewport;
    let trigger, saved;
    const fold = value => value.normalize('NFC').toLocaleLowerCase('tr-TR');
    function filter() {
        const query = fold(search.value.trim());
        let count = 0;
        links.forEach(link => { link.hidden = !fold(link.textContent).includes(query); if (!link.hidden) count++; });
        panel.querySelector('[data-documents-count]').textContent = query ? `${count} / ${links.length} belge` : `${links.length} belge`;
        panel.querySelector('.documents-empty').hidden = count !== 0;
        panel.querySelector('.documents-list').scrollTop = 0;
    }
    function position() {
        if (!panel.open) return;
        const width = viewport?.width || innerWidth, height = viewport?.height || innerHeight;
        const x = viewport?.offsetLeft || 0, y = viewport?.offsetTop || 0;
        const mobile = innerWidth < 1024;
        const full = innerWidth < 600 || (mobile && innerHeight <= 500);
        let top, left, available;
        if (mobile) {
            available = height - (full ? 0 : 32);
            top = y + (full ? 0 : 16);
            left = x + (full ? 0 : Math.max(16, (width - Math.min(560, width - 32)) / 2));
        } else {
            const rect = trigger.getBoundingClientRect();
            const below = y + height - rect.bottom - 20;
            const above = rect.top - y - 20;
            available = Math.max(below, above);
            if (available < 220) { top = y + 12; available = height - 24; }
            else top = below >= above ? rect.bottom + 8 : rect.top - 8 - Math.min(panel.scrollHeight, available);
            left = Math.max(x + 12, Math.min(rect.right - 340, x + width - 352));
        }
        panel.style.left = `${left}px`; panel.style.top = `${top}px`;
        panel.style.maxHeight = `${Math.max(0, available)}px`;
        panel.style.height = mobile ? `${available}px` : 'auto';
    }
    document.querySelectorAll('[data-documents-open]').forEach(button => button.addEventListener('click', () => {
        trigger = button; search.value = ''; filter();
        saved = {overflow: document.body.style.overflow, position: document.body.style.position, top: document.body.style.top, width: document.body.style.width, x: scrollX, y: scrollY};
        // Lock all presentations so resizing an open panel cannot unlock the page.
        Object.assign(document.body.style, {overflow: 'hidden', position: 'fixed', top: `-${saved.y}px`, width: '100%'});
        panel.showModal(); button.setAttribute('aria-expanded', 'true'); position(); search.focus({preventScroll:true});
    }));
    panel.querySelector('[data-documents-close]').addEventListener('click', () => panel.close());
    panel.addEventListener('click', event => { if (event.target === panel) { const r = panel.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) panel.close(); } });
    panel.addEventListener('close', () => {
        if (saved) { const {x,y,...styles} = saved; Object.assign(document.body.style, styles); window.scrollTo(x,y); saved = null; }
        trigger?.setAttribute('aria-expanded', 'false'); trigger?.focus({preventScroll:true});
    });
    search.addEventListener('input', filter);
    panel.querySelector('[data-documents-clear]').addEventListener('click', () => { search.value = ''; filter(); search.focus(); });
    window.addEventListener('resize', position); viewport?.addEventListener('resize', position); viewport?.addEventListener('scroll', position);
})();

(() => {
    document.querySelectorAll('[data-listing-share]').forEach((card) => {
        const { title, url } = card.dataset;
        const dialog = card.querySelector('[data-share-dialog]');
        const input = card.querySelector('[data-share-link]');
        const status = (text) => card.querySelectorAll('[data-share-status]').forEach((item) => { item.textContent = text; });
        const fallback = () => {
            if (typeof dialog.showModal === 'function') {
                if (!dialog.open) dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }
            input.focus();
            input.select();
        };
        card.querySelectorAll('[data-share-native]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (typeof navigator.share !== 'function') { fallback(); return; }
                try { await navigator.share({ title, url }); }
                catch (error) { if (error.name !== 'AbortError') fallback(); }
            });
        });
        card.querySelectorAll('[data-share-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(url);
                    status('Bağlantı kopyalandı. İstediğiniz uygulamaya yapıştırabilirsiniz.');
                } catch {
                    fallback();
                    status('Bağlantıyı seçtik. Basılı tutarak veya Ctrl+C ile kopyalayabilirsiniz.');
                }
            });
        });
        card.querySelector('[data-share-close]').addEventListener('click', () => {
            if (typeof dialog.close === 'function') dialog.close();
            else dialog.removeAttribute('open');
        });
    });
})();

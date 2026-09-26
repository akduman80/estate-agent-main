(() => {
    if (window.zydPwaLoaded) return;
    window.zydPwaLoaded = true;
    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/sw.js').catch(console.error);
    }
    const standalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    let promptEvent;
    let banner;
    const remove = () => { banner?.remove(); banner = null; };
    const show = (ios = false) => {
        if (standalone() || banner) return;
        const kind = window.zydPwaKind || (location.pathname === '/admin' || location.pathname.startsWith('/admin/') ? 'admin' : 'site');
        try { if (sessionStorage.getItem(`pwa-dismissed-${kind}`)) return; } catch {}
        banner = document.createElement('aside');
        banner.setAttribute('aria-label', 'Uygulamayı yükle');
        banner.style.cssText = 'position:fixed;bottom:calc(90px + env(safe-area-inset-bottom, 0px));right:16px;left:16px;margin-left:auto;max-width:340px;z-index:9999;padding:16px;background:#172554;color:white;border-radius:14px;box-shadow:0 4px 24px #0003;font:14px/1.5 system-ui';
        const message = document.createElement('p');
        message.style.cssText = 'margin:0 0 12px';
        const appName = kind === 'projects' ? 'ZYDEMLAK Projeler’i' : (kind === 'admin' ? 'Yönetim panelini' : 'ZYDEMLAK’ı');
        message.textContent = ios ? `${appName} yüklemek için Safari paylaş menüsünden “Ana Ekrana Ekle”yi seçin.` : `${appName} telefonunuza uygulama olarak ekleyin.`;
        banner.append(message);
        const button = (label, action) => {
            const element = document.createElement('button');
            element.type = 'button';
            element.textContent = label;
            element.style.cssText = 'padding:8px 12px;margin-right:8px;border:1px solid #ffffff80;border-radius:8px;background:transparent;color:white;cursor:pointer;font:inherit';
            element.addEventListener('click', action);
            banner.append(element);
            return element;
        };
        if (!ios) button('Uygulamayı yükle', async (event) => {
            if (!promptEvent) return;
            event.currentTarget.disabled = true;
            const currentPrompt = promptEvent;
            promptEvent = null;
            try { await currentPrompt.prompt(); await currentPrompt.userChoice; } finally { remove(); }
        });
        button('Kapat', () => {
            try { sessionStorage.setItem(`pwa-dismissed-${kind}`, '1'); } catch {}
            remove();
        });
        document.body.append(banner);
    };
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        promptEvent = event;
        show();
    });
    window.addEventListener('appinstalled', remove);
    window.matchMedia('(display-mode: standalone)').addEventListener('change', () => { if (standalone()) remove(); });
    const ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios) show(true);
})();

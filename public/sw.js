// Network-only: never store listings, authenticated pages or API responses.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate' || event.request.method !== 'GET') return;
    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bağlantı yok · ZYDEMLAK</title><body style="font:18px/1.6 system-ui;padding:32px;max-width:480px;margin:auto"><h1>İnternet bağlantısı yok</h1><p>ZYDEMLAK ve yönetim panelini kullanmak için internete bağlanıp yeniden deneyin.</p><button onclick="location.reload()" style="padding:12px 20px;font:inherit">Yeniden dene</button></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' } },
    )));
});

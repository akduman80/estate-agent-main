@once
<link rel="stylesheet" href="{{ asset('zyd-map/zyd-project-map.css') }}?v={{ file_exists(public_path('zyd-map/zyd-project-map.css')) ? filemtime(public_path('zyd-map/zyd-project-map.css')) : 1 }}">
<script src="{{ asset('zyd-map/zyd-project-map.js') }}?v={{ file_exists(public_path('zyd-map/zyd-project-map.js')) ? filemtime(public_path('zyd-map/zyd-project-map.js')) : 1 }}"></script>
<script>
(() => {
    const maps = new Map();
    function mountMaps() {
        for (const [host, api] of maps) {
            if (!host.isConnected) { api.destroy(); maps.delete(host); }
        }
        document.querySelectorAll('[data-zyd-map]').forEach(host => {
            if (maps.has(host)) return;
            const config = JSON.parse(host.dataset.config || '{}');
            maps.set(host, window.ZydProjectMap.mount(host, config));
        });
    }
    document.addEventListener('DOMContentLoaded', mountMaps);
    document.addEventListener('livewire:navigated', mountMaps);
    new MutationObserver(mountMaps).observe(document.documentElement, { childList: true, subtree: true });
    mountMaps();
})();
</script>
@endonce

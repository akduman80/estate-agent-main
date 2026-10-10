@once
@include('partials.zyd-map-assets')
<style>
[data-project-location-picker][data-expanded="true"]{position:fixed;inset:0;z-index:9999;background:#051b2e;padding:16px;overflow:auto}
[data-project-location-picker][data-expanded="true"] [x-ref="map"]{height:auto!important}
</style>
<script>
window.projectLocationPicker = config => ({
    map: null, latitude: config.initialLatitude === null ? null : Number(config.initialLatitude),
    longitude: config.initialLongitude === null ? null : Number(config.initialLongitude),
    locating: false, locationError: '', expanded: false,
    init() {
        this.map = window.ZydProjectMap.mount(this.$refs.map, {
            initial: this.latitude !== null && this.longitude !== null ? { lat: this.latitude, lng: this.longitude } : null,
            onPick: (lat, lng) => this.placeMarker(lat, lng, true, false),
        });
    },
    destroy() { this.map?.destroy(); this.map = null; },
    toggleExpanded() { this.expanded = !this.expanded; setTimeout(() => this.map?.invalidateSize(), 150); },
    placeMarker(lat, lng, updateState = true, updateMap = true) {
        this.latitude = Number(Number(lat).toFixed(7)); this.longitude = Number(Number(lng).toFixed(7));
        if (updateMap) this.map?.setLocation(this.latitude, this.longitude);
        if (updateState) {
            this.$wire.set(config.latitudeStatePath, this.latitude, false);
            this.$wire.set(config.longitudeStatePath, this.longitude, false);
        }
    },
    useCurrentLocation() {
        if (!navigator.geolocation) { this.locationError = 'Konum kullanılamıyor.'; return; }
        this.locating = true;
        navigator.geolocation.getCurrentPosition(p => {
            this.placeMarker(p.coords.latitude, p.coords.longitude); this.locating = false;
        }, () => { this.locationError = 'Konum iznini kontrol edin.'; this.locating = false; }, { enableHighAccuracy: true, timeout: 15000 });
    },
});
</script>
@endonce

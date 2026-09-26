@if(request()->routeIs([
    'filament.admin.resources.projects.*',
    'filament.projects.resources.projects.*',
]))
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        [data-project-location-picker][data-expanded="true"] {
            background: rgb(249 250 251);
            inset: 0;
            overflow: auto;
            padding: 1rem;
            position: fixed;
            z-index: 9999;
        }

        [data-project-location-picker][data-expanded="true"] [x-ref="map"] {
            height: calc(100dvh - 11rem) !important;
            min-height: 28rem !important;
        }

        [data-project-location-picker][data-expanded="true"] [data-map-expand-button] {
            color: rgb(17 24 39) !important;
        }

        [data-project-location-picker][data-expanded="true"] p {
            color: rgb(17 24 39) !important;
        }
    </style>
    <script>
        window.projectLocationPicker = (config) => ({
            map: null,
            marker: null,
            latitude: config.initialLatitude === null ? null : Number(config.initialLatitude),
            longitude: config.initialLongitude === null ? null : Number(config.initialLongitude),
            locating: false,
            locationError: '',
            expanded: false,

            init() {
                const hasLocation = Number.isFinite(this.latitude) && Number.isFinite(this.longitude);
                const center = hasLocation
                    ? [this.latitude, this.longitude]
                    : [config.defaultLatitude, config.defaultLongitude];

                this.map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView(center, hasLocation ? 16 : 13);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(this.map);

                if (hasLocation) this.placeMarker(this.latitude, this.longitude, false);

                this.map.on('click', (event) => this.placeMarker(event.latlng.lat, event.latlng.lng));
                window.setTimeout(() => this.map?.invalidateSize(), 150);
            },

            destroy() {
                this.map?.remove();
                this.map = null;
                this.marker = null;
            },

            toggleExpanded() {
                this.expanded = ! this.expanded;
                window.setTimeout(() => this.map?.invalidateSize(), 150);
            },

            placeMarker(latitude, longitude, updateState = true) {
                this.latitude = Number(Number(latitude).toFixed(7));
                this.longitude = Number(Number(longitude).toFixed(7));
                this.locationError = '';

                if (! this.marker) {
                    this.marker = L.marker([this.latitude, this.longitude], { draggable: true }).addTo(this.map);
                    this.marker.on('dragend', () => {
                        const position = this.marker.getLatLng();
                        this.placeMarker(position.lat, position.lng);
                    });
                } else {
                    this.marker.setLatLng([this.latitude, this.longitude]);
                }

                if (updateState) {
                    this.$wire.set(config.latitudeStatePath, this.latitude, false);
                    this.$wire.set(config.longitudeStatePath, this.longitude, false);
                }
            },

            useCurrentLocation() {
                if (! navigator.geolocation) {
                    this.locationError = 'Tarayıcınız konum özelliğini desteklemiyor.';
                    return;
                }

                this.locating = true;
                this.locationError = '';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.placeMarker(position.coords.latitude, position.coords.longitude);
                        this.map.setView([this.latitude, this.longitude], 17);
                        this.locating = false;
                    },
                    () => {
                        this.locationError = 'Konum alınamadı. Haritadan bir noktaya dokunabilirsiniz.';
                        this.locating = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000 },
                );
            },
        });
    </script>
@endif

<div wire:ignore x-data="{
    map: null, marker: null, error: '', expanded: false,
    async init() {
        try {
            if (!window.L) {
                if (!window.zydListingLeaflet) window.zydListingLeaflet = new Promise((resolve,reject) => {
                    const css = document.createElement('link'); css.rel='stylesheet'; css.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'; document.head.append(css);
                    const script = document.createElement('script'); script.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'; script.onload=resolve; script.onerror=reject; document.head.append(script);
                });
                await window.zydListingLeaflet;
            }
            this.map=L.map(this.$refs.map).setView([39,35],6);
            const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap',maxZoom:19}).addTo(this.map);
            tiles.on('tileerror',()=>{this.error='Harita yüklenemedi. Koordinatları yukarıdaki alanlara girebilirsiniz; form verileri korunur.'});
            const lat=this.$wire.get('data.form_details.latitude'), lng=this.$wire.get('data.form_details.longitude');
            if (lat!==null && lat!==undefined && lat!=='' && lng!==null && lng!==undefined && lng!=='') this.place(Number(lat),Number(lng),false);
            this.map.on('click',e=>this.place(e.latlng.lat,e.latlng.lng));
        } catch { this.error='Harita hizmetine ulaşılamadı. Enlem ve boylam alanlarını kullanabilirsiniz.'; }
    },
    place(lat,lng,write=true) {
        if(!this.map || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
        if(!this.marker){this.marker=L.marker([lat,lng],{draggable:true}).addTo(this.map);this.marker.on('dragend',()=>{const p=this.marker.getLatLng();this.place(p.lat,p.lng)})}else this.marker.setLatLng([lat,lng]);
        this.map.panTo([lat,lng]);
        if(write){this.$wire.set('data.form_details.latitude',Number(lat.toFixed(7)),false);this.$wire.set('data.form_details.longitude',Number(lng.toFixed(7)),false)}
    },
    destroy(){this.map?.remove()}
}" x-bind:style="expanded ? 'position:fixed;inset:0;z-index:9999;background:white;padding:16px;overflow:auto' : ''" x-on:keydown.escape.window="expanded=false;setTimeout(()=>map?.invalidateSize(),100)">
    <button type="button" class="mb-2 rounded-lg border px-3 py-2 text-sm" x-on:click="expanded=!expanded;setTimeout(()=>map?.invalidateSize(),100)" x-text="expanded?'Haritayı küçült':'Haritayı büyüt'"></button>
    <div x-ref="map" style="height:340px;width:100%;max-width:100%;z-index:0" role="region" aria-label="İlan konumu haritası"></div>
    <p class="mt-2 text-sm">Haritaya dokunun veya işaretçiyi sürükleyin. Klavyeyle kullanım için yukarıdaki enlem/boylam alanlarını kullanın. Konumun seçtiğiniz il ve ilçeyle eşleştiğini kontrol edin.</p>
    <p x-text="error" role="alert" class="mt-2 text-sm text-danger-600"></p>
</div>

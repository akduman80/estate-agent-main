(async () => {
    const root=document.querySelector('[data-property-map]'); if(!root)return;
    const status=root.querySelector('[data-map-status]'), form=root.querySelector('form'), cards=root.querySelector('[data-map-cards]'), more=root.querySelector('[data-map-more]');
    const loadStyle=url=>{const el=document.createElement('link');el.rel='stylesheet';el.href=url;document.head.append(el);};
    const loadScript=url=>new Promise((resolve,reject)=>{const el=document.createElement('script');el.src=url;el.onload=resolve;el.onerror=reject;document.head.append(el);});
    try {
        loadStyle('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        loadStyle('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css');loadStyle('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css');
        if(!window.L)await loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
        if(!L.markerClusterGroup)await loadScript('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js');
        const map=L.map(root.querySelector('[data-map-canvas]')).setView([39,35],6);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> katkıcıları'}).addTo(map);
        const cluster=L.markerClusterGroup({chunkedLoading:true}).addTo(map);let page=1,controller,timer;
        const card=record=>{const el=document.createElement('a');el.href=record.url;el.className='block min-w-48 max-w-64 rounded-lg border bg-white p-3 text-blue-950';
            if(record.photo){const image=document.createElement('img');image.src=record.photo;image.alt='';image.loading='lazy';image.style.cssText='width:160px;height:100px;object-fit:cover';el.append(image);}
            const title=document.createElement('strong'),price=document.createElement('p');title.textContent=record.title;title.style.overflowWrap='anywhere';price.textContent=record.price;el.append(title,price);return el;};
        async function search(append=false){
            controller?.abort();controller=new AbortController();const active=controller;
            if(!append){page=1;cluster.clearLayers();cards.replaceChildren();}more.hidden=true;status.textContent='İlanlar yükleniyor…';
            const params=new URLSearchParams(new FormData(form)), bounds=map.getBounds();
            root.querySelector('[data-map-list]').href='/ilanlar?'+params.toString();
            params.set('south',Math.max(-90,bounds.getSouth()));params.set('north',Math.min(90,bounds.getNorth()));params.set('west',Math.max(-180,bounds.getWest()));params.set('east',Math.min(180,bounds.getEast()));params.set('page',page);
            try{const response=await fetch(root.dataset.endpoint+'?'+params,{signal:active.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error();const result=await response.json();if(controller!==active)return;
                result.data.forEach(record=>{const marker=L.marker([record.latitude,record.longitude]);marker.bindPopup(()=>card(record));cluster.addLayer(marker);cards.append(card(record));});
                more.hidden=!result.more;status.textContent=cards.children.length+' ilan gösteriliyor.'+(result.more?' Bu bölgede başka ilanlar var.':'');
            }catch(error){if(error.name!=='AbortError')status.textContent='Harita ilanları yüklenemedi. Liste görünümünü kullanabilir veya yeniden filtreleyebilirsiniz.';}
        }
        form.onsubmit=e=>{e.preventDefault();search();};map.on('moveend',()=>{clearTimeout(timer);timer=setTimeout(()=>search(),400);});more.onclick=()=>{page++;search(true);};
        root.querySelector('[data-map-fullscreen]').onclick=async()=>{const stage=root.querySelector('[data-map-stage]');try{if(document.fullscreenElement)await document.exitFullscreen();else await stage.requestFullscreen();}catch{stage.classList.toggle('property-map-expanded');}setTimeout(()=>map.invalidateSize(),100);};
        document.addEventListener('fullscreenchange',()=>setTimeout(()=>map.invalidateSize(),100));search();
    }catch{status.textContent='Harita hizmeti açılamadı. Liste görünümünde arama yapabilirsiniz.';}
})();

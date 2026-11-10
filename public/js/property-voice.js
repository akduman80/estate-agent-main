(() => {
    document.querySelectorAll('[data-voice-search]').forEach(root => {
        const dialog = root.querySelector('dialog'), text = root.querySelector('[data-voice-text]'), status = root.querySelector('[data-voice-status]'), link = root.querySelector('[data-voice-results]');
        let recognition;
        root.querySelector('[data-voice-start]').onclick = () => dialog.showModal();
        root.querySelector('[data-voice-close]').onclick = () => dialog.close();
        dialog.addEventListener('close', () => recognition?.abort());
        text.oninput = () => { link.hidden = true; };
        root.querySelector('[data-voice-record]').onclick = () => {
            const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!Speech) { status.textContent = 'Ses tanıma desteklenmiyor. Aramanızı aşağıya yazabilirsiniz.'; return; }
            recognition?.abort(); recognition = new Speech(); recognition.lang = 'tr-TR'; recognition.continuous = false;
            recognition.onresult = e => { if (!dialog.open) return; text.value = e.results[0][0].transcript; link.hidden = true; status.textContent = 'Metni kontrol edip düzenleyebilirsiniz.'; };
            recognition.onerror = () => { status.textContent = 'Mikrofon kullanılamadı. Aramanızı yazabilirsiniz.'; };
            recognition.onend = () => { if (status.textContent === 'Dinleniyor…') status.textContent = 'Dinleme sona erdi. Yeniden deneyebilir veya yazabilirsiniz.'; };
            try { recognition.start(); status.textContent = 'Dinleniyor…'; } catch { status.textContent = 'Mikrofon başlatılamadı; yazılı arama kullanılabilir.'; }
        };
        root.querySelector('[data-voice-apply]').onclick = async e => {
            if (!text.value.trim()) { status.textContent = 'Önce bir arama yazın veya söyleyin.'; return; }
            const button = e.currentTarget; button.disabled = true; link.hidden = true;
            try {
                const response = await fetch(root.dataset.endpoint, {method: 'POST', headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({text: text.value})});
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!Object.keys(result.filters).length || result.ambiguous || result.unsupported) { status.textContent = 'Bazı koşullar kesin anlaşılamadı. İl, ilçe, fiyat ve kategoriyi daha açık yazın veya normal arama filtrelerini kullanın.'; return; }
                status.textContent = 'Algılanan filtreler: ' + Object.entries(result.filters).map(([key,value]) => ({city:'İl',district:'İlçe',neighborhood:'Mahalle',type:'Durum',category:'Kategori',min_price:'En az fiyat',max_price:'En çok fiyat',min_area:'En az m²',max_area:'En çok m²',sort:'Sıralama'}[key] + ': ' + value)).join(' · ');
                link.href = result.url; link.hidden = false;
            } catch { status.textContent = 'Arama şu anda hazırlanamadı. Normal arama kutusunu kullanabilirsiniz.'; }
            finally { button.disabled = false; }
        };
    });
})();

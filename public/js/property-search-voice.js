(() => {
    document.querySelectorAll('form[data-search-voice]').forEach(form => {
        const input = form.querySelector('input[name="q"]');
        const button = form.querySelector('[data-search-voice-button]');
        const status = form.querySelector('[data-search-voice-status]');
        if (!input || !button || !status) return;

        let recognition;
        let active = false;

        const announce = message => {
            status.textContent = message;
            status.hidden = false;
        };
        const stopState = () => {
            active = false;
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', 'Arama alanını sesle doldur');
            button.title = 'Arama alanını sesle doldur';
        };

        button.addEventListener('click', () => {
            if (active) {
                recognition?.stop();
                announce('Dinleme durduruluyor…');
                return;
            }

            const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!Speech) {
                announce('Tarayıcınız ses tanımayı desteklemiyor. Aramanızı yazarak sürdürebilirsiniz.');
                return;
            }

            if (!window.confirm('Ses, tarayıcınızın konuşma tanıma hizmetine iletilebilir. Site ses kaydı oluşturmaz. Tanınan metin arama alanına yerleştirilir. Devam edilsin mi?')) return;

            recognition?.abort();
            recognition = new Speech();
            recognition.lang = 'tr-TR';
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.onstart = () => announce('Dinleniyor…');
            recognition.onresult = event => {
                const transcript = String(event.results?.[0]?.[0]?.transcript ?? '').trim();
                if (!transcript) {
                    announce('Sesli arama metni algılanmadı. Yeniden deneyebilir veya yazabilirsiniz.');
                    return;
                }

                input.value = transcript;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
                announce('Arama alanı dolduruldu. Metni kontrol edip İlan Ara düğmesine basın.');
            };
            recognition.onerror = event => {
                stopState();
                announce(event.error === 'not-allowed'
                    ? 'Mikrofon izni verilmedi. Aramanızı yazarak sürdürebilirsiniz.'
                    : 'Mikrofon kullanılamadı. Aramanızı yazarak sürdürebilirsiniz.');
            };
            recognition.onend = () => {
                stopState();
                if (status.textContent === 'Dinleniyor…') announce('Dinleme sona erdi. Yeniden deneyebilir veya yazabilirsiniz.');
            };

            active = true;
            button.setAttribute('aria-pressed', 'true');
            button.setAttribute('aria-label', 'Sesli aramayı durdur');
            button.title = 'Sesli aramayı durdur';
            announce('Dinleniyor…');
            try {
                recognition.start();
            } catch {
                stopState();
                announce('Mikrofon başlatılamadı. Aramanızı yazarak sürdürebilirsiniz.');
            }
        });
    });
})();

import {callingCode, countries, digits, normalizeSpeech, spokenDigits, validateField} from './field-rules.js';

let nextId = 0;
const eligible = input => input.matches('textarea,input:not([type]),input[type=text],input[type=tel],input[type=email],input[type=date],input[type=number]') && !input.readOnly && !input.disabled;
export function fieldKind(input) {
    if (input.dataset.fieldKind) return input.dataset.fieldKind;
    if (input.type === 'tel') return 'phone';
    if (input.type === 'email') return 'email';
    if (input.type === 'date') return 'date';
    if (input.type === 'number') return 'amount';
    const labelText = input.closest('label') ? [...input.closest('label').childNodes].filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent).join(' ') : '';
    const name = `${input.name} ${input.getAttribute('aria-label') || ''} ${labelText}`.toLocaleLowerCase('tr-TR');
    if (/identity|tckn|kimlik/.test(name)) return 'identity';
    if (/phone|telefon/.test(name)) return 'phone';
    if (/email|e-posta/.test(name)) return 'email';
    if (/date|tarih/.test(name)) return 'date';
    if (/amount|tutar|bedel|kapora|depozito/.test(name)) return 'amount';
    if (/adres/.test(name)) return 'address';
    return 'text';
}

export function createFieldControl(kind = 'text', multiline = false) {
    const input = document.createElement(multiline || ['address', 'description', 'paragraph', 'article'].includes(kind) ? 'textarea' : 'input');
    if (input.tagName === 'INPUT') input.type = 'text';
    input.dataset.fieldKind = kind;
    return input;
}

export function readField(input) {
    return {raw: input.value, ...validateField(input.value, fieldKind(input), {country: input.dataset.country, currency: input.dataset.currency}), country: input.dataset.country || 'TR', currency: input.dataset.currency || 'TRY'};
}

export class SmartFields {
    constructor(root, status) {
        this.root = root; this.status = status; this.controls = new Map(); this.active = null; this.receiving = false;
        this.Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.supported = !!this.Recognition && window.isSecureContext;
        this.root.addEventListener('input', event => {
            if (!eligible(event.target)) return;
            if (this.active && !this.receiving) this.cancel('Elle giriş yapıldığı için sesli giriş durduruldu.');
            this.validate(event.target);
        }, true);
        this.root.addEventListener('focusout', event => {
            if (eligible(event.target)) this.format(event.target);
        });
        this.root.addEventListener('focusin', event => {
            if (this.active && eligible(event.target) && event.target !== this.active.input) this.cancel('Alan değiştirildi; sesli giriş durduruldu.');
        });
        this.root.addEventListener('paste', event => {
            const input = event.target;
            if (!eligible(input) || fieldKind(input) !== 'identity' || !event.clipboardData) return;
            event.preventDefault();
            const pasted = digits(event.clipboardData.getData('text')).replace(/\D/g, '');
            const combined = input.value.slice(0, input.selectionStart ?? 0) + pasted + input.value.slice(input.selectionEnd ?? input.value.length);
            if (combined.length > 11) { this.message('T.C. kimlik numarası en fazla 11 rakam olabilir. Yapıştırılan değer aktarılmadı.'); return; }
            input.value = combined;
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
        this.observer = new MutationObserver(() => this.refresh());
        this.observer.observe(root, {childList: true, subtree: true, attributes: true, attributeFilter: ['readonly', 'disabled', 'type', 'data-field-kind']});
        document.addEventListener('visibilitychange', () => { if (document.hidden) this.cancel(); });
        this.refresh();
        this.message(this.supported ? 'Sesli giriş için alanın mikrofonuna dokunun. Klavye her zaman kullanılabilir.' : 'Bu tarayıcıda sesli giriş desteklenmiyor. Klavyeyle devam edebilirsiniz.');
    }
    message(text) { this.status.textContent = text; }
    refresh() {
        for (const [input, control] of this.controls) {
            if (!this.root.contains(input) || !eligible(input)) {
                if (this.active?.input === input) this.cancel();
                control.button?.remove(); control.error.remove(); control.option?.remove();
                if (this.root.contains(input) && input.parentElement?.classList.contains('dw-input-group')) input.parentElement.replaceWith(input);
                this.controls.delete(input);
            }
        }
        for (const input of this.root.querySelectorAll('input,textarea')) {
            if (!eligible(input) || this.controls.has(input)) continue;
            const label = input.closest('label');
            input.id ||= `dw-smart-${++nextId}`;
            if (label) label.htmlFor = input.id;
            const labelText = input.getAttribute('aria-label') || (label ? [...label.childNodes].filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent).join(' ').trim() : input.name) || 'Belge alanı';
            const kind = fieldKind(input);
            input.classList.add('dw-smart-input');
            if (kind === 'identity') { input.maxLength = 11; input.inputMode = 'numeric'; input.autocomplete = 'off'; }
            if (kind === 'phone') { input.inputMode = 'tel'; input.autocomplete = 'off'; }
            if (kind === 'email') input.inputMode = 'email';
            if (kind === 'amount') input.inputMode = 'decimal';
            if (kind === 'date') input.placeholder ||= 'GG.AA.YYYY';
            const group = document.createElement('span'); group.className = 'dw-input-group';
            input.before(group); group.append(input);
            const error = document.createElement('span'); error.className = 'dw-field-error'; error.id = `${input.id}-error`; error.setAttribute('aria-live', 'polite');
            input.setAttribute('aria-describedby', `${input.getAttribute('aria-describedby') || ''} ${error.id}`.trim());
            const mic = document.createElement('button'); mic.type = 'button'; mic.className = 'dw-mic';
            mic.setAttribute('aria-label', `${labelText} — Türkçe sesli giriş`); mic.setAttribute('aria-pressed', 'false');
            mic.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3m-4 0h8"/></svg>';
            mic.disabled = !this.supported;
            mic.title = this.supported ? `${labelText}: sesli giriş` : 'Sesli giriş bu tarayıcıda desteklenmiyor';
            mic.addEventListener('click', event => { if (event.isTrusted) this.listen(input); }); group.append(mic); group.after(error);
            const control = {button: mic, error, label: labelText}; this.controls.set(input, control);
            if (['phone', 'amount'].includes(kind)) {
                const select = document.createElement('select'); select.className = 'dw-field-option';
                select.setAttribute('aria-label', `${labelText} — ${kind === 'phone' ? 'ülke' : 'para birimi'}`);
                const names = typeof Intl.DisplayNames === 'function' ? new Intl.DisplayNames(['tr'], {type: 'region'}) : null;
                const choices = kind === 'phone' ? countries : (typeof Intl.supportedValuesOf === 'function' ? Intl.supportedValuesOf('currency') : ['TRY', 'USD', 'EUR', 'GBP']);
                for (const choice of choices) {
                    const option = document.createElement('option'); option.value = choice;
                    option.textContent = kind === 'phone' ? `${names?.of(choice) || choice} (+${callingCode(choice)})` : choice;
                    select.append(option);
                }
                const key = kind === 'phone' ? 'country' : 'currency'; input.dataset[key] ||= kind === 'phone' ? 'TR' : 'TRY'; select.value = input.dataset[key];
                select.addEventListener('change', () => {
                    this.cancel();
                    const choice = select.value;
                    const previous = kind === 'phone' ? this.validate(input) : null;
                    // Parse the existing value before changing country; preserve the user's new selection.
                    input.dataset[key] = choice;
                    if (kind === 'phone' && previous?.national) input.value = previous.national;
                    this.format(input); input.dispatchEvent(new Event('input', {bubbles: true}));
                });
                group.before(select); control.option = select;
            }
            this.validate(input);
        }
    }
    validate(input) {
        if (!this.controls.has(input)) this.refresh();
        const control = this.controls.get(input); if (!control) return;
        if (fieldKind(input) === 'identity') {
            const normalized = digits(input.value).replace(/\D/g, '').slice(0, 11);
            if (input.value !== normalized) input.value = normalized;
        }
        const result = validateField(input.value, fieldKind(input), {country: input.dataset.country, currency: input.dataset.currency});
        input.setCustomValidity(result.error);
        input.setAttribute('aria-invalid', String(!result.valid)); control.error.textContent = result.error;
        if (result.valid) input.dataset.canonicalValue = result.canonical;
        else delete input.dataset.canonicalValue;
        if (fieldKind(input) === 'phone' && result.valid && result.country) { input.dataset.country = result.country; if (control.option) control.option.value = result.country; }
        return result;
    }
    format(input) {
        const result = this.validate(input);
        const display = ['date', 'number'].includes(input.type) ? result?.canonical : result?.display;
        if (result?.valid && input.value !== display) {
            input.value = display; input.dispatchEvent(new Event('input', {bubbles: true}));
        }
    }
    cancel(message = '') {
        const active = this.active; if (!active) return;
        this.active = null; clearTimeout(active.timer);
        active.button.setAttribute('aria-pressed', 'false'); active.button.classList.remove('is-listening');
        try { active.recognition.abort(); } catch { /* Already ended. */ }
        if (message) this.message(message);
    }
    listen(input) {
        if (!this.supported || !eligible(input) || !input.isConnected) return;
        if (this.active?.input === input) { try { this.active.recognition.stop(); } catch { this.cancel(); } return; }
        this.cancel();
        const control = this.controls.get(input), kind = fieldKind(input);
        let recognition;
        try { recognition = new this.Recognition(); } catch { this.message('Sesli giriş başlatılamadı. Klavyeyle devam edebilirsiniz.'); return; }
        recognition.lang = 'tr-TR'; recognition.continuous = false; recognition.interimResults = false; recognition.maxAlternatives = 1;
        const active = {input, recognition, button: control.button, initial: input.value, start: input.selectionStart ?? input.value.length, end: input.selectionEnd ?? input.value.length};
        this.active = active;
        const current = () => this.active === active && input.isConnected && eligible(input) && (this.root.closest('dialog')?.open ?? true);
        recognition.onstart = () => {
            if (!current()) return;
            active.button.setAttribute('aria-pressed', 'true'); active.button.classList.add('is-listening');
            this.message(`${control.label}: dinleniyor. Durdurmak için aynı mikrofona dokunun.`);
        };
        recognition.onresult = event => {
            if (!current()) return;
            let transcript = '';
            for (let index = 0; index < event.results.length; index++) if (event.results[index].isFinal) transcript += `${event.results[index][0].transcript} `;
            transcript = transcript.trim(); if (!transcript) return;
            active.gotResult = true;
            if (['identity', 'phone'].includes(kind) && spokenDigits(transcript) === null) { this.message('Rakamlar anlaşılamadı. Rakamları tek tek söyleyin veya klavyeyi kullanın.'); return; }
            let normalized = normalizeSpeech(transcript, kind);
            if (kind === 'identity' && normalized.replace(/\D/g, '').length > 11) { this.message('T.C. kimlik numarası 11 rakam olmalı. Konuşma aktarılmadı; sonucu yeniden söyleyin veya klavyeyi kullanın.'); return; }
            if (['date', 'number'].includes(input.type)) {
                const checked = validateField(normalized, kind, {country: input.dataset.country, currency: input.dataset.currency});
                if (!checked.valid) { this.message(checked.error); return; }
                normalized = checked.canonical;
            }
            const free = ['text', 'address', 'description', 'paragraph', 'article'].includes(kind);
            input.value = free ? active.initial.slice(0, active.start) + normalized + active.initial.slice(active.end) : normalized;
            this.receiving = true;
            try { input.dispatchEvent(new Event('input', {bubbles: true})); this.format(input); }
            finally { this.receiving = false; }
            this.message(input.validity.valid ? `${control.label}: konuşma aktarıldı. Sonucu kontrol edip düzeltebilirsiniz.` : `${control.label}: konuşma aktarıldı; işaretli bilgiyi düzeltin.`);
        };
        recognition.onerror = event => {
            if (!current()) return;
            const messages = {'not-allowed': 'Mikrofon izni verilmedi. Klavyeyle devam edebilirsiniz.', 'service-not-allowed': 'Konuşma hizmetine izin verilmedi. Klavyeyle devam edebilirsiniz.', 'audio-capture': 'Mikrofona erişilemiyor. Klavyeyle devam edebilirsiniz.', 'no-speech': 'Konuşma algılanmadı. Yeniden deneyebilir veya klavyeyi kullanabilirsiniz.', network: 'Konuşma hizmetine bağlanılamadı. Klavyeyle devam edebilirsiniz.', 'language-not-supported': 'Türkçe ses tanıma bu tarayıcıda kullanılamıyor. Klavyeyle devam edebilirsiniz.'};
            this.message(messages[event.error] || 'Sesli giriş tamamlanamadı. Klavyeyle devam edebilirsiniz.'); this.cancel();
        };
        recognition.onend = () => {
            if (this.active !== active) return;
            this.active = null; clearTimeout(active.timer); active.button.classList.remove('is-listening'); active.button.setAttribute('aria-pressed', 'false');
            if (!active.gotResult) this.message('Sesli giriş sona erdi. Yeniden deneyebilir veya klavyeyle devam edebilirsiniz.');
        };
        try {
            // Called only by an explicit button click; no getUserMedia/MediaRecorder or audio persistence.
            this.message(`${control.label}: mikrofon izni ve konuşma hizmeti bekleniyor…`);
            recognition.start();
            active.timer = setTimeout(() => { if (this.active === active) { try { recognition.stop(); } catch { this.cancel(); } } }, 30000);
        } catch { this.cancel(); this.message('Sesli giriş başlatılamadı. Klavyeyle devam edebilirsiniz.'); }
    }
}

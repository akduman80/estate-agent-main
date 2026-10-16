(() => {
    const dialog = document.getElementById('visitor-poll');
    if (!dialog || window.visitorPollStarted) return;
    window.visitorPollStarted = true;
    const read = (store, key, fallback) => { try { return JSON.parse(store.getItem(key)) ?? fallback; } catch { return fallback; } };
    const write = (store, key, value) => { try { store.setItem(key, JSON.stringify(value)); } catch {} };
    const storage = name => { try { return window[name]; } catch { return { getItem: () => null, setItem: () => {} }; } };
    const session = storage('sessionStorage');
    const persistent = storage('localStorage');
    let token = read(persistent, 'poll.browser', null);
    if (!token) { token = crypto.randomUUID(); write(persistent, 'poll.browser', token); }
    const started = read(session, 'poll.started', Date.now());
    write(session, 'poll.started', started);
    const form = dialog.querySelector('form');
    const submit = dialog.querySelector('.poll-submit');
    const message = dialog.querySelector('.poll-message');
    let poll, previousFocus, busy = false;
    const periodKey = () => `${poll.id}.${poll.period || 'legacy'}`;
    const shownThisPeriod = () => {
        const shown = read(session, 'poll.shown.v2', null);
        return shown && (shown.id !== poll.id || shown.period === (poll.period || 'legacy'));
    };
    const markShown = () => write(session, 'poll.shown.v2', { id: poll.id, period: poll.period || 'legacy' });
    const close = () => {
        markShown();
        if (poll) write(session, `poll.dismissed.${periodKey()}`, true);
        dialog.close();
        previousFocus?.focus?.();
    };
    dialog.querySelector('.poll-close').onclick = close;
    dialog.querySelector('.poll-later').onclick = close;
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    form.onchange = () => { submit.disabled = busy || !form.querySelector('input:checked'); };
    window.addEventListener('storage', event => {
        if (poll && event.key === `poll.voted.${periodKey()}` && dialog.open) close();
    });
    form.onsubmit = async event => {
        event.preventDefault();
        const selected = form.querySelector('input:checked');
        if (!selected || busy) return;
        busy = true; submit.disabled = true; message.textContent = 'Oyunuz kaydediliyor…';
        try {
            const response = await fetch(poll.url, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': dialog.dataset.csrf },
                body: JSON.stringify({ option_id: selected.value, voter_token: token, period: poll.period })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.recorded) throw new Error(response.status === 404
                ? 'Bu anketin yayın süresi sona ermiş olabilir.'
                : response.status === 409 ? 'Anketin oylama dönemi değişti. Sayfayı yenileyip tekrar deneyin.'
                : response.status === 419 ? 'Oturum süresi doldu. Sayfayı yenileyip yeniden deneyin.'
                : 'Oyunuz kaydedilemedi. Lütfen yeniden deneyin.');
            write(persistent, `poll.voted.${periodKey()}`, true);
            message.textContent = data.message;
            form.querySelectorAll('input').forEach(input => { input.disabled = true; });
            setTimeout(close, 1600);
        } catch (error) {
            message.textContent = error.message || 'Bağlantı kurulamadı. Lütfen yeniden deneyin.';
            busy = false; submit.disabled = false;
        }
    };
    const open = async () => {
        try {
            const response = await fetch(`${dialog.dataset.endpoint}?voter_token=${encodeURIComponent(token)}`, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store'
            });
            if (!response.ok) return;
            ({ poll } = await response.json());
            if (!poll || read(persistent, `poll.voted.${periodKey()}`, false)
                || read(session, `poll.dismissed.${periodKey()}`, false)
                || shownThisPeriod()
                || ((!poll.period || poll.period === 'legacy') && (read(persistent, `poll.voted.${poll.id}`, false)
                    || read(session, 'poll.shown', false) || read(session, `poll.dismissed.${poll.id}`, false)))
                || (poll.ends_at && Date.parse(poll.ends_at) <= Date.now())) return;
            dialog.querySelector('h2').textContent = poll.question;
            const fieldset = form.querySelector('fieldset');
            poll.options.forEach(option => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'radio'; input.name = 'option_id'; input.value = option.id; input.required = true;
                const text = document.createElement('span'); text.textContent = option.text;
                label.append(input, text); fieldset.append(label);
            });
            previousFocus = document.activeElement;
            markShown();
            dialog.showModal();
            dialog.querySelector('.poll-close').focus();
        } catch { /* The site remains usable when the poll service is unavailable. */ }
    };
    setTimeout(open, Math.max(0, 60000 - (Date.now() - started)));
})();

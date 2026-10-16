(() => {
    const disclosure = document.querySelector('[data-welcome-disclosure]');
    const control = document.querySelector('[data-welcome-control]');
    const button = document.querySelector('[data-welcome-toggle]');
    const content = document.getElementById('home-welcome-content');
    if (!disclosure || !control || !button || !content) return;

    let expanded = true;
    let timer;
    const setExpanded = (value) => {
        expanded = value;
        if (!expanded && content.contains(document.activeElement)) button.focus({ preventScroll: true });
        content.inert = !expanded;
        content.setAttribute('aria-hidden', String(!expanded));
        disclosure.classList.toggle('is-collapsed', !expanded);
        button.setAttribute('aria-expanded', String(expanded));
        button.setAttribute('aria-label', `Karşılama, hava durumu ve namaz vakitleri alanını ${expanded ? 'kapat' : 'aç'}`);
        button.title = expanded ? 'Üst alanı kapat' : 'Üst alanı aç';
    };
    control.hidden = false;
    button.addEventListener('click', () => {
        window.clearTimeout(timer);
        setExpanded(!expanded);
    });
    // A manually reopened area stays open until the visitor closes it.
    timer = window.setTimeout(() => setExpanded(false), 20000);
})();

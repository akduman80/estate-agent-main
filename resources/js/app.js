import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('[data-mobile-menu-button]');
    const menu = document.querySelector('[data-mobile-menu]');

    button?.addEventListener('click', () => {
        menu?.classList.toggle('hidden');
        button.setAttribute('aria-expanded', String(! menu?.classList.contains('hidden')));
    });
});

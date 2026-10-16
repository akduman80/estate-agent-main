import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8').replace("import './bootstrap';", '');
function setup(count, reduced = false) {
    const events = {};
    const timers = new Map();
    let timerId = 0;
    let hovered = false;
    const slides = Array.from({ length: count }, (_, index) => {
        const classes = new Set(index === 0 ? ['is-visible'] : []);
        return {
            complete: true, naturalWidth: 1600,
            dataset: { destination: index === 0 ? 'https://wa.me/905303607769?text=Merhaba' : `https://example.com/campaign-${index}`, label: `Reklam ${index}` },
            classList: { add: (...names) => names.forEach(name => classes.add(name)), remove: (...names) => names.forEach(name => classes.delete(name)), contains: name => classes.has(name) },
            setAttribute() {}, removeAttribute() {},
        };
    });
    const ad = { querySelectorAll: () => slides, matches: () => hovered, contains: target => target === ad, setAttribute() {}, addEventListener: (name, fn) => { events[name] = fn; } };
    const media = { matches: reduced, addEventListener: (name, fn) => { events.motion = fn; } };
    const document = { hidden: false, activeElement: null, querySelector: () => null, querySelectorAll: selector => selector === '[data-home-print-ad]' ? [ad] : [], addEventListener: (name, fn) => { events[name] = fn; } };
    const window = { matchMedia: () => media, setTimeout: (fn, delay) => { timers.set(++timerId, { fn, delay }); return timerId; }, clearTimeout: id => timers.delete(id) };
    vm.runInNewContext(source, { document, window, URL, console: { error() {} } });
    events.DOMContentLoaded();
    const tick = delay => {
        assert.equal(timers.size, 1);
        const [id, timer] = [...timers][0];
        assert.equal(timer.delay, delay);
        timers.delete(id); timer.fn();
    };
    return { events, timers, slides, ad, media, document, tick, hover: value => { hovered = value; } };
}

const state = setup(4);
assert.equal(state.ad.href, state.slides[0].dataset.destination);
for (const index of [1, 2, 3, 0]) {
    state.tick(5000);
    assert.equal(state.ad.href, state.slides[index].dataset.destination);
    assert(state.slides[(index + 3) % 4].classList.contains('is-previous'));
    state.tick(640);
    assert.equal(state.slides.filter(slide => slide.classList.contains('is-visible')).length, 1);
}
state.hover(true); state.events.pointerenter(); assert.equal(state.timers.size, 0);
state.hover(false); state.events.pointerleave(); assert.equal(state.timers.size, 1);
state.document.activeElement = state.ad; state.events.focusin(); assert.equal(state.timers.size, 0);
state.document.activeElement = null; state.events.focusout({ relatedTarget: null }); assert.equal(state.timers.size, 1);
state.document.hidden = true; state.events.visibilitychange(); assert.equal(state.timers.size, 0);
state.document.hidden = false; state.events.visibilitychange(); assert.equal(state.timers.size, 1);
state.media.matches = true; state.events.motion(); assert.equal(state.timers.size, 0);
assert.equal(setup(1).timers.size, 0);
assert.equal(setup(3, true).timers.size, 0);
const unloaded = setup(3);
unloaded.slides[1].complete = false; unloaded.tick(5000);
assert.equal(unloaded.ad.href, unloaded.slides[0].dataset.destination);
unloaded.slides[1].complete = true; unloaded.tick(5000); unloaded.tick(640);
assert.equal(unloaded.ad.href, unloaded.slides[1].dataset.destination);
console.log('PASS: 1–4 slides, ordered loop, 5000/640ms, retained image, exact target URLs, hover/focus/visibility/reduced motion, load retry.');

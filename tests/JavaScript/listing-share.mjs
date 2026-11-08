import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/listing-share.js', import.meta.url), 'utf8');
function fixture(navigator) {
    const element = () => ({ listeners: {}, textContent: '', addEventListener(event, listener) { this.listeners[event] = listener; } });
    const native = element();
    const copy = element();
    const close = element();
    const statuses = [element(), element()];
    const dialog = { open: false, showModal() { this.open = true; }, close() { this.open = false; } };
    const input = { focused: false, selected: false, focus() { this.focused = true; }, select() { this.selected = true; } };
    const card = {
        dataset: { title: 'İlan başlığı', url: 'https://zydemlak.com/ilan/test' },
        querySelector: (query) => ({ '[data-share-dialog]': dialog, '[data-share-link]': input, '[data-share-close]': close })[query],
        querySelectorAll: (query) => ({ '[data-share-native]': [native], '[data-share-copy]': [copy], '[data-share-status]': statuses })[query],
    };
    vm.runInNewContext(script, { document: { querySelectorAll: () => [card] }, navigator });
    return { native, copy, close, dialog, input, statuses };
}

let shared;
let state = fixture({ share: async (data) => { shared = data; } });
await state.native.listeners.click();
assert.equal(shared.url, 'https://zydemlak.com/ilan/test');
assert.equal(shared.title, 'İlan başlığı');
assert.equal(state.dialog.open, false);

state = fixture({});
await state.native.listeners.click();
assert.equal(state.dialog.open, true);
assert.equal(state.input.selected, true);
state.close.listeners.click();
assert.equal(state.dialog.open, false);

state = fixture({ share: async () => { throw { name: 'AbortError' }; } });
await state.native.listeners.click();
assert.equal(state.dialog.open, false);

state = fixture({ share: async () => { throw { name: 'NotAllowedError' }; } });
await state.native.listeners.click();
assert.equal(state.dialog.open, true);

let copied;
state = fixture({ clipboard: { writeText: async (url) => { copied = url; } } });
await state.copy.listeners.click();
assert.equal(copied, 'https://zydemlak.com/ilan/test');
assert.match(state.statuses[0].textContent, /kopyalandı/);
assert.equal(state.statuses[0].textContent, state.statuses[1].textContent);

state = fixture({ clipboard: { writeText: async () => { throw new Error('blocked'); } } });
await state.copy.listeners.click();
assert.equal(state.dialog.open, true);
assert.equal(state.input.focused, true);
assert.equal(state.input.selected, true);
assert.match(state.statuses[0].textContent, /Ctrl\+C/);
console.log('PASS: native share, no API fallback, cancellation, blocked share, clipboard and manual copy.');

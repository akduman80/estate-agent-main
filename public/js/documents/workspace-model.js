import {originalMappings} from './original-mappings.js?v=documents-20261018-all1';
// Drafts exist only in memory. Templates and original download routes are read-only.
export class DocumentDraft {
    constructor() { this.state = {values: {}, blocks: [], edits: {}}; this.past = []; this.future = []; }
    change(update) {
        const next = structuredClone(this.state); update(next);
        if (JSON.stringify(next) === JSON.stringify(this.state)) return;
        this.past.push(structuredClone(this.state));
        if (this.past.length > 100) this.past.shift();
        this.state = next; this.future = [];
    }
    undo() { if (!this.past.length) return; this.future.push(this.state); this.state = this.past.pop(); }
    redo() { if (!this.future.length) return; this.past.push(this.state); this.state = this.future.pop(); }
    reset() { this.change(state => { state.values = {}; state.blocks = []; state.edits = {}; }); }
    get dirty() { return Object.keys(this.state.values).some(key => this.state.values[key]) || this.state.blocks.length > 0 || Object.keys(this.state.edits || {}).length > 0; }
}

// Exact bytes, not list index/title, authorize a template mapping.
export const templates = {
    '1dc3d14ca5a5d05e0183893cb7c92adaf5b06f5a62481a52706f1364b2a2d60f': {
        id: 'tahliye-v1', size: [595.32, 841.92], pages: 1,
        fields: [
            {key: 'tenant', kind: 'name', label: 'Kiracının adı soyadı', y: 708.10},
            {key: 'tenantIdentity', kind: 'identity', label: 'Kiracının T.C. kimlik numarası', y: 669.10},
            {key: 'landlord', kind: 'name', label: 'Kiraya verenin adı soyadı', y: 643.18},
            {key: 'landlordIdentity', kind: 'identity', label: 'Kiraya verenin T.C. kimlik numarası', y: 604.06},
            {key: 'address', kind: 'address', label: 'Mecurun (kiralananın) adresi', y: 578.23, lines: 2},
            {key: 'commitmentDate', kind: 'date', label: 'Taahhüt tarihi', y: 552.31},
            {key: 'vacateDate', kind: 'date', label: 'Tahliye tarihi', y: 526.51},
        ].map(field => ({...field, positions: [{page: 1, x: 225, y: field.y, width: 299, lines: field.lines || 1}]})),
    },
};

for (const [hash, mapping] of Object.entries(originalMappings)) {
    if (templates[hash]) templates[hash].fields.push(...mapping.fields);
    else templates[hash] = mapping;
}

export function identifyFormat(bytes) {
    if (new TextDecoder().decode(bytes.slice(0, 5)) === '%PDF-') return 'pdf';
    // ZIP containers need an OOXML adapter before editing is allowed.
    if (bytes[0] === 0x50 && bytes[1] === 0x4b) return 'ooxml';
    return 'unsupported';
}

export async function templateFor(bytes, sourceUrl = null) {
    const hash = [...new Uint8Array(await crypto.subtle.digest('SHA-256', bytes))]
        .map(value => value.toString(16).padStart(2, '0')).join('');
    const published = typeof document !== 'undefined' ? JSON.parse(document.getElementById('document-template-catalog')?.textContent || '[]') : [];
    const revision = published.find(item => item.hash === hash && item.format === 'pdf' && sourceUrl && new URL(item.url, location.href).pathname === new URL(sourceUrl, location.href).pathname);
    const mapped = revision?.mapping;
    return {hash, template: mapped ? {id: revision.id, pages: mapped.pageSizes.length, size: mapped.pageSizes[0], pageSizes: mapped.pageSizes, fields: mapped.fields} : templates[hash] || null};
}

import {validateField} from './field-rules.js?v=documents-20261018-all1';
import {applyPdfEdits} from './pdf-edit.js?v=documents-20261018-all1';
import {discardUnreachableObjects} from './pdf-structure.js?v=documents-20261018-all1';

let engine;
function script(src) {
    return new Promise((resolve, reject) => {
        const node = document.createElement('script'); node.src = src;
        node.onload = resolve; node.onerror = () => { node.remove(); reject(new Error('PDF kütüphanesi yüklenemedi.')); };
        document.head.append(node);
    });
}
export async function pdfEngine() {
    if (!engine) engine = Promise.all([
        script('/vendor/pdf-output/pdf-lib.min.js'), script('/vendor/pdf-output/fontkit.umd.min.js'),
        fetch('/vendor/pdfjs/standard_fonts/LiberationSans-Regular.ttf').then(r => { if (!r.ok) throw new Error('Yazı tipi yüklenemedi.'); return r.arrayBuffer(); }),
    ]).then(([, , font]) => ({...window.PDFLib, fontkit: window.fontkit, font})).catch(error => { engine = null; throw error; });
    return engine;
}

// All coordinates are PDF points, lower-left origin. Preview renders these exact bytes.
export function wrapText(text, font, size, width) {
    const supported = font.getCharacterSet ? new Set(font.getCharacterSet()) : null;
    if (supported && [...String(text)].some(character => !/\s/.test(character) && !supported.has(character.codePointAt(0)))) throw new Error('Metinde seçilen yazı tipinin desteklemediği karakter var. İlgili karakteri değiştirin.');
    const lines = [];
    for (const paragraph of String(text).replace(/\r/g, '').split('\n')) {
        let line = '';
        for (const word of paragraph.split(/\s+/).filter(Boolean)) {
            if (font.widthOfTextAtSize(word, size) > width) throw new Error('Bir kelime ayrılan alana sığmıyor. Metni kısaltın.');
            const next = line ? `${line} ${word}` : word;
            if (line && font.widthOfTextAtSize(next, size) > width) { lines.push(line); line = word; } else line = next;
        }
        lines.push(line);
    }
    return lines;
}
export async function buildPdf(source, template, state, mode, analyses = []) {
    if (source.byteLength > 25 * 1024 * 1024) throw new Error('Belge çok büyük.');
    const lib = await pdfEngine();
    const {PDFDocument, rgb, fontkit, font: fontBytes} = lib;
    const pdf = await PDFDocument.load(source.slice(), {updateMetadata: false});
    if (pdf.getPages().some(page => page.getRotation().angle % 360)) throw new Error('Döndürülmüş sayfa için çıktı eşlemesi desteklenmiyor.');
    pdf.registerFontkit(fontkit);
    const font = await pdf.embedFont(fontBytes, {subset: true});
    const fonts = new Map([['Regular', font]]);
    async function positionFont(p) {
        const style = p.bold && p.italic ? 'BoldItalic' : p.bold ? 'Bold' : p.italic ? 'Italic' : 'Regular';
        if (!fonts.has(style)) {
            const response = await fetch(`/vendor/pdfjs/standard_fonts/LiberationSans-${style}.ttf`);
            if (!response.ok) throw new Error('Seçilen yazı biçimi yüklenemedi.');
            fonts.set(style, await pdf.embedFont(await response.arrayBuffer(), {subset: true}));
        }
        return fonts.get(style);
    }
    const pages = pdf.getPages();
    if (mode === 'fill') {
        if (!template) throw new Error('Bu belge için doğrulanmış alan eşlemesi yok.');
        for (const field of template.fields) {
            const raw = field.locked ? '' : state.values[field.key] || '';
            const checked = validateField(raw, field.kind, {country: field.country, currency: field.currency});
            if (!checked.valid) throw new Error(`${field.label}: ${checked.error}`);
            if (!raw.trim()) continue;
            for (const p of field.positions) {
                const page = pages[p.page - 1]; if (!page) throw new Error('Şablon sayfası bulunamadı.');
                const bounds = page.getSize();
                if (p.x < 0 || p.y < 0 || p.width <= 0 || p.x + p.width > bounds.width || p.y > bounds.height) throw new Error('Alan sayfa sınırları dışında.');
                const size = p.fontSize || field.fontSize || 11, lineHeight = size * 1.15;
                const fieldFont = await positionFont(p);
                const lines = wrapText(checked.display, fieldFont, size, p.width);
                const maxLines = p.lines || Math.max(1, Math.floor((p.height || size * 1.2) / lineHeight));
                if (lines.length > maxLines || p.y - (lines.length - 1) * lineHeight < 0) throw new Error(`${field.label}: Metin ayrılan alana sığmıyor. Kısaltın.`);
                lines.forEach((line, i) => page.drawText(line, {x: p.x, y: p.y - i * lineHeight, size, font: fieldFont, color: rgb(0, 0, 0)}));
            }
        }
    } else {
        if (Object.keys(state.edits || {}).length) await applyPdfEdits(pdf, lib, analyses, state.edits, async item => {
            const selected = await positionFont({bold:/bold/i.test(item.fontFamily),italic:/italic|oblique/i.test(item.fontFamily)});
            wrapText(state.edits[`${item.page}:${item.id}`] || '', selected, item.fontSize, Math.max(item.width, 1));
            return selected;
        });
        // Added material goes onto explicitly separate appendix pages; originals are never masked.
        let page, y;
        const newPage = () => { page = pdf.addPage([595.28, 841.89]); y = 790; };
        const write = (text, size = 11) => {
            for (const line of wrapText(text, font, size, 495)) {
                if (!page || y < 55) newPage();
                page.drawText(line, {x: 50, y, size, font, color: rgb(0, 0, 0)}); y -= size * 1.4;
            }
            y -= 8;
        };
        for (const block of state.blocks) {
            for (const data of Object.values(block.fieldData || {})) if (!data.valid) throw new Error('Ek içerikte geçersiz alan var. İşaretli alanı düzeltin.');
            if (!page) { newPage(); write('Ek içerik', 15); }
            if (block.type === 'table') {
                for (const row of block.rows) {
                    const cols = row.map(value => wrapText(value, font, 11, 231));
                    const h = Math.max(1, ...cols.map(col => col.length)) * 15 + 12;
                    if (h > 720) throw new Error('Tablo satırı bir sayfaya sığmıyor. Satırı kısaltın.');
                    if (y - h < 55) newPage();
                    cols.forEach((col, index) => {
                        page.drawRectangle({x: 50 + index * 247.5, y: y - h, width: 247.5, height: h, borderWidth: .5, borderColor: rgb(.4, .4, .4)});
                        col.forEach((line, i) => page.drawText(line, {x: 56 + index * 247.5, y: y - 15 - i * 15, font, size: 11}));
                    }); y -= h + 8;
                }
            } else if (block.type === 'dateAmount') {
                write([block.fieldData?.date?.display || block.date, block.fieldData?.amount?.display || block.amount, block.text].filter(Boolean).join('\n'));
            } else {
                write(block.fieldData?.text?.display || block.text || '');
                if (block.type === 'signature') { if (y < 110) newPage(); write('İmza: ______________________________'); y -= 35; }
            }
        }
    }
    if (mode === 'edit' && Object.keys(state.edits || {}).length) await discardUnreachableObjects(pdf, lib);
    return pdf.save();
}

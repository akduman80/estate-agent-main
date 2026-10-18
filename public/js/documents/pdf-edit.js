import {analyzeTextPage, replacePageText} from './pdf-structure.js?v=documents-20261018-all1';

// The original stays immutable. Only decoded content streams in the working copy change.
export async function inspectPdf(pdf, OPS) {
    const pages = [];
    for (let number = 1; number <= pdf.numPages; number++) {
        const page = await pdf.getPage(number);
        if (page.rotate % 360) throw new Error('Döndürülmüş sayfa metni düzenlenemiyor.');
        const analysis = await analyzeTextPage(page, OPS);
        const content = await page.getTextContent();
        const size = page.getViewport({scale: 1});
        analysis.items.forEach(item => {
            item.page = number;
            item.fontSize = Math.max(6, Math.min(36, item.height));
            item.fontFamily = content.styles[item.font]?.fontFamily || 'sans-serif';
            try { item.fontFamily = page.commonObjs.get(item.font)?.name || item.fontFamily; } catch {}
        });
        pages.push({...analysis, size: [size.width, size.height]});
    }
    return pages;
}

function include(removals, refs, analysis) {
    for (const reference of refs) {
        if (!removals.has(reference.op)) removals.set(reference.op, new Set());
        const set = removals.get(reference.op);
        reference.glyphs.forEach(index => set.add(index));
        // Include intervening whitespace, while retaining text advance for neighbours.
        const first = Math.min(...reference.glyphs), last = Math.max(...reference.glyphs);
        for (let i = first; i <= last; i++) if (/^\s*$/.test(analysis.operations[reference.op].glyphs[i]?.unicode || '')) set.add(i);
    }
}

export async function applyPdfEdits(pdf, lib, analyses, edits, fontFor) {
    for (let index = 0; index < analyses.length; index++) {
        const analysis = analyses[index], removals = new Map(), replacements = [];
        for (const item of analysis.items) {
            const key = `${index + 1}:${item.id}`;
            if (!Object.hasOwn(edits || {}, key) || edits[key] === item.text) continue;
            if (!item.editable) throw new Error('Seçilen PDF metninin karakter eşlemesi doğrulanamadı.');
            include(removals, item.removal, analysis);
            const text = String(edits[key]);
            if (text.trim()) {
                const font = await fontFor(item);
                if (text.includes('\n') || font.widthOfTextAtSize(text, item.fontSize) > item.width + .5) throw new Error(`Sayfa ${index + 1}: Yeni metin eski satırın alanına sığmıyor. Kısaltın veya ek içerik kullanın.`);
                replacements.push({item, text, font});
            }
        }
        replacePageText(pdf, lib, index + 1, analysis, removals);
        const page = pdf.getPages()[index];
        for (const {item, text, font} of replacements) page.drawText(text, {x:item.x, y:item.y, size:item.fontSize, font, rotate:lib.degrees(item.angle || 0), color:lib.rgb(0,0,0)});
    }
}

export function allTextRemovals(analysis) {
    const removals = new Map();
    analysis.operations.forEach((op, index) => removals.set(index, new Set(op.glyphs.map((_, i) => i))));
    return removals;
}

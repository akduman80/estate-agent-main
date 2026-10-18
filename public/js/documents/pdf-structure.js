// Parse PDF content tokens without executing JavaScript or painting over old text.
export function contentTokens(source) {
    let offset = 0;
    const whitespace = char => /[\x00\t\n\f\r ]/.test(char);
    function next() {
        while (offset < source.length) {
            if (whitespace(source[offset])) { offset++; continue; }
            if (source[offset] === '%') { while (offset < source.length && !/[\r\n]/.test(source[offset])) offset++; continue; }
            break;
        }
        if (offset >= source.length) return null;
        const start = offset, char = source[offset++];
        if (char === '(') {
            const bytes = []; let depth = 1;
            while (offset < source.length && depth) {
                let c = source[offset++];
                if (c === '\\') {
                    c = source[offset++];
                    if (c === '\r' || c === '\n') { if (c === '\r' && source[offset] === '\n') offset++; continue; }
                    if (/[0-7]/.test(c)) { let octal = c; for (let i = 0; i < 2 && /[0-7]/.test(source[offset] || 'x'); i++) octal += source[offset++]; bytes.push(parseInt(octal, 8) & 255); }
                    else bytes.push(({n:10,r:13,t:9,b:8,f:12})[c] ?? c.charCodeAt(0));
                } else if (c === '(') { depth++; bytes.push(40); }
                else if (c === ')') { if (--depth) bytes.push(41); }
                else { if (c === '\r') { if (source[offset] === '\n') offset++; c = '\n'; } bytes.push(c.charCodeAt(0) & 255); }
            }
            if (depth) throw new Error('PDF metin dizgesi tamamlanmamış.');
            return {type:'string', bytes, start, end:offset};
        }
        if (char === '<' && source[offset] !== '<') {
            let hex = ''; while (offset < source.length && source[offset] !== '>') hex += source[offset++];
            if (source[offset++] !== '>') throw new Error('PDF hex dizgesi tamamlanmamış.');
            hex = hex.replace(/\s/g, ''); if (!/^[a-f\d]*$/i.test(hex)) throw new Error('PDF hex dizgesi geçersiz.');
            if (hex.length % 2) hex += '0';
            return {type:'string', bytes:Array.from({length:hex.length/2}, (_,i)=>parseInt(hex.slice(i*2,i*2+2),16)), start, end:offset};
        }
        if (char === '[') {
            const values = []; let token;
            while ((token = next()) && token.value !== ']') values.push(token);
            if (!token) throw new Error('PDF dizisi tamamlanmamış.');
            return {type:'array', values, start, end:offset};
        }
        if (char === '<' && source[offset] === '<') { offset++; return {type:'delimiter',value:'<<',start,end:offset}; }
        if (char === '>' && source[offset] === '>') { offset++; return {type:'delimiter',value:'>>',start,end:offset}; }
        if (char === ']') return {type:'delimiter',value:']',start,end:offset};
        let value = char;
        while (offset < source.length && !whitespace(source[offset]) && !'()<>[]{}/%'.includes(source[offset])) value += source[offset++];
        return {type:char === '/' ? 'name' : /^[+-]?(?:\d+\.?\d*|\.\d+)$/.test(value) ? 'number' : 'word', value, start, end:offset};
    }
    const result = []; let token; while ((token = next())) result.push(token); return result;
}

export function textOperators(source) {
    const result = []; let operands = [], inText = false, dictionaryDepth = 0;
    for (const token of contentTokens(source)) {
        if (token.value === '<<') dictionaryDepth++;
        if (token.value === '>>') dictionaryDepth--;
        if (token.type !== 'word' || dictionaryDepth || ['true','false','null'].includes(token.value)) { operands.push(token); continue; }
        const op = token.value;
        if (op === 'BI') throw new Error('Satır içi görüntülü PDF için güvenilir metin düzenleme sağlanamıyor.');
        if (op === 'BT') inText = true;
        if (inText && ['Tj','TJ',"'",'"'].includes(op)) {
            const value = operands.at(-1), parts = value?.type === 'array' ? value.values : value?.type === 'string' ? [value] : [];
            if (!parts.length || parts.some(p => !['string','number'].includes(p.type))) throw new Error('PDF metin işlemi çözümlenemedi.');
            if (parts.some(p => p.type === 'string' && p.bytes.length)) result.push({parts, operator:op, operands, start:operands[0].start, end:token.end});
        }
        if (op === 'ET') inText = false;
        operands = [];
    }
    return result;
}

export async function analyzeTextPage(page, OPS) {
    const [list, content] = await Promise.all([page.getOperatorList(), page.getTextContent()]);
    const operations = []; let fontSize = 12, charSpacing = 0, wordSpacing = 0, font;
    for (let i = 0; i < list.fnArray.length; i++) {
        const op = list.fnArray[i], args = list.argsArray[i];
        if (op === OPS.setFont) [font, fontSize] = args;
        if (op === OPS.setCharSpacing) charSpacing = args[0];
        if (op === OPS.setWordSpacing) wordSpacing = args[0];
        if (op !== OPS.showText && op !== OPS.showSpacedText) continue;
        const glyphs = (args[0] || []).filter(g => g && typeof g === 'object');
        if (glyphs.length) operations.push({glyphs, fontSize, charSpacing, wordSpacing, font});
    }
    let searchable = ''; const lookup = [];
    operations.forEach((operation, op) => operation.glyphs.forEach((glyph, index) => {
        for (const char of glyph.unicode || '') if (!/\s/.test(char)) { searchable += char; for (let i=0;i<char.length;i++) lookup.push({op,index}); }
    }));
    let cursor = 0;
    const items = content.items.filter(item => item.str?.trim()).map((item, id) => {
        const needle = item.str.replace(/\s/g, ''), at = searchable.indexOf(needle, cursor);
        const targets = at >= 0 ? lookup.slice(at, at + needle.length) : [];
        if (at >= 0) cursor = at + needle.length;
        const glyphs = new Map();
        targets.forEach(({op,index}) => { if (!glyphs.has(op)) glyphs.set(op, new Set()); glyphs.get(op).add(index); });
        return {id, text:item.str, x:item.transform[4], y:item.transform[5], width:item.width, height:item.height, font:item.fontName, angle:Math.atan2(item.transform[1],item.transform[0])*180/Math.PI,
            removal:[...glyphs].map(([op,indices])=>({op,glyphs:[...indices]})), editable:at>=0};
    });
    return {operations, items};
}

export function rewriteText(source, analysis, removals) {
    const operators = textOperators(source);
    if (operators.length !== analysis.operations.length) throw new Error('PDF metin sırası doğrulanamadı; değişiklik uygulanmadı.');
    const changes = [];
    for (const [opIndex, indices] of removals) {
        const raw = operators[opIndex], metadata = analysis.operations[opIndex];
        if (!raw || !metadata) throw new Error('PDF metin başvurusu geçersiz.');
        const originalBytes = raw.parts.filter(p=>p.type==='string').flatMap(p=>p.bytes);
        const size = originalBytes.length / metadata.glyphs.length;
        if (![1,2].includes(size)) throw new Error('PDF karakter kodlaması doğrulanamadı.');
        let at = 0;
        for (let i=0;i<originalBytes.length;i+=size) {
            const code = size===1 ? originalBytes[i] : originalBytes[i]*256+originalBytes[i+1];
            if (code !== metadata.glyphs[at++].originalCharCode) throw new Error('PDF karakter eşlemesi doğrulanamadı.');
        }
        let glyphIndex = 0; const result = [];
        for (const part of raw.parts) {
            if (part.type === 'number') { result.push(part.value); continue; }
            if (part.bytes.length % size) throw new Error('PDF karakter sınırı geçersiz.');
            let retained = [];
            const flush = () => { if (retained.length) result.push('<'+retained.map(b=>b.toString(16).padStart(2,'0')).join('')+'>'); retained=[]; };
            for (let i=0;i<part.bytes.length;i+=size) {
                const glyph = metadata.glyphs[glyphIndex];
                if (indices.has(glyphIndex)) {
                    flush();
                    const advance = glyph.width + 1000*(metadata.charSpacing+(glyph.isSpace?metadata.wordSpacing:0))/metadata.fontSize;
                    result.push(String(-advance));
                } else retained.push(...part.bytes.slice(i,i+size));
                glyphIndex++;
            }
            flush();
        }
        // Quoted text operators also change spacing or move to the next line.
        const prefix = raw.operator === "'" ? 'T* ' : raw.operator === '"' ? `${raw.operands[0].value} Tw ${raw.operands[1].value} Tc T* ` : '';
        changes.push({start:raw.start,end:raw.end,value:`${prefix}[${result.join(' ')}] TJ`});
    }
    for (const change of changes.sort((a,b)=>b.start-a.start)) source = source.slice(0,change.start)+change.value+source.slice(change.end);
    return source;
}

export function replacePageText(pdf, lib, pageNumber, analysis, removals) {
    if (!removals.size) return;
    const page = pdf.getPages()[pageNumber-1], contents = page.node.Contents();
    const entries = contents instanceof lib.PDFArray ? contents.asArray() : [contents];
    const decoded = entries.map(ref => lib.decodePDFRawStream(pdf.context.lookup(ref)).decode());
    const source = decoded.map(bytes => Array.from(bytes,b=>String.fromCharCode(b)).join('')).join('\n');
    const changed = rewriteText(source, analysis, removals);
    page.node.set(lib.PDFName.of('Contents'),pdf.context.register(pdf.context.flateStream(Uint8Array.from(changed,c=>c.charCodeAt(0)))));
}

export async function discardUnreachableObjects(pdf, lib) {
    await pdf.flush();
    const reachable = new Set(), visited = new Set();
    function visit(value) {
        if (!value || visited.has(value)) return; visited.add(value);
        if (value instanceof lib.PDFRef) { reachable.add(value.toString()); visit(pdf.context.lookup(value)); }
        else if (value instanceof lib.PDFArray) value.asArray().forEach(visit);
        else if (value instanceof lib.PDFDict) value.entries().forEach(([,v])=>visit(v));
        else if (value.dict) visit(value.dict);
    }
    Object.values(pdf.context.trailerInfo).forEach(visit);
    for (const [ref] of pdf.context.enumerateIndirectObjects()) if (!reachable.has(ref.toString())) pdf.context.delete(ref);
}

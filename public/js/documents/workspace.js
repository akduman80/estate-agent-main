import {DocumentDraft, identifyFormat, templateFor} from './workspace-model.js';

const dialog = document.getElementById('document-workspace');
if (dialog) initialize();

function initialize() {
    const find = name => dialog.querySelector(`[data-workspace-${name}]`);
    const form = find('fields'), blocks = find('blocks');
    const canvas = dialog.querySelector('canvas'), context = canvas.getContext('2d');
    const modes = [...dialog.querySelectorAll('[data-workspace-mode]')];
    const labels = {paragraph: 'Paragraf', article: 'Madde', note: 'Açıklama', dateAmount: 'Tarih / tutar', table: 'Tablo', signature: 'İmza alanı'};
    let session = null, generation = 0, renderTask = null, resizeTimer;
    let baseCanvas = null, viewport = null;
    const draft = () => session.drafts[session.mode];
    const say = message => { find('status').textContent = message; };
    const button = (label, callback) => {
        const node = document.createElement('button'); node.type = 'button'; node.textContent = label;
        node.addEventListener('click', callback); return node;
    };
    function history() {
        find('undo').disabled = !draft().past.length;
        find('redo').disabled = !draft().future.length;
        find('reset').disabled = !draft().dirty;
    }
    function setMode(mode) {
        if (!session || !['fill', 'edit'].includes(mode)) return;
        session.mode = mode;
        modes.forEach(node => node.setAttribute('aria-pressed', String(node.dataset.workspaceMode === mode)));
        find('fill').hidden = mode !== 'fill'; find('edit').hidden = mode !== 'edit';
        find('subtitle').textContent = `${mode === 'fill' ? 'Doldurma' : 'Düzenleme'} • Ayrı çalışma kopyası • Orijinal korunur`;
        rebuildControls(); paintValues();
    }
    function rebuildControls() {
        form.replaceChildren(); blocks.replaceChildren();
        const template = session.template;
        find('fill-notice').textContent = template
            ? 'Üst bölümdeki yedi boş alan doldurulur. Belge sonundaki noktalı tarih ve imza alanı bu eşlemeye dahil değildir. Sabit metin ve yerleşim korunur; yalnız önizleme yapılır.'
            : 'Bu belgenin alan eşlemesi henüz hazır değil. Doldurma kullanılamıyor; orijinalini indirebilirsiniz.';
        if (template) for (const field of template.fields) {
            const label = document.createElement('label'); label.className = 'dw-field'; label.textContent = field.label;
            const input = document.createElement(field.lines > 1 ? 'textarea' : 'input');
            if (input.tagName === 'INPUT') input.type = 'text';
            input.name = field.key; input.value = session.drafts.fill.state.values[field.key] || '';
            input.addEventListener('input', () => {
                session.drafts.fill.change(state => { state.values[field.key] = input.value; });
                history(); paintValues();
            });
            label.append(input); form.append(label);
        }
        for (const block of session.drafts.edit.state.blocks) renderBlock(block);
        history();
    }
    function editBlock(id, update) {
        session.drafts.edit.change(state => update(state.blocks.find(block => block.id === id)));
        history();
    }
    function renderBlock(block) {
        const section = document.createElement('section'); section.className = 'dw-block';
        const heading = document.createElement('div'); heading.className = 'dw-block-heading';
        const title = document.createElement('strong'); title.textContent = labels[block.type];
        heading.append(title, button(`${labels[block.type]} kaldır`, () => {
            session.drafts.edit.change(state => { state.blocks = state.blocks.filter(item => item.id !== block.id); });
            rebuildControls();
        }));
        section.append(heading);
        function field(labelText, key, multiline = false) {
            const label = document.createElement('label'); label.className = 'dw-field'; label.textContent = labelText;
            const input = document.createElement(multiline ? 'textarea' : 'input');
            if (!multiline) input.type = 'text';
            input.value = block[key] || '';
            input.addEventListener('input', () => editBlock(block.id, target => { target[key] = input.value; }));
            label.append(input); section.append(label);
        }
        if (block.type === 'table') {
            const scroll = document.createElement('div'); scroll.className = 'dw-table-scroll';
            const table = document.createElement('table'); table.setAttribute('aria-label', 'Çalışma kopyasına eklenen tablo');
            const body = document.createElement('tbody');
            block.rows.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');
                row.forEach((value, columnIndex) => {
                    const td = document.createElement('td'), input = document.createElement('input');
                    input.type = 'text'; input.value = value;
                    input.setAttribute('aria-label', `${rowIndex + 1}. satır, ${columnIndex + 1}. sütun`);
                    input.addEventListener('input', () => editBlock(block.id, target => { target.rows[rowIndex][columnIndex] = input.value; }));
                    td.append(input); tr.append(td);
                });
                const td = document.createElement('td'); td.style.width = '52px';
                td.append(button('Sil', () => { editBlock(block.id, target => target.rows.splice(rowIndex, 1)); rebuildControls(); }));
                tr.append(td); body.append(tr);
            });
            table.append(body); scroll.append(table); section.append(scroll);
            section.append(button('Satır ekle', () => { editBlock(block.id, target => target.rows.push(['', ''])); rebuildControls(); }));
        } else if (block.type === 'dateAmount') {
            field('Tarih', 'date'); field('Tutar ve para birimi', 'amount'); field('Açıklama', 'text', true);
        } else if (block.type === 'signature') {
            field('İmzalayacak kişinin adı / unvanı', 'text');
            const note = document.createElement('p'); note.textContent = 'İmza için boş alan. Elektronik imza oluşturulmaz.'; section.append(note);
        } else field(labels[block.type], 'text', true);
        blocks.append(section);
    }
    function paintValues() {
        if (!session || !baseCanvas || !viewport) return;
        canvas.width = baseCanvas.width; canvas.height = baseCanvas.height;
        context.drawImage(baseCanvas, 0, 0);
        if (session.mode !== 'fill' || !session.template) return;
        const scale = viewport.scale, values = session.drafts.fill.state.values;
        let overflowing = false;
        for (const field of session.template.fields) {
            const value = (values[field.key] || '').trim(); if (!value) continue;
            for (const position of field.positions.filter(item => item.page === session.page)) {
                let fontSize = 11, lines;
                const wrap = () => {
                    context.font = `${fontSize * scale}px DocumentTemplateSans, Arial, sans-serif`;
                    const words = value.replace(/\s+/g, ' ').split(' '), result = []; let line = '';
                    for (const word of words) {
                        const next = line ? `${line} ${word}` : word;
                        if (line && context.measureText(next).width > position.width * scale) { result.push(line); line = word; }
                        else line = next;
                    }
                    if (line) result.push(line); return result;
                };
                do { lines = wrap(); if (lines.length <= position.lines && lines.every(line => context.measureText(line).width <= position.width * scale)) break; fontSize -= .5; } while (fontSize >= 8);
                if (fontSize < 8) { overflowing = true; continue; }
                context.fillStyle = '#111'; context.textBaseline = 'alphabetic';
                lines.forEach((line, index) => {
                    const point = viewport.convertToViewportPoint(position.x, position.y - index * 12);
                    context.fillText(line, point[0], point[1]);
                });
            }
        }
        say(overflowing ? 'Bazı bilgiler ayrılan alana sığmıyor ve önizlemeye aktarılmadı. Daha kısa bilgi girin.' : 'Gerçek şablon üzerinde önizleme. Bilgiler sunucuya gönderilmez; çıktı henüz sunulmaz.');
    }
    async function renderPage(token = generation) {
        if (!session?.pdf || !dialog.open) return;
        const current = session;
        const pageNumber = current.page;
        if (renderTask) { renderTask.cancel(); renderTask = null; }
        const page = await current.pdf.getPage(pageNumber);
        if (token !== generation || current !== session || pageNumber !== current.page) return;
        const width = Math.max(240, Math.min(find('paper').parentElement.clientWidth - 32, 1000));
        const initial = page.getViewport({scale: 1});
        const nextViewport = page.getViewport({scale: width / initial.width * Math.min(devicePixelRatio || 1, 2)});
        const buffer = document.createElement('canvas'); buffer.width = Math.ceil(nextViewport.width); buffer.height = Math.ceil(nextViewport.height);
        const task = page.render({canvasContext: buffer.getContext('2d'), viewport: nextViewport}); renderTask = task;
        try { await task.promise; } catch (error) { if (error.name === 'RenderingCancelledException') return; throw error; }
        if (token !== generation || current !== session || pageNumber !== current.page || renderTask !== task) return;
        renderTask = null; baseCanvas = buffer; viewport = nextViewport;
        find('page').textContent = `${current.page} / ${current.pdf.numPages}`;
        find('prev').disabled = current.page <= 1; find('next').disabled = current.page >= current.pdf.numPages;
        find('paper').hidden = false; find('paging').hidden = false; find('preview-message').hidden = true;
        paintValues();
    }
    async function open(detail) {
        if (session) return;
        const url = new URL(detail.url, location.href);
        if (url.origin !== location.origin) return;
        const token = ++generation;
        session = {mode: detail.mode, title: detail.title, trigger: detail.trigger, drafts: {fill: new DocumentDraft(), edit: new DocumentDraft()}, template: null, page: 1, pdf: null, abort: new AbortController()};
        find('paper').hidden = true; find('paging').hidden = true;
        find('preview-message').hidden = false; find('preview-message').textContent = 'Orijinal belge yükleniyor…';
        baseCanvas = null; viewport = null;
        document.getElementById('document-workspace-title').textContent = detail.title;
        find('download').href = url.href;
        dialog.showModal(); setMode(detail.mode); say('Orijinalden ayrı çalışma kopyası hazırlanıyor…');
        try {
            const response = await fetch(url.href, {signal: session.abort.signal, credentials: 'same-origin', redirect: 'error'});
            if (!response.ok) throw new Error('download');
            const declaredSize = Number(response.headers.get('Content-Length'));
            if (declaredSize > 25 * 1024 * 1024) throw new Error('size');
            const bytes = new Uint8Array(await response.arrayBuffer());
            if (bytes.byteLength > 25 * 1024 * 1024) throw new Error('size');
            if (token !== generation) return;
            // Keep the source bytes immutable; PDF.js receives a separate buffer.
            session.source = bytes;
            const format = identifyFormat(bytes);
            if (format !== 'pdf') {
                find('preview-message').textContent = format === 'ooxml'
                    ? 'Bu Office/ZIP belgesinde (DOCX dahil) sayfa düzenini koruyan tarayıcı önizlemesi ve şablon düzenlemesi henüz desteklenmiyor. Orijinalini indirebilir, ayrı çalışma kopyasına ek içerik hazırlayabilirsiniz.'
                    : 'Bu dosya türünde önizleme ve şablon düzenlemesi desteklenmiyor. Orijinalini indirebilirsiniz.';
                say('Doldurma kapalı. Ek içerikler yalnız ayrı çalışma kopyasında hazırlanır.');
                return;
            }
            const {template} = await templateFor(bytes);
            const pdfBase = new URL(dialog.dataset.pdfBase, location.href);
            const pdfjs = await import(new URL('pdf.min.js', pdfBase).href);
            if (token !== generation) return;
            pdfjs.GlobalWorkerOptions.workerSrc = new URL('pdf.worker.min.js', pdfBase).href;
            session.loadingTask = pdfjs.getDocument({data: bytes.slice(), isEvalSupported: false, enableXfa: false, standardFontDataUrl: new URL('standard_fonts/', pdfBase).href, cMapUrl: new URL('cmaps/', pdfBase).href, cMapPacked: true, wasmUrl: new URL('wasm/', pdfBase).href});
            const pdf = await session.loadingTask.promise;
            if (token !== generation) return;
            session.pdf = pdf;
            if (template && template.pages === pdf.numPages) {
                const page = await pdf.getPage(1);
                if (token !== generation) return;
                const size = page.getViewport({scale: 1});
                if (Math.abs(size.width - template.size[0]) < .1 && Math.abs(size.height - template.size[1]) < .1) session.template = template;
            }
            await document.fonts.load('11px DocumentTemplateSans');
            if (token !== generation) return;
            rebuildControls(); say(session.template ? 'Alan eşlemesi doğrulandı. Doldurma önizlemesi hazır.' : 'Gerçek PDF önizlemesi. Alan eşlemesi hazır olmadığı için doldurma kapalı.');
            await renderPage(token);
        } catch (error) {
            if (token !== generation || error.name === 'AbortError') return;
            session.template = null; rebuildControls();
            find('preview-message').hidden = false;
            find('preview-message').textContent = 'Belge yüklenemedi veya bu tarayıcı önizlemeyi desteklemiyor. Orijinali İndir bağlantısını kullanabilirsiniz.';
            say('Önizleme açılamadı. Orijinal belge değiştirilmedi.');
        }
    }
    const hasChanges = () => session && Object.values(session.drafts).some(item => item.dirty);
    function close() {
        if (hasChanges() && !confirm('Kaydedilmemiş çalışma kopyası silinecek. Kapatılsın mı?')) return;
        dialog.close();
    }
    document.addEventListener('document-workspace:open', event => open(event.detail));
    form.addEventListener('submit', event => event.preventDefault());
    modes.forEach(node => node.addEventListener('click', () => { setMode(node.dataset.workspaceMode); say(session.template ? 'Mod değiştirildi. Doldurma ve ek içerik çalışmaları ayrı tutulur.' : 'Alan eşlemesi hazır değil. Yalnız ek içerik çalışma kopyası düzenlenebilir.'); }));
    find('close').addEventListener('click', close);
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('close', () => {
        ++generation; session?.abort.abort(); renderTask?.cancel(); renderTask = null;
        const trigger = session?.trigger;
        const task = session?.loadingTask;
        if (task && typeof task.destroy === 'function') Promise.resolve(task.destroy()).catch(() => {});
        session = null; baseCanvas = null; viewport = null; form.replaceChildren(); blocks.replaceChildren();
        canvas.width = 0; canvas.height = 0;
        trigger?.focus({preventScroll: true});
    });
    window.addEventListener('beforeunload', event => { if (hasChanges()) { event.preventDefault(); event.returnValue = ''; } });
    find('undo').addEventListener('click', () => { draft().undo(); rebuildControls(); paintValues(); });
    find('redo').addEventListener('click', () => { draft().redo(); rebuildControls(); paintValues(); });
    find('reset').addEventListener('click', () => {
        if (draft().dirty && confirm('Bu moddaki değişiklikler kaldırılıp orijinal duruma dönülsün mü?')) { draft().reset(); rebuildControls(); paintValues(); }
    });
    dialog.querySelectorAll('[data-workspace-add]').forEach(node => node.addEventListener('click', () => {
        const type = node.dataset.workspaceAdd;
        session.drafts.edit.change(state => state.blocks.push({id: crypto.randomUUID(), type, text: '', ...(type === 'table' ? {rows: [['', '']]} : {})}));
        rebuildControls(); blocks.lastElementChild?.querySelector('input,textarea')?.focus();
    }));
    for (const [name, step] of [['prev', -1], ['next', 1]]) find(name).addEventListener('click', () => {
        if (!session?.pdf) return;
        session.page = Math.max(1, Math.min(session.pdf.numPages, session.page + step));
        renderPage().catch(() => say('Sayfa önizlemesi açılamadı. Orijinalini indirebilirsiniz.'));
    });
    window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => renderPage().catch(() => say('Önizleme yeniden boyutlandırılamadı.')), 180); });
}


import {DocumentDraft, identifyFormat, templateFor} from './workspace-model.js?v=documents-20261018-all1';

import {SmartFields, createFieldControl, readField} from './smart-fields.js?v=documents-20261018-all1';
import {validateField} from './field-rules.js?v=documents-20261018-all1';
import {buildPdf} from './pdf-output.js?v=documents-20261018-all1';
import {leaseHash, leaseTemplate, renderLease, renderNativeTemplate} from './native-lease.js?v=documents-20261018-all1';
import {inspectPdf} from './pdf-edit.js?v=documents-20261018-all1';
import {pdfToWord} from './word-output.js?v=documents-20261018-all2';

const dialog = document.getElementById('document-workspace');
if (dialog) initialize();

function initialize() {
    const find = name => dialog.querySelector(`[data-workspace-${name}]`);
    const form = find('fields'), blocks = find('blocks');
    const canvas = dialog.querySelector('canvas'), context = canvas.getContext('2d');
    const modes = [...dialog.querySelectorAll('[data-workspace-mode]')];
    const labels = {paragraph: 'Paragraf', article: 'Madde', note: 'Açıklama', dateAmount: 'Tarih / tutar', table: 'Tablo', signature: 'İmza alanı', field: 'Bilgi alanı'};
    const smart = new SmartFields(dialog.querySelector('.dw-controls'), find('voice-status'));
    let session = null, generation = 0, renderTask = null, resizeTimer;
    let baseCanvas = null, viewport = null, outputTimer, outputVersion = 0;
    dialog.dataset.tab = "form";
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
        smart.cancel();
        form.replaceChildren(); blocks.replaceChildren();
        const template = session.template;
        find('generate').disabled = !session.pdf || (session.mode === 'fill' && !template);
        if (!session.pdf) find('output-status').textContent = 'PDF ön izlemesi ve doğrulanmış şablon hazır olduğunda çıktı açılır. DOCX dönüşümü desteklenmiyor; orijinal dosyayı indirebilirsiniz.';
        find('fill-notice').textContent = template
            ? 'Alanlar gerçek belgeye işlenir. Kısa alanlarda metni sığacak şekilde yazın; uzun açıklamalar için düzenleme modundaki ek içerikleri kullanın.'
            : 'Bu belgenin alan eşlemesi henüz hazır değil. Doldurma kullanılamıyor; orijinalini indirebilirsiniz.';
        if (session.nativeLease) find('fill-notice').textContent = 'Gerçek Word şablonunun tablo alanları ile banka, şube, hesap, mahkeme ve tarih bilgileri doldurulur. Bilgiler dönüşüm için sunucuya iletilir; geçici dosyalar işlem sonunda temizlenir. İmza alanları elle imzalanmak üzere boş kalır.';
        if (session.nativeTemplate) find('fill-notice').textContent = 'Alanlar özgün DOCX şablonuna işlenir. Bilgiler dönüşüm için sunucuya iletilir; geçici dosyalar işlem sonunda temizlenir. Uzun metin Word sayfa akışını değiştirebilir.';
        if (template) for (const field of template.fields) {
            const label = document.createElement('label'); label.className = 'dw-field'; label.textContent = field.label;
            const input = createFieldControl(field.kind, field.lines > 1 || field.positions?.some(p => (p.lines || 1) > 1 || (p.height || 0) > 25));
            input.readOnly = !!field.locked; input.required = !!field.required;
            if (field.microphone === false) input.dataset.voiceDisabled = 'true';
            if (field.country) input.dataset.country = field.country; if (field.currency) input.dataset.currency = field.currency;
            input.name = field.key; input.value = session.drafts.fill.state.values[field.key] || '';
            input.addEventListener('input', () => {
                session.drafts.fill.change(state => { state.values[field.key] = input.value; });
                history(); paintValues();
            });
            label.append(input); form.append(label);
        }
        for (const block of session.drafts.edit.state.blocks) renderBlock(block);
        renderOriginalText();
        smart.refresh();
        history();
    }
    function renderOriginalText() {
        const target = find('original-text'); if (!target) return;
        target.replaceChildren();
        const page = session.analyses?.[session.page - 1];
        if (!page) return;
        const title = document.createElement('p'); title.textContent = `Sayfa ${session.page}: Özgün metinler. Boş bıraktığınız metin çalışma kopyasından silinir.`; target.append(title);
        for (const item of page.items) {
            const key = `${session.page}:${item.id}`;
            const label = document.createElement('label'); label.className = 'dw-field'; label.textContent = item.text;
            const input = createFieldControl('text'); input.value = session.drafts.edit.state.edits?.[key] ?? item.text;
            input.disabled = !item.editable; input.setAttribute('aria-label', `Özgün metin: ${item.text}`);
            input.addEventListener('input', () => {
                session.drafts.edit.change(state => { state.edits ||= {}; if (input.value === item.text) delete state.edits[key]; else state.edits[key] = input.value; });
                history(); paintValues();
            });
            label.append(input); target.append(label);
        }
        smart.refresh();
    }
    function editBlock(id, update) {
        session.drafts.edit.change(state => update(state.blocks.find(block => block.id === id)));
        history(); paintValues();
    }
    function renderBlock(block) {
        const section = document.createElement('section'); section.className = 'dw-block';
        const heading = document.createElement('div'); heading.className = 'dw-block-heading';
        const title = document.createElement('strong'); title.textContent = labels[block.type];
        heading.append(title, button(`${labels[block.type]} kaldır`, () => {
            session.drafts.edit.change(state => { state.blocks = state.blocks.filter(item => item.id !== block.id); });
            rebuildControls(); paintValues();
        }));
        section.append(heading);
        function field(labelText, key, multiline = false, kind = 'text') {
            const label = document.createElement('label'); label.className = 'dw-field'; label.textContent = labelText;
            const input = createFieldControl(kind, multiline);
            const metadata = block.fieldData?.[key];
            if (metadata?.country) input.dataset.country = metadata.country;
            if (metadata?.currency) input.dataset.currency = metadata.currency;
            input.value = metadata?.raw ?? block[key] ?? '';
            input.addEventListener('input', () => editBlock(block.id, target => {
                const data = readField(input);
                // Canonical data is separate from the transient, editable display buffer.
                target[key] = ['phone', 'date', 'amount'].includes(kind) ? (data.canonical ?? '') : input.value;
                target.fieldData ||= {}; target.fieldData[key] = data;
            }));
            label.append(input); section.append(label);
        }
        if (block.type === 'field') {
            const select = document.createElement('select'); select.className = 'dw-field-option'; select.setAttribute('aria-label', 'Bilgi alanının türü');
            const kinds = {name: 'Ad soyad', identity: 'T.C. kimlik numarası', phone: 'Telefon', email: 'E-posta', address: 'Adres', city: 'İl', district: 'İlçe', neighborhood: 'Mahalle', date: 'Tarih', amount: 'Tutar', text: 'Serbest metin'};
            for (const [value, text] of Object.entries(kinds)) { const option = document.createElement('option'); option.value = value; option.textContent = text; select.append(option); }
            select.value = block.kind || 'text';
            select.addEventListener('change', () => { editBlock(block.id, target => { target.kind = select.value; target.text = ''; target.fieldData = {}; }); rebuildControls(); }); section.append(select);
            field(kinds[block.kind || 'text'], 'text', false, block.kind || 'text');
        } else if (block.type === 'table') {
            const scroll = document.createElement('div'); scroll.className = 'dw-table-scroll';
            const table = document.createElement('table'); table.setAttribute('aria-label', 'Çalışma kopyasına eklenen tablo');
            const body = document.createElement('tbody');
            block.rows.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');
                row.forEach((value, columnIndex) => {
                    const td = document.createElement('td'), input = createFieldControl('text');
                    input.value = value;
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
            field('Tarih', 'date', false, 'date'); field('Tutar', 'amount', false, 'amount'); field('Açıklama', 'text', true, 'description');
        } else if (block.type === 'signature') {
            field('İmzalayacak kişinin adı / unvanı', 'text', false, 'name');
            const note = document.createElement('p'); note.textContent = 'İmza için boş alan. Elektronik imza oluşturulmaz.'; section.append(note);
        } else field(labels[block.type], 'text', true, {note: 'description', paragraph: 'paragraph', article: 'article'}[block.type] || 'text');
        blocks.append(section);
    }
    function outputButtons(ready) {
        for (const name of ['pdf', 'print', 'share']) find(name).disabled = !ready;
        find('word').disabled = !ready;
        find('word').textContent = 'Word İndir';
    }
    function paintValues() {
        if (!session?.pdf) return;
        for (const input of form.querySelectorAll('input,textarea')) { const f = session.template?.fields.find(f => f.key === input.name); if (f) { f.country = input.dataset.country; f.currency = input.dataset.currency; } }
        const key = JSON.stringify([session.mode, draft().state, session.template?.fields.map(f => [f.country, f.currency])]);
        if (key === session.previewKey) return;
        session.previewKey = key; session.output = null; outputButtons(false);
        clearTimeout(outputTimer); const version = ++outputVersion;
        outputTimer = setTimeout(() => updateOutput(version, false), session.nativeLease || session.nativeTemplate ? 1200 : 250);
    }
    async function updateOutput(version, explicit) {
        const current = session; if (!current?.pdf) return;
        if(current.nativeBusy) { clearTimeout(outputTimer); outputTimer=setTimeout(()=>updateOutput(version,explicit),500); return; }
        try {
            if (current.mode === 'fill' && !current.template) throw new Error('Alan eşlemesi hazır değil; doldurulmuş çıktı oluşturulamaz.');
            let bytes;
            if((current.nativeLease || current.nativeTemplate) && current.mode === 'fill') {
                current.nativeBusy=true;
                const values=structuredClone(draft().state.values);
                const result=current.nativeTemplate ? await renderNativeTemplate(dialog,current.nativeTemplate,values,current.abort.signal) : await renderLease(dialog,values,current.abort.signal);
                bytes=result.pdf;
                if(current===session && version===outputVersion) current.wordOutput=result.docx;
            } else bytes = await buildPdf(current.source, current.template, structuredClone(draft().state), current.mode, current.analyses);
            if (current !== session || version !== outputVersion) return;
            const pdfBase = new URL(dialog.dataset.pdfBase, location.href);
            const pdfjs = await import(new URL('pdf.min.js', pdfBase).href);
            const loading = pdfjs.getDocument({data: bytes.slice(), isEvalSupported: false, enableXfa: false, standardFontDataUrl: new URL('standard_fonts/', pdfBase).href});
            const pdf = await loading.promise;
            if (current !== session || version !== outputVersion) { await loading.destroy(); return; }
            if(current.nativeLease && current.mode==='fill') {
                try { await verifyLeaseLayout(current.baselinePdf,pdf); }
                catch(error) { await loading.destroy(); throw error; }
            }
            renderTask?.cancel(); renderTask = null;
            const old = current.outputTask; current.outputTask = loading; current.pdf = pdf;
            current.page = Math.min(current.page, pdf.numPages); current.output = bytes;
            find('paper').hidden = false;
            await renderPage(); if (old) await old.destroy();
            if (explicit) { outputButtons(true); find('output-status').textContent = 'Belge hazır. Ön izleme ile indirilen PDF aynı dosyadır.'; }
            say(current.nativeLease || current.nativeTemplate ? 'Gerçek DOCX şablonundan ön izleme hazır. Geçici dönüşüm dosyaları temizlendi.' : 'Canlı ön izleme: orijinal sayfalar ve çalışma kopyanız. Bilgiler sunucuya gönderilmez.');
        } catch (error) {
            if (current !== session || version !== outputVersion) return;
            current.output = null; outputButtons(false); find('output-status').textContent = error.message;
            if (current.template || current.mode === 'edit') { find('paper').hidden = true; say(error.message); }
        } finally { current.nativeBusy=false; }
    }
    async function verifyLeaseLayout(original,output) {
        if(original.numPages!==output.numPages) throw new Error('Metin orijinal sayfa düzenini aşıyor. Alanları kısaltın.');
        for(let pageNumber=1;pageNumber<=original.numPages;pageNumber++) {
            const baseline=await original.getPage(pageNumber),candidate=await output.getPage(pageNumber);
            const a=await baseline.getTextContent(),b=await candidate.getTextContent();
            for(const item of a.items.filter(item=>item.str.trim())) {
                if(!b.items.some(next=>next.str===item.str && Math.abs(next.transform[4]-item.transform[4])<.6 && Math.abs(next.transform[5]-item.transform[5])<.6)) throw new Error('Metin tablo veya sabit satırları kaydırıyor. Alanları kısaltın.');
            }
        }
    }
    function selectTab(tab) {
        dialog.dataset.tab = tab;
        dialog.querySelectorAll('[data-workspace-tab]').forEach(node => node.setAttribute('aria-selected', String(node.dataset.workspaceTab === tab)));
        if (tab === 'preview') requestAnimationFrame(() => renderPage().catch(() => say('Ön izleme açılamadı.')));
    }
    function downloadOutput() {
        if (!session?.output) return;
        const url = URL.createObjectURL(new Blob([session.output], {type: 'application/pdf'}));
        const a = document.createElement('a'); a.href = url; a.download = 'belge.pdf'; a.click();
        setTimeout(() => URL.revokeObjectURL(url), 30000);
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
        const nextViewport = page.getViewport({scale: width / initial.width * Math.min(devicePixelRatio || 1, 2) * (current.zoom || 1)});
        const buffer = document.createElement('canvas'); buffer.width = Math.ceil(nextViewport.width); buffer.height = Math.ceil(nextViewport.height);
        const task = page.render({canvasContext: buffer.getContext('2d'), viewport: nextViewport}); renderTask = task;
        try { await task.promise; } catch (error) { if (error.name === 'RenderingCancelledException') return; throw error; }
        if (token !== generation || current !== session || pageNumber !== current.page || renderTask !== task) return;
        renderTask = null; baseCanvas = buffer; viewport = nextViewport;
        canvas.width = buffer.width; canvas.height = buffer.height; context.drawImage(buffer, 0, 0);
        find('paper').style.width = `${width * (current.zoom || 1)}px`;
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
        dialog.showModal(); selectTab('form'); outputButtons(false); setMode(detail.mode); say('Orijinalden ayrı çalışma kopyası hazırlanıyor…');
        try {
            const response = await fetch(url.href, {signal: session.abort.signal, credentials: 'same-origin', redirect: 'error'});
            if (!response.ok) throw new Error('download');
            const declaredSize = Number(response.headers.get('Content-Length'));
            if (declaredSize > 25 * 1024 * 1024) throw new Error('size');
            let bytes = new Uint8Array(await response.arrayBuffer());
            if (bytes.byteLength > 25 * 1024 * 1024) throw new Error('size');
            if (token !== generation) return;
            // Keep the source bytes immutable; PDF.js receives a separate buffer.
            session.source = bytes;
            let format = identifyFormat(bytes);
            let templateOverride=null;
            if (format==='ooxml') {
                const identification=await templateFor(bytes,url.href);
                const catalog=JSON.parse(document.getElementById('document-template-catalog')?.textContent||'[]');
                const revision=catalog.find(r=>r.format==='docx' && r.hash===identification.hash && new URL(r.url,location.href).href===url.href);
                if(revision) {
                    session.nativeTemplate=revision;
                    const previewResponse=await fetch(revision.previewUrl,{signal:session.abort.signal,credentials:'same-origin'});
                    if(!previewResponse.ok) throw new Error('DOCX ön izlemesi yüklenemedi.');
                    bytes=new Uint8Array(await previewResponse.arrayBuffer());
                    if((await templateFor(bytes)).hash!==revision.previewHash) throw new Error('DOCX ön izlemesi doğrulanamadı.');
                    session.source=bytes;format='pdf';
                    templateOverride={id:revision.id,fields:revision.mapping.fields};
                }
            }
            if(format==='ooxml' && (await templateFor(bytes,url.href)).hash===leaseHash) {
                session.nativeLease=true;
                say('Gerçek Word şablonu PDF ön izlemesine dönüştürülüyor…');
                const rendered=await renderLease(dialog,{},session.abort.signal);
                if(token!==generation) return;
                session.wordOutput=rendered.docx; bytes=rendered.pdf; session.source=bytes;
                format='pdf'; templateOverride=structuredClone(leaseTemplate);
            }
            if (format !== 'pdf') {
                find('preview-message').textContent = format === 'ooxml'
                    ? 'Bu Office/ZIP belgesinde (DOCX dahil) sayfa düzenini koruyan tarayıcı önizlemesi ve şablon düzenlemesi henüz desteklenmiyor. Orijinalini indirebilir, ayrı çalışma kopyasına ek içerik hazırlayabilirsiniz.'
                    : 'Bu dosya türünde önizleme ve şablon düzenlemesi desteklenmiyor. Orijinalini indirebilirsiniz.';
                say('Doldurma kapalı. Ek içerikler yalnız ayrı çalışma kopyasında hazırlanır.');
                return;
            }
            const template = templateOverride || (await templateFor(bytes, url.href)).template;
            const pdfBase = new URL(dialog.dataset.pdfBase, location.href);
            const pdfjs = await import(new URL('pdf.min.js', pdfBase).href);
            if (token !== generation) return;
            pdfjs.GlobalWorkerOptions.workerSrc = new URL('pdf.worker.min.js', pdfBase).href;
            session.loadingTask = pdfjs.getDocument({data: bytes.slice(), isEvalSupported: false, enableXfa: false, standardFontDataUrl: new URL('standard_fonts/', pdfBase).href, cMapUrl: new URL('cmaps/', pdfBase).href, cMapPacked: true, wasmUrl: new URL('wasm/', pdfBase).href});
            const pdf = await session.loadingTask.promise;
            if (token !== generation) return;
            session.pdf = pdf;
            session.analyses = await inspectPdf(pdf, pdfjs.OPS);
            if (token !== generation) return;
            if(session.nativeLease || session.nativeTemplate) {
                session.baselinePdf=pdf;
                template.pages=pdf.numPages;
                template.pageSizes=[];
                for(let page=1;page<=pdf.numPages;page++) {const size=(await pdf.getPage(page)).getViewport({scale:1});template.pageSizes.push([size.width,size.height]);}
                template.size=template.pageSizes[0];
            }
            if (template && template.pages === pdf.numPages) {
                let matches = true;
                for (let index = 0; index < pdf.numPages; index++) {
                    const page = await pdf.getPage(index + 1);
                    if (token !== generation) return;
                    const size = page.getViewport({scale: 1}), expected = template.pageSizes?.[index] || template.size;
                    if (Math.abs(size.width - expected[0]) >= .1 || Math.abs(size.height - expected[1]) >= .1) matches = false;
                }
                if (matches) session.template = structuredClone(template);
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
        smart.cancel();
        ++generation; ++outputVersion; clearTimeout(outputTimer); session?.outputTask?.destroy(); session?.abort.abort(); renderTask?.cancel(); renderTask = null;
        const trigger = session?.trigger;
        const task = session?.loadingTask;
        if (task && typeof task.destroy === 'function') Promise.resolve(task.destroy()).catch(() => {});
        session = null; baseCanvas = null; viewport = null; form.replaceChildren(); blocks.replaceChildren();
        find('original-text')?.replaceChildren();
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
        rebuildControls(); paintValues(); blocks.lastElementChild?.querySelector('input,textarea')?.focus();
    }));
    for (const [name, step] of [['prev', -1], ['next', 1]]) find(name).addEventListener('click', () => {
        if (!session?.pdf) return;
        session.page = Math.max(1, Math.min(session.pdf.numPages, session.page + step));
        renderOriginalText();
        renderPage().catch(() => say('Sayfa önizlemesi açılamadı. Orijinalini indirebilirsiniz.'));
    });
    dialog.querySelectorAll('[data-workspace-tab]').forEach(node => node.addEventListener('click', () => selectTab(node.dataset.workspaceTab)));
    find('return').addEventListener('click', () => selectTab('form'));
    find('generate').addEventListener('click', () => { clearTimeout(outputTimer); updateOutput(++outputVersion, true); });
    find('pdf').addEventListener('click', downloadOutput);
    find('word').addEventListener('click',async()=>{
        if(!session?.output) return;
        const current=session,version=outputVersion;
        find('word').disabled=true;
        try {
            let bytes=current.mode==='fill'&&(current.nativeLease||current.nativeTemplate)?current.wordOutput:null;
            if(!bytes) {
                find('output-status').textContent='Düzenlenebilir Word hazırlanıyor. PDF’den aktarılan metinlerin satırları Word’de farklı görünebilir; indirdikten sonra kontrol edin.';
                const pdfjs=await import(new URL('pdf.min.js',new URL(dialog.dataset.pdfBase,location.href)).href);
                bytes=await pdfToWord(current.output,pdfjs);
            }
            if(current!==session||version!==outputVersion)return;
            const url=URL.createObjectURL(new Blob([bytes],{type:'application/vnd.openxmlformats-officedocument.wordprocessingml.document'}));
            const anchor=document.createElement('a');anchor.href=url;anchor.download='belge.docx';anchor.click();
            setTimeout(()=>URL.revokeObjectURL(url),30000);
            find('output-status').textContent='Word dosyası indirildi. PDF’den aktarılan metinlerin yerleşimini Word’de kontrol edin.';
        }catch(error){if(current===session)find('output-status').textContent=error.message||'Word oluşturulamadı.';}
        finally{if(current===session&&version===outputVersion)find('word').disabled=false;}
    });
    find('share').addEventListener('click', async () => {
        if (!session?.output) return;
        const file = new File([session.output], 'belge.pdf', {type: 'application/pdf'});
        if (!navigator.canShare?.({files: [file]}) || !navigator.share) {
            find('output-status').textContent = 'Bu tarayıcı dosya paylaşamıyor. PDF İndir ile kaydedip istediğiniz uygulamaya ekleyin.'; return;
        }
        try { await navigator.share({files: [file], title: 'Belge'}); find('output-status').textContent = 'Dosya paylaşım menüsüne aktarıldı.'; }
        catch (error) { find('output-status').textContent = error.name === 'AbortError' ? 'Paylaşım iptal edildi.' : 'Dosya paylaşılamadı. PDF İndir ile devam edin.'; }
    });
    find('print').addEventListener('click', () => {
        if (!session?.output) return;
        const url = URL.createObjectURL(new Blob([session.output], {type: 'application/pdf'}));
        find('output-status').textContent = 'Yazdırma isteği hazırlanıyor. Pencere açılmazsa PDF’yi indirip cihazınızdan yazdırın.';
        const frame = document.createElement('iframe'); frame.hidden = true; frame.src = url;
        frame.onload = () => { try { frame.contentWindow.focus(); frame.contentWindow.print(); } catch { find('output-status').textContent = 'Tarayıcı doğrudan yazdırmayı desteklemiyor. PDF’yi indirip yazdırın.'; } };
        document.body.append(frame);
        setTimeout(() => { frame.remove(); URL.revokeObjectURL(url); }, 60000);
    });
    for (const [name, delta] of [['zoom-out', -.25], ['zoom-in', .25]]) find(name).addEventListener('click', () => {
        if (!session?.pdf) return; session.zoom = Math.max(.5, Math.min(2.5, (session.zoom || 1) + delta));
        find('zoom').textContent = Math.round(session.zoom * 100) + '%'; renderPage().catch(() => say('Yakınlaştırma başarısız.'));
    });
    window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => renderPage().catch(() => say('Önizleme yeniden boyutlandırılamadı.')), 180); });
}


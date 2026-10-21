import {buildPdf} from './pdf-output.js?v=documents-20261021-v1';

export async function mount(root, wire) {
    if (root.dataset.mounted) return; root.dataset.mounted = 'true';
    const get = name => root.querySelector(`[data-template-${name}]`);
    const canvas = get('canvas'), ctx = canvas.getContext('2d'), status = text => { get('status').textContent = text; };
    let source, original, pdf, page = 1, viewport, background, mapping = {pageSizes: [], fields: []}, selected = -1, drawing = false, repeat = false, start, renderToken = 0, format;
    const pdfjs = await import('/vendor/pdfjs/pdf.min.js'); pdfjs.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.js';
    const sync = () => wire.set('mapping', JSON.stringify(mapping), false);
    const button = (text, action) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = text; b.onclick = action; return b; };
    function paint() {
        if (!background) return; ctx.drawImage(background, 0, 0);
        mapping.fields.forEach((f, index) => f.positions.filter(p => p.page === page).forEach(p => {
            const [x, y] = viewport.convertToViewportPoint(p.x, p.y + p.fontSize);
            ctx.strokeStyle = index === selected ? '#ea580c' : '#1d4ed8'; ctx.lineWidth = 2;
            ctx.strokeRect(x, y, p.width * viewport.scale, p.height * viewport.scale);
            ctx.font = '12px sans-serif'; ctx.fillStyle = '#172554'; ctx.fillText(f.label, x, Math.max(12, y - 3));
        }));
    }
    async function render() {
        if (!pdf) return;
        const token = ++renderToken, p = await pdf.getPage(page);
        const base = p.getViewport({scale: 1});
        const next = p.getViewport({scale: Math.min(1.4, Math.max(250, canvas.parentElement.clientWidth) / base.width)});
        const buffer = document.createElement('canvas'); buffer.width = Math.ceil(next.width); buffer.height = Math.ceil(next.height);
        await p.render({canvasContext: buffer.getContext('2d'), viewport: next}).promise;
        if (token !== renderToken) return;
        viewport = next; background = buffer; canvas.width = buffer.width; canvas.height = buffer.height;
        get('page').textContent = `${page} / ${pdf.numPages}`; paint();
    }
    function fields() {
        const target = get('fields'); target.replaceChildren();
        mapping.fields.forEach((f, index) => {
            const section = document.createElement('section'); section.className = 'dt-field' + (selected === index ? ' active' : '');
            section.append(button(`${f.label} seç`, () => { selected = index; fields(); paint(); }));
            const input = (label, value, update, type = 'text') => {
                const l = document.createElement('label'); l.textContent = label;
                const i = document.createElement('input'); i.type = type;
                if (type === 'checkbox') i.checked = value; else i.value = value;
                i.onchange = () => { update(type === 'checkbox' ? i.checked : type === 'number' ? Number(i.value) : i.value); sync(); paint(); };
                l.append(i); section.append(l);
            };
            input('Alan anahtarı (aynı bilgi için bir anahtar)', f.key, v => { f.key = v; });
            input('Etiket', f.label, v => { f.label = v; });
            input('Seçim önerileri (| ile ayırın; serbest metin korunur)', (f.options || []).join(' | '), v => { f.options = v.split('|').map(s => s.trim()).filter(Boolean); });
            input('Bağlı üst alan anahtarı (İlçe → İl, Mahalle → İlçe)', f.dependsOn || '', v => { if (v.trim()) f.dependsOn = v.trim(); else delete f.dependsOn; });
            if (format === 'docx') input('Bu alanın geçtiği sayfalar (örnek: 1, 3)', (f.pages || []).join(', '), value => {
                f.pages = [...new Set(value.split(',').map(part => Number(part.trim())))];
            });
            const label = document.createElement('label'); label.textContent = 'Alan türü';
            const select = document.createElement('select');
            for (const [value, title] of Object.entries({text:'Metin',name:'Ad soyad',identity:'T.C. kimlik',phone:'Telefon',email:'E-posta',address:'Adres',city:'İl',district:'İlçe',neighborhood:'Mahalle',date:'Tarih',amount:'Tutar',description:'Açıklama'})) {
                const o = document.createElement('option'); o.value = value; o.textContent = title; select.append(o);
            }
            select.value = f.kind; select.onchange = () => { f.kind = select.value; sync(); }; label.append(select); section.append(label);
            input('Zorunlu', f.required, v => { f.required = v; }, 'checkbox');
            input('Kilitli (kullanıcı değiştiremez)', f.locked, v => { f.locked = v; }, 'checkbox');
            input('Mikrofon uygun', f.microphone, v => { f.microphone = v; }, 'checkbox');
            f.positions.forEach((p, position) => {
                for (const [key, title] of Object.entries({page:'Sayfa',x:'X (sol kenar, pt)',y:'Y (alt kenardan ilk satır tabanı, pt)',width:'Genişlik (pt)',height:'Yükseklik (pt)',fontSize:'Yazı boyutu (pt)'})) input(title, p[key], value => { p[key] = value; }, 'number');
                input('Kalın yazı', !!p.bold, value => { p.bold = value; }, 'checkbox');
                input('İtalik yazı', !!p.italic, value => { p.italic = value; }, 'checkbox');
                section.append(button('Bu konumu kaldır', () => { f.positions.splice(position, 1); sync(); fields(); paint(); }));
            });
            section.append(button('Alanı kaldır', () => { mapping.fields.splice(index, 1); selected = -1; sync(); fields(); paint(); })); target.append(section);
        });
    }
    async function load(bytes, savedMapping, docxPreview = null, keys = []) {
        ++renderToken; source = bytes; page = 1; selected = -1; format = new TextDecoder().decode(bytes.slice(0, 5)) === '%PDF-' ? 'pdf' : 'docx';
        if (original) await original.destroy(); original = null; pdf = null; background = null; canvas.width = canvas.height = 0;
        get('add').disabled = get('repeat').disabled = format !== 'pdf';
        if (format !== 'pdf') {
            if (!docxPreview) throw new Error('DOCX ön izlemesi oluşturulamadı.');
            bytes = Uint8Array.from(atob(docxPreview), c => c.charCodeAt(0));
            savedMapping ||= {pageSizes:[],fields:keys.map(key=>({key,label:key.replaceAll('_',' '),kind:'text',required:false,locked:false,microphone:true,positions:[]}))};
        }
        original = pdfjs.getDocument({data: bytes.slice(), isEvalSupported: false, enableXfa: false, standardFontDataUrl: '/vendor/pdfjs/standard_fonts/'});
        pdf = await original.promise;
        const hasItems = value => value instanceof Map ? value.size > 0 : !!value && Object.keys(value).length > 0;
        if (hasItems(await pdf.getJSActions()) || hasItems(await pdf.getAttachments())) throw new Error('JavaScript veya dosya eki içeren PDF kabul edilmiyor.');
        const pageSizes = [];
        if (pdf.numPages > 100) throw new Error('En fazla 100 sayfa desteklenir.');
        for (let i = 1; i <= pdf.numPages; i++) { const p = await pdf.getPage(i); if (hasItems(await p.getJSActions())) throw new Error('Sayfa JavaScript içeriyor.'); if (p.rotate % 360) throw new Error('Döndürülmüş PDF sayfası desteklenmiyor.'); const v = p.getViewport({scale: 1}); pageSizes.push([v.width, v.height]); }
        mapping = savedMapping || {pageSizes, fields: []}; mapping.pageSizes = pageSizes;
        if (format === 'docx') {
            const texts = [];
            for (let number = 1; number <= pdf.numPages; number++) texts.push((await (await pdf.getPage(number)).getTextContent()).items.map(item => item.str).join(''));
            for (const field of mapping.fields) {
                if (field.pages?.length) continue;
                const escaped = field.key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const marker = new RegExp(`\\{\\{\\s*${escaped}\\s*\\}\\}`);
                field.pages = texts.flatMap((text, index) => marker.test(text) ? [index + 1] : []);
            }
        }
        sync(); fields(); await render(); status('Alan çizmek için Yeni alan çiz seçin ve sayfada sürükleyin.');
    }
    get('file').onchange = async event => {
        try {
            const file = event.target.files[0]; if (!file) return;
            if (file.size > 12 * 1024 * 1024) throw new Error('En fazla 12 MB yükleyebilirsiniz.');
            await new Promise((resolve, reject) => wire.upload('upload', file, resolve, () => reject(new Error('Yükleme başarısız.'))));
            const bytes = new Uint8Array(await file.arrayBuffer());
            const result = file.name.toLowerCase().endsWith('.docx') ? await wire.previewDocxUpload() : null;
            await load(bytes, null, result?.preview, result?.keys);
            status('Dosya hazır. Eşleyip yeni taslak sürümü kaydedin.');
        } catch (error) { status(error.message); }
    };
    for (const [name, delta] of [['prev', -1], ['next', 1]]) get(name).onclick = () => { if (!pdf) return; page = Math.max(1, Math.min(pdf.numPages, page + delta)); render().catch(e => status(e.message)); };
    get('add').onclick = () => { drawing = true; canvas.style.touchAction = 'none'; repeat = false; status('Yeni alan için sol üstten sağ alta sürükleyin.'); };
    get('repeat').onclick = () => { if (selected < 0) { status('Önce bir alan seçin.'); return; } drawing = true; canvas.style.touchAction = 'none'; repeat = true; status('Aynı bilginin ikinci konumu için sürükleyin.'); };
    const point = e => { const r = canvas.getBoundingClientRect(); return [(e.clientX - r.left) * canvas.width / r.width, (e.clientY - r.top) * canvas.height / r.height]; };
    canvas.onpointerdown = e => { if (!drawing || !viewport) return; e.preventDefault(); start = point(e); canvas.setPointerCapture(e.pointerId); };
    canvas.onpointerup = e => {
        if (!start || !drawing) return;
        const end = point(e), a = viewport.convertToPdfPoint(...start), b = viewport.convertToPdfPoint(...end); start = null; drawing = false; canvas.style.touchAction = 'auto';
        const x = Math.min(a[0], b[0]), top = Math.max(a[1], b[1]), width = Math.abs(a[0] - b[0]), height = Math.abs(a[1] - b[1]);
        if (width < 15 || height < 14) { status('Alan en az 15 × 14 pt olmalı.'); return; }
        const p = {page, x: +x.toFixed(2), y: +(top - 11).toFixed(2), width: +width.toFixed(2), height: +(height - 11).toFixed(2), fontSize: 11, fontFamily: 'LiberationSans'};
        if (repeat && mapping.fields[selected]) mapping.fields[selected].positions.push(p);
        else { mapping.fields.push({key: 'field_' + crypto.randomUUID().replaceAll('-', '').slice(0, 12), label: 'Yeni alan', kind: 'text', required: false, locked: false, microphone: true, positions: [p]}); selected = mapping.fields.length - 1; }
        sync(); fields(); paint();
    };
    root.addEventListener('click', async event => {
        try {
            const version = event.target.closest('[data-template-version]');
            if (version) { const r = await wire.selectVersion(version.dataset.templateId, Number(version.dataset.templateVersion)); await load(Uint8Array.from(atob(r.bytes), c => c.charCodeAt(0)), r.mapping, r.preview); }
            if (event.target.closest('[data-template-new]')) { await wire.newTemplate(); source = null; mapping = {pageSizes: [], fields: []}; pdf = null; background = null; fields(); canvas.width = canvas.height = 0; get('file').value = ''; status('Yeni dosya seçin.'); }
            if (event.target.closest('[data-template-test]')) {
                if (!source) throw new Error('Önce PDF / DOCX seçin ve taslak sürümü kaydedin.');
                await sync();
                const values = {};
                const samples = {identity:'10000000146',phone:'+905301234567',email:'ornek@example.com',date:'18.10.2026',amount:'1250,50',address:'Örnek Mahallesi\nİstanbul',name:'Çağrı Şen'};
                mapping.fields.forEach(f => { values[f.key] = samples[f.kind] || 'Örnek bilgi'; });
                const bytes = format === 'docx' ? Uint8Array.from(atob((await wire.testDocxVersion(values)).preview), c=>c.charCodeAt(0)) : await buildPdf(source, {fields: mapping.fields}, {values}, 'fill');
                const task = pdfjs.getDocument({data: bytes, isEvalSupported: false, standardFontDataUrl: '/vendor/pdfjs/standard_fonts/'});
                const oldPdf = pdf; pdf = await task.promise; page = 1; await render(); pdf = oldPdf;
                const hash = [...new Uint8Array(await crypto.subtle.digest('SHA-256', source))].map(v => v.toString(16).padStart(2, '0')).join('');
                await wire.confirmTest(hash); await task.destroy(); status('Örnek verilerle PDF oluştu. Görsel yerleşimi kontrol edin; kaydedilen sürümü yayınlayabilirsiniz.');
            }
        } catch (error) { status(error.message || 'İşlem tamamlanamadı.'); }
    });
    get('file').disabled = false; root.dataset.editorReady = 'true';
}

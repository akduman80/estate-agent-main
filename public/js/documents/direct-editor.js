import {SmartFields, createFieldControl} from './smart-fields.js?v=documents-20261018-all1';
import {choiceValues, clearDependentValues, composeLocationValues} from './field-choices.js?v=documents-20261022';
import {loadAddresses} from './address-data.js?v=documents-20261022';

// PDF points remain the single coordinate system for editing and output.
export function fieldRectangle(position, height) {
    const size = position.fontSize || 11;
    return {left: position.x, top: height - position.y - size,
        width: position.width, height: Math.max(size * 1.25, position.height || (position.lines || 1) * size * 1.15)};
}

export class DirectEditor {
    constructor(root, status, changed) {
        this.root = root; this.changed = changed; this.version = 0;
        this.smart = new SmartFields(root, status);
    }
    clear() { this.version++; this.smart.cancel(); this.root.replaceChildren(); this.session = null; }
    async render(session) {
        const version = ++this.version;
        try { await loadAddresses(); } catch { /* Manual entry always remains available. */ }
        if (this.session === session && this.root.children.length === session.pdf.numPages) {
            this.sync(session);
            return; // Keep selection, keyboard and microphone stable while typing.
        }
        this.smart.cancel(); this.root.replaceChildren(); this.session = session;
        for (let number = 1; number <= session.pdf.numPages; number++) {
            const page = await session.pdf.getPage(number);
            if (version !== this.version) return;
            const viewport = page.getViewport({scale: 1});
            const sheet = document.createElement('section'); sheet.className = 'dw-direct-page';
            sheet.setAttribute('aria-label', `Sayfa ${number}`);
            sheet.style.width = `${viewport.width}px`; sheet.style.height = `${viewport.height}px`;
            const canvas = document.createElement('canvas');
            canvas.width = Math.ceil(viewport.width * 1.5); canvas.height = Math.ceil(viewport.height * 1.5);
            sheet.append(canvas); this.root.append(sheet);
            sheet.addEventListener('dblclick', event => {
                if (event.target !== canvas) return;
                const bounds = sheet.getBoundingClientRect();
                const x = (event.clientX-bounds.left)*viewport.width/bounds.width;
                const top = (event.clientY-bounds.top)*viewport.height/bounds.height;
                const width = Math.min(400, viewport.width-x-30);
                const occupied = [...sheet.querySelectorAll('label')].map(node=>({x:parseFloat(node.style.left),top:parseFloat(node.style.top),width:parseFloat(node.style.width),height:parseFloat(node.style.height)}));
                if (width<60 || top>viewport.height-50 || occupied.some(r=>x<r.x+r.width&&x+width>r.x&&top<r.top+r.height&&top+20>r.top)) return;
                const next = occupied.filter(r=>r.top>top&&x<r.x+r.width&&x+width>r.x).map(r=>r.top);
                const height = Math.min(viewport.height-30,...next)-top-4;
                if(height<20)return;
                const annotation={id:crypto.randomUUID(),page:number,x,y:viewport.height-top-11,width,height,fontSize:11,text:''};
                session.drafts.fill.change(state=>{state.annotations ||= [];state.annotations.push(annotation);});
                this.annotation(sheet,session,annotation,viewport.height).focus(); this.changed();
            });
            await page.render({canvasContext: canvas.getContext('2d'), viewport: page.getViewport({scale: 1.5})}).promise;
            if (version !== this.version) return;
            for (const field of session.template?.fields || []) {
                const helpers=session.template.fields.filter(f=>f.compositeTarget===field.key);
                if(helpers.length)continue;
                let positions=field.positions || [];
                if(field.compositeTarget){
                    const target=session.template.fields.find(f=>f.key===field.compositeTarget);
                    const peers=session.template.fields.filter(f=>f.compositeTarget===field.compositeTarget);
                    const index=peers.indexOf(field);
                    positions=(target?.positions || []).map(p=>({...p,x:p.x+index*p.width/peers.length,width:p.width/peers.length-2}));
                }
                for (const position of positions) {
                    if (position.page !== number) continue;
                    this.control(sheet, session, field, position, viewport.height);
                }
            }
            // Original text is edited in place. No separate form or copied legal text.
            for (const item of session.analyses?.[number - 1]?.items || []) {
                if (!item.editable || !item.text.trim()) continue;
                const key = `${number}:${item.id}`;
                const field = {key, label: item.text, kind: 'text', original: true};
                this.control(sheet, session, field, {x:item.x,y:item.y,width:Math.max(item.width,12),height:item.fontSize*1.2,fontSize:item.fontSize}, viewport.height);
            }
            for(const annotation of session.drafts.fill.state.annotations || []) if(annotation.page===number)this.annotation(sheet,session,annotation,viewport.height);
        }
        this.smart.refresh(); this.sync(session);
    }
    annotation(sheet, session, annotation, height) {
        const label=document.createElement('label');label.className='dw-direct-field dw-annotation';
        const rect=fieldRectangle(annotation,height);
        Object.assign(label.style,{left:`${rect.left}px`,top:`${rect.top}px`,width:`${rect.width}px`,height:`${rect.height}px`,fontSize:`${annotation.fontSize}px`});
        const input=createFieldControl('paragraph',true);input.value=annotation.text;input.dataset.annotation=annotation.id;
        input.setAttribute('aria-label','Yeni madde veya açıklama');input.title='Yeni madde veya açıklama — Ctrl+B: kalın, Ctrl+I: italik';
        const update=()=>{session.drafts.fill.change(state=>{const item=state.annotations.find(a=>a.id===annotation.id);if(item)item.text=input.value;});this.changed();};
        const toggle=key=>{session.drafts.fill.change(state=>{const item=state.annotations.find(a=>a.id===annotation.id);if(item)item[key]=!item[key];});this.sync(session);this.changed();};
        input.addEventListener('input',update);
        input.addEventListener('keydown',event=>{if(!(event.ctrlKey||event.metaKey)||!['b','i'].includes(event.key.toLowerCase()))return;event.preventDefault();toggle(event.key.toLowerCase()==='b'?'bold':'italic');});
        label.append(input);
        const formats=document.createElement('span');formats.className='dw-inline-formats';
        for(const [key,text]of [['bold','Kalın'],['italic','İtalik']]){const button=document.createElement('button');button.type='button';button.textContent=text;button.dataset.noteStyle=key;button.setAttribute('aria-pressed',String(!!annotation[key]));button.addEventListener('click',()=>toggle(key));formats.append(button);}
        label.append(formats);sheet.append(label);this.smart.refresh();return input;
    }
    control(sheet, session, field, position, pageHeight) {
        const rect = fieldRectangle(position, pageHeight);
        const label = document.createElement('label'); label.className = 'dw-direct-field';
        if (field.original) label.classList.add('dw-direct-text');
        Object.assign(label.style, {left:`${rect.left}px`,top:`${rect.top}px`,width:`${rect.width}px`,height:`${rect.height}px`,fontSize:`${position.fontSize || 11}px`});
        const input = createFieldControl(field.kind, (position.lines || 1) > 1);
        input.name = field.key; input.dataset.original = String(!!field.original);
        input.setAttribute('aria-label', field.label); input.title = field.label;
        if(field.compositeTarget)input.placeholder=field.label.replace(' seçimi','').replace(' (manuel)','');
        if(field.outputPrefix)input.placeholder=field.label;
        if(['city','district','neighborhood'].includes(field.kind))input.title+=' — TurkiyeAPI yerel önerileri, 22.10.2026; resmî adres doğrulaması değildir. Elle değiştirilebilir.';
        input.readOnly = !!field.locked;
        input.value = field.original ? session.drafts.fill.state.edits[field.key] ?? field.label : session.drafts.fill.state.values[field.key] || '';
        const updateChoices = () => {
            const options = choiceValues(field, session.template.fields, session.drafts.fill.state.values);
            if (!options.length) return;
            const list = document.createElement('datalist'); list.id = `direct-${sheet.getAttribute('aria-label').replace(/\s/g,'')}-${field.key}-${Math.random().toString(36).slice(2)}`;
            for (const value of options) { const option = document.createElement('option'); option.value = value; list.append(option); }
            label.querySelector('datalist')?.remove(); label.append(list); input.setAttribute('list',list.id);
        };
        input.addEventListener('focus', () => { updateChoices(); input.scrollIntoView({block:'nearest',inline:'nearest'}); });
        input.addEventListener('input', () => {
            session.drafts.fill.change(state => {
                if (field.original) { if (input.value === field.label) delete state.edits[field.key]; else state.edits[field.key] = input.value; }
                else {
                    if (state.values[field.key] !== input.value) clearDependentValues(session.template.fields,state.values,field.key);
                    state.values[field.key] = input.value;
                    composeLocationValues(session.template.fields,state.values,field.key);
                }
            });
            this.sync(session, input); this.changed();
        });
        label.append(input); sheet.append(label);
    }
    sync(session, active) {
        for (const input of this.root.querySelectorAll('input,textarea')) {
            if(input.dataset.annotation){
                const item=session.drafts.fill.state.annotations?.find(a=>a.id===input.dataset.annotation);
                input.closest('label').hidden=!item;
                if(item){if(input!==document.activeElement)input.value=item.text;input.style.fontWeight=item.bold?'700':'400';input.style.fontStyle=item.italic?'italic':'normal';for(const button of input.closest('label').querySelectorAll('[data-note-style]'))button.setAttribute('aria-pressed',String(!!item[button.dataset.noteStyle]));}
                continue;
            }
            input.closest('label').classList.toggle('dw-has-edit', input.dataset.original === 'true' && Object.hasOwn(session.drafts.fill.state.edits,input.name));
            if (input === active || input === document.activeElement) continue;
            if (input.dataset.original === 'true') {
                input.value = session.drafts.fill.state.edits[input.name] ?? input.title;
            } else input.value = session.drafts.fill.state.values[input.name] || '';
        }
    }
}

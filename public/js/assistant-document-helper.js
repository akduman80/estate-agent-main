const make=(tag,text)=>{const e=document.createElement(tag);if(text!==undefined)e.textContent=text;return e;};
const fold=s=>s.toLocaleLowerCase('tr-TR').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/ı/g,'i').trim();
const aliases={landlord:'Kiraya veren',tenant:'Kiracı',monthlyRent:'Aylık kira',startDate:'Başlangıç tarihi',city:'İl',county:'İlçe',district:'Mahalle'};
export function documentHelper(workspace,fields){
    workspace.querySelector('[data-assistant-preview]')?.remove();
    const box=make('section');box.dataset.assistantPreview='true';box.style.cssText='padding:12px;background:#fff7ed;color:#172554;border:1px solid #f97316;max-height:260px;overflow:auto';
    box.append(make('strong','Asistan belge yardımcısı — bilgiler Google’a gönderilmez'));
    const info=make('p','Belge yüklenince Alanları göster düğmesine basın. Eksik alanlar yazdırmayı engellemez.');
    const show=make('button','Alanları göster');show.type='button';
    const controls=make('div'),status=make('p');status.setAttribute('role','status');let recognition;
    const inputs=()=>[...new Map([...workspace.querySelectorAll('.dw-direct-pages input,.dw-direct-pages textarea')].filter(i=>i.dataset.original==='false'&&!i.disabled&&!i.readOnly).map(i=>[i.name,i])).values()];
    show.onclick=()=>{
        const source=inputs();controls.replaceChildren();if(!source.length){status.textContent='Belgenin yüklenmesini bekleyip yeniden deneyin.';return;}
        const staged=new Map(),bulk=make('textarea');bulk.rows=2;bulk.placeholder='Alan etiketi: değer; diğer alan etiketi: değer';bulk.setAttribute('aria-label','Belge bilgilerini yazın veya söyleyin');bulk.style.width='100%';
        const mic=make('button','Belge bilgilerini sesle yaz');mic.type='button';
        mic.onclick=()=>{const Speech=window.SpeechRecognition||window.webkitSpeechRecognition;if(!Speech){status.textContent='Ses tanıma desteklenmiyor; yazılı giriş kullanabilirsiniz.';return;}if(!confirm('Sesiniz tarayıcınızın ses tanıma hizmetine gönderilebilir. Devam edilsin mi?'))return;recognition?.abort();recognition=new Speech();recognition.lang='tr-TR';recognition.onresult=e=>{if(workspace.open)bulk.value=e.results[0][0].transcript;};recognition.onerror=()=>{status.textContent='Mikrofon kullanılamadı. Bilgileri yazabilirsiniz.';};try{recognition.start();}catch{status.textContent='Mikrofon başlatılamadı.';}};
        const parse=make('button','Yazılan bilgileri ön izlemeye al');parse.type='button';controls.append(bulk,mic,parse);
        for(const input of source){
            const title=input.getAttribute('aria-label')||input.name,label=make('label',title),draft=make('input');label.style.cssText='display:block;margin:6px 0';draft.type='text';draft.dataset.assistantField=input.name;draft.value=fields[input.name]??input.value;draft.style.cssText='display:block;width:100%;box-sizing:border-box';
            input.dispatchEvent(new Event('focus'));if(input.getAttribute('list'))draft.setAttribute('list',input.getAttribute('list'));
            label.append(draft);controls.append(label);staged.set(input.name,{draft,title});
        }
        parse.onclick=()=>{let count=0;for(const part of bulk.value.split(/[;\n]/)){const at=part.indexOf(':');if(at<0)continue;const key=fold(part.slice(0,at)),value=part.slice(at+1).trim();for(const [name,item]of staged)if(key===fold(item.title)||key===fold(aliases[name]||name)){item.draft.value=value;count++;break;}}status.textContent=`${count} alan ön izlemeye alındı. Kontrol ettikten sonra aktarın.`;};
        const apply=make('button','Kontrol ettim, dolu alanları belgeye aktar');apply.type='button';
        apply.onclick=()=>{let count=0;for(const [name,item]of staged){if(!item.draft.value.trim())continue;const input=inputs().find(i=>i.name===name);if(!input)continue;input.value=item.draft.value;input.dispatchEvent(new Event('input',{bubbles:true}));count++;}status.textContent=`${count} alan aktarıldı. Eksik/hatalı alanları belgede kontrol edin. Mevcut Geri al düğmesi kullanılabilir.`;};
        controls.append(apply);status.textContent=`${source.length} mevcut alan bulundu; ${[...staged.values()].filter(x=>!x.draft.value).length} alan boş.`;
    };
    const close=make('button','Yardımcıyı kapat');close.type='button';close.onclick=()=>{recognition?.abort();box.remove();};
    box.append(info,show,controls,status,close);workspace.querySelector('.dw-body')?.before(box);
    workspace.addEventListener('close',()=>{recognition?.abort();box.remove();},{once:true});
}

const names={landlord:'Kiraya veren',tenant:'Kiracı',monthlyRent:'Aylık kira (TL)',startDate:'Başlangıç tarihi'};
export function renderDocuments(parent,documents,fields){
    const create=(tag,text)=>{const e=document.createElement(tag);e.textContent=text;return e;};
    if(Object.keys(fields).length){const preview=create('dl','');for(const [key,value] of Object.entries(fields)){if(!names[key])continue;preview.append(create('dt',names[key]),create('dd',value));}parent.append(preview);}
    for(const doc of documents){
        let url;try{url=new URL(doc.url,location.origin);}catch{continue;}if(url.origin!==location.origin)continue;
        const button=create('button',`${doc.title} — editörde aç`);button.type='button';button.style.display='block';button.style.marginTop='8px';
        button.onclick=()=>{
            const workspace=document.getElementById('document-workspace');
            const entry=[...document.querySelectorAll('.documents-entry')].find(entry=>entry.querySelector('.documents-link')?.href===url.href);
            const action=entry?.querySelector('[data-document-action="fill"]');
            if(!workspace || !action){parent.append(create('p','Belge bu sayfada bulunamadı. Dokümanlar menüsünü kullanın.'));return;}
            document.getElementById('zyd-assistant')?.close();action.click();
            if(!Object.keys(fields).length)return;
            workspace.querySelector('[data-assistant-preview]')?.remove();
            const preview=create('section','');preview.dataset.assistantPreview='true';preview.style.cssText='padding:12px;background:#fff7ed;color:#172554;border:1px solid #f97316';
            const heading=create('strong','Asistan alan ön izlemesi — henüz aktarılmadı');preview.append(heading);
            for(const [key,value]of Object.entries(fields)){if(names[key])preview.append(create('p',`${names[key]}: ${value}`));}
            const status=create('p','Belge yüklenince eşleşen alanlara aktarabilirsiniz. Yalnız bu çalışma kopyası değişir.');
            const apply=create('button','Kontrol ettim, eşleşen alanlara aktar');apply.type='button';
            apply.onclick=()=>{
                let count=0;const missing=[];
                for(const [key,value] of Object.entries(fields)){
                    if(!names[key])continue;
                    const input=[...workspace.querySelectorAll('.dw-direct-pages input,.dw-direct-pages textarea')].find(i=>i.name===key && i.dataset.original==='false' && !i.disabled && !i.readOnly);
                    if(!input){missing.push(names[key]);continue;}
                    input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));count++;
                }
                if(count){heading.textContent='Asistan alan aktarımı — sonucu kontrol edin';cancel.textContent='Bilgi kutusunu kapat';apply.disabled=missing.length===0;}
                status.textContent=`${count} alan aktarıldı.${missing.length?' Eşlenemeyen alanlar: '+missing.join(', ')+'. Belgenin yüklenmesini bekleyin veya elle doldurun.':''} Sonucu kontrol edin; mevcut Geri al düğmesini kullanabilirsiniz.`;
            };
            const cancel=create('button','Aktarımdan vazgeç');cancel.type='button';cancel.onclick=()=>preview.remove();
            preview.append(status,apply,cancel);workspace.querySelector('.dw-body')?.before(preview);
            workspace.addEventListener('close',()=>preview.remove(),{once:true});
        };
        parent.append(button);
        if(/satış vaadi|satis vaadi/i.test(doc.title))parent.append(create('p','Hazırlanan taslak tek başına resmî şekil şartını karşılamaz. Gereken noter veya diğer resmî işlemleri ilgili kurumla tamamlayın. Asistan imza oluşturmaz.'));
    }
}

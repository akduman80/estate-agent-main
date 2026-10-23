import {documentHelper} from './assistant-document-helper.js?v=gemini-20261023';
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
            documentHelper(workspace,fields);
        };
        parent.append(button);
        if(/satış vaadi|satis vaadi/i.test(doc.title))parent.append(create('p','Hazırlanan taslak tek başına resmî şekil şartını karşılamaz. Gereken noter veya diğer resmî işlemleri ilgili kurumla tamamlayın. Asistan imza oluşturmaz.'));
    }
}

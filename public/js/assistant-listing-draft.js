const key='zyd-assistant-listing-draft';
const allowed=new Set(['city_id','district_id','category','type','price','square_meters','title','description']);
function initialize(){
    let stored;try{stored=JSON.parse(sessionStorage.getItem(key));}catch{return;}
    if(!stored || !stored.fields)return;
    if(!Number.isFinite(stored.created) || Date.now()-stored.created<0 || Date.now()-stored.created>15*60*1000){sessionStorage.removeItem(key);return;}
    const component=[...document.querySelectorAll('[wire\\:id]')].find(e=>e.querySelector('[wire\\:model^="data."], [wire\\:model\\.live^="data."], input[id="data.title"]'));
    if(!component || !window.Livewire)return;
    const wire=window.Livewire.find(component.getAttribute('wire:id'));
    if(!wire)return;
    const preview=document.createElement('section');preview.style.cssText='padding:16px;border:1px solid #f97316;border-radius:12px;margin-bottom:16px';
    const heading=document.createElement('h2');heading.textContent='Asistan ilan taslağı — kaydedilmedi';preview.append(heading);
    const text=document.createElement('pre');text.style.cssText='white-space:pre-wrap;overflow-wrap:anywhere';text.textContent=Object.entries(stored.fields).filter(([k])=>allowed.has(k)).map(([k,v])=>`${({city_id:'İl',district_id:'İlçe',category:'Kategori',type:'İşlem türü',price:'Fiyat',square_meters:'Metrekare',title:'Başlık',description:'Açıklama'})[k]}: ${String(v).replace(/<[^>]*>/g,'')}`).join('\n');preview.append(text);
    const status=document.createElement('p');status.textContent='Alanları kontrol edip mevcut forma aktarın. Kayıt ve yayın işlemlerini panelden kendiniz tamamlayın.';
    const apply=document.createElement('button');apply.type='button';apply.textContent='Kontrol ettim, forma aktar';
    apply.onclick=async()=>{apply.disabled=true;try{for(const [name,value]of Object.entries(stored.fields)){if(!allowed.has(name))continue;await wire.$set(`data.${name}`,value);}sessionStorage.removeItem(key);status.textContent='Taslak forma aktarıldı. Kaydedilmedi veya yayınlanmadı. Eksik alanları panelde tamamlayın.';}catch{status.textContent='Aktarım tamamlanamadı. Formu kontrol edin veya bilgileri elle girin.';apply.disabled=false;}};
    const cancel=document.createElement('button');cancel.type='button';cancel.textContent='Taslağı sil';cancel.onclick=()=>{sessionStorage.removeItem(key);preview.remove();};
    preview.append(status,apply,cancel);component.prepend(preview);
}
if(window.Livewire)initialize();else document.addEventListener('livewire:initialized',initialize,{once:true});

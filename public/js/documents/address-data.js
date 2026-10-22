let dataset, loading;
const equal=(a,b)=>a.toLocaleLowerCase('tr-TR')===String(b||'').trim().toLocaleLowerCase('tr-TR');
export async function loadAddresses() {
    if(!loading){
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),5000);
    loading=fetch('/data/documents/turkey-addresses.json',{credentials:'same-origin',signal:controller.signal}).then(response=>{
        if(!response.ok)throw new Error('Adres önerileri yüklenemedi; elle giriş yapabilirsiniz.');
        return response.json();
    }).then(data=>dataset=data.provinces).catch(error=>{loading=null;throw error;}).finally(()=>clearTimeout(timer));
    }
    return loading;
}
export function addressChoices(field,fields,values) {
    if(!dataset)return null;
    if(field.kind==='city')return Object.keys(dataset);
    const parent=fields.find(f=>f.key===field.dependsOn);
    if(field.kind==='district'&&parent?.kind==='city') {
        const city=Object.keys(dataset).find(name=>equal(name,values[parent.key]));
        return Object.keys(dataset[city]||{});
    }
    if(field.kind==='neighborhood'&&parent?.kind==='district') {
        const province=fields.find(f=>f.key===parent.dependsOn);
        const city=Object.keys(dataset).find(name=>equal(name,values[province?.key]));
        const county=Object.keys(dataset[city]||{}).find(name=>equal(name,values[parent.key]));
        return dataset[city]?.[county]||[];
    }
    return null;
}

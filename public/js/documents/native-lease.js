import {validateField} from './field-rules.js?v=documents-20261018-all1';
import {leaseLocation} from './lease-locations.js?v=lease-location-20261020-v1';

export const leaseHash = 'f7e7ea2e3d9a703650a8f6d55b7dec2f49c67fc574051607f076189309369924';
export const leaseTemplate = {
    id:'native-lease-v2-location', pages:3,
    fields:[
        ['city','İl','city'], ['county','İlçe','district'], ['district','Mahalle','neighborhood'], ['street','Cadde / sokak','address'], ['number','Bina / daire numarası','text'],
        ['propertyType','Kiralananın cinsi','text'], ['landlord','Kiraya verenin adı soyadı','name'],
        ['landlordIdentity','Kiraya verenin T.C. kimlik numarası','identity'], ['landlordAddress','Kiraya verenin adresi','address'],
        ['tenant','Kiracının adı soyadı','name'], ['tenantIdentity','Kiracının T.C. kimlik numarası','identity'],
        ['tenantAddress','Kiracının adresi','address'], ['startDate','Kira başlangıç tarihi','date'], ['duration','Kira süresi','text'],
        ['annualRent','Yıllık kira bedeli (TL)','amount'], ['monthlyRent','Aylık kira bedeli (TL)','amount'],
        ['paymentMethod','Kiranın ödeme şekli','text'], ['usage','Kiralananın kullanım şekli','text'],
        ['condition','Kiralananın durumu','text'], ['inventory','Demirbaşlar','description'],
        ['bank','Banka','text'], ['branch','Banka şubesi','text'], ['bankAccount','Hesap / IBAN','text'],
        ['court','Mahkeme yeri','text'], ['contractDate','Sözleşme tarihi','date'],
    ].map(([key,label,kind])=>({key,label,kind,...(key==='county'?{dependsOn:'city'}:key==='district'?{dependsOn:'county'}:{}),...(key==='propertyType'?{options:['Daire','Müstakil ev','İş yeri','Arsa']}:key==='paymentMethod'?{options:['Banka havalesi','EFT','Nakit']}:{}),pages:['landlord','tenant','guarantor'].includes(key)?[1,2,3]:['bank','branch','bankAccount','court','contractDate'].includes(key)?[3]:[1],positions:[],lines:['address','description'].includes(kind)?2:1,currency:'TRY'})),
};

export async function renderLease(dialog, values, signal) {
    const normalized={};
    for(const field of leaseTemplate.fields) {
        const value=values[field.key]||'';
        const checked=validateField(value,field.kind,{currency:'TRY'});
        if(!checked.valid) throw new Error(`${field.label}: ${checked.error}`);
        if(value.length>240) throw new Error(`${field.label}: En fazla 240 karakter kullanın.`);
        normalized[field.key]=value ? checked.display : '';
    }
    const location = leaseLocation(normalized.city, normalized.county);
    normalized.city = location.city || normalized.city;
    normalized.county = location.county || normalized.county;
    const extraWidths={bank:60,branch:84,bankAccount:132,court:51.28,contractDate:51.60,landlord:175,tenant:95,guarantor:85};
    const measure=document.createElement('canvas').getContext('2d');measure.font='10px Arial';
    for(const [key,width]of Object.entries(extraWidths))if(normalized[key]&&(normalized[key].includes('\n')||measure.measureText(normalized[key]).width>width))throw new Error(`${leaseTemplate.fields.find(f=>f.key===key).label}: Metin ayrılan alana sığmıyor. Kısaltın.`);
    const response=await fetch('/dokumanlar/islemler/kira',{
        method:'POST',credentials:'same-origin',signal,
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':dialog.dataset.csrf},
        body:JSON.stringify({values:normalized}),
    });
    if(!response.ok) {
        if(response.status===429) throw new Error('Çok sık belge oluşturuldu. Bir dakika sonra yeniden deneyin.');
        const error=await response.json().catch(()=>({}));
        throw new Error(error.message||'Belge oluşturulamadı. Yeniden deneyin.');
    }
    const result=await response.json();
    const decode=text=>Uint8Array.from(atob(text),character=>character.charCodeAt(0));
    return {pdf:decode(result.pdf),docx:decode(result.docx)};
}

export async function renderNativeTemplate(dialog, revision, values, signal) {
    const normalized = {};
    for (const field of revision.mapping.fields) {
        const value = field.locked ? '' : values[field.key] || '';
        const checked = validateField(value,field.kind);
        if (!checked.valid) throw new Error(`${field.label}: ${checked.error}`);
        normalized[field.key] = checked.display;
    }
    const response = await fetch(`/dokumanlar/islemler/sablon/${revision.id}/${revision.version}`, {
        method:'POST',credentials:'same-origin',signal,
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':dialog.dataset.csrf},
        body:JSON.stringify({values:normalized}),
    });
    if (!response.ok) { const error=await response.json().catch(()=>({}));throw new Error(error.message || 'Belge oluşturulamadı. Kısa süre sonra yeniden deneyin.'); }
    const result = await response.json(),decode=text=>Uint8Array.from(atob(text),c=>c.charCodeAt(0));
    return {pdf:decode(result.pdf),docx:decode(result.docx)};
}

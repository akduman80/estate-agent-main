import {validateField} from './field-rules.js';

export const leaseHash = 'f7e7ea2e3d9a703650a8f6d55b7dec2f49c67fc574051607f076189309369924';
export const leaseTemplate = {
    id:'native-lease-v1', pages:3,
    fields:[
        ['district','Mahalle','address'], ['street','Cadde / sokak','address'], ['number','Bina / daire numarası','text'],
        ['propertyType','Kiralananın cinsi','text'], ['landlord','Kiraya verenin adı soyadı','name'],
        ['landlordIdentity','Kiraya verenin T.C. kimlik numarası','identity'], ['landlordAddress','Kiraya verenin adresi','address'],
        ['tenant','Kiracının adı soyadı','name'], ['tenantIdentity','Kiracının T.C. kimlik numarası','identity'],
        ['tenantAddress','Kiracının adresi','address'], ['startDate','Kira başlangıç tarihi','date'], ['duration','Kira süresi','text'],
        ['annualRent','Yıllık kira bedeli (TL)','amount'], ['monthlyRent','Aylık kira bedeli (TL)','amount'],
        ['paymentMethod','Kiranın ödeme şekli','text'], ['usage','Kiralananın kullanım şekli','text'],
        ['condition','Kiralananın durumu','text'], ['inventory','Demirbaşlar','description'],
    ].map(([key,label,kind])=>({key,label,kind,positions:[],lines:['address','description'].includes(kind)?2:1,currency:'TRY'})),
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

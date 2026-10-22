import {leaseLocations, leaseLocation} from './lease-locations.js?v=lease-location-20261020-v1';
import {addressChoices} from './address-data.js?v=documents-20261022';

// These source fields explicitly contain one combined location, without cadastral data.
// Keep the original output position; helper controls only compose its text.
const combinedLocations = {'original-4-v2':['row_2',false],'original-5-v2':['blank_9',true],'original-6-v2':['blank_17',true],'original-8-v2':['blank_9',true]};
export function extendLocationFields(template) {
    const entry = combinedLocations[template?.id];
    if (!entry) return template;
    const [target, neighborhood] = entry, original = template.fields.find(field => field.key === target);
    if (!original || template.fields.some(field => field.compositeTarget === target)) return template;
    const pages = original.pages || [...new Set(original.positions.map(p => p.page))];
    const helpers = ['city','county',...(neighborhood?['quarter']:[])].map((part,index)=>({
        key:`${target}_${part}`,label:['İl seçimi','İlçe seçimi','Mahalle (manuel)'][index],
        kind:['city','district','neighborhood'][index],positions:[],pages,
        compositeTarget:target,...(index?{dependsOn:`${target}_${index===1?'city':'county'}`}:{})
    }));
    template.fields.splice(template.fields.indexOf(original),0,...helpers);
    if(template.id==='original-4-v2'){const quarter=template.fields.find(f=>f.key==='row_3');if(quarter){quarter.kind='neighborhood';quarter.dependsOn=target+'_county';}}

    return template;
}

export function composeLocationValues(fields, values, changedKey) {
    const changed = fields.find(field => field.key === changedKey);
    if (changed?.compositeTarget) {
        values[changed.compositeTarget] = fields.filter(field => field.compositeTarget === changed.compositeTarget)
            .map(field => values[field.key] || '').filter(Boolean).join(' / ');
    } else {
        for (const field of fields.filter(field => field.compositeTarget === changedKey)) values[field.key] = '';
    }
}

// Dependencies use field keys, so multiple parties/addresses never share a parent accidentally.
export function choiceValues(field, fields, values) {
    const current=addressChoices(field,fields,values);
    if(current!==null)return current;
    if (field.kind === 'city') return Object.keys(leaseLocations);
    if (field.kind === 'district' && field.dependsOn) {
        const parent = fields.find(item => item.key === field.dependsOn);
        if (parent?.kind === 'city') return leaseLocations[leaseLocation(values[parent.key]).city] || [];
    }
    return field.options || [];
}

export function validateChoices(fields, values) {
    for (const field of fields) {
        const value = values[field.key];
        if (!value || !(field.kind === 'city' || (field.kind === 'district' && field.dependsOn))) continue;
        const match = choiceValues(field, fields, values).find(option => option.toLocaleLowerCase('tr-TR') === value.trim().toLocaleLowerCase('tr-TR'));
        if (match) values[field.key] = match;
    }
    for (const field of fields.filter(field => field.compositeTarget && values[field.key])) composeLocationValues(fields, values, field.key);
    return values;
}

export function clearDependentValues(fields, values, changedKey, visited = new Set()) {
    if (visited.has(changedKey)) return;
    visited.add(changedKey);
    for (const field of fields.filter(item => item.dependsOn === changedKey)) {
        values[field.key] = '';
        clearDependentValues(fields, values, field.key, visited);
    }
}

export function prepareChoices(form, fields, values) {
    for (const field of fields) {
        const input = [...form.querySelectorAll('input,textarea')].find(item => item.name === field.key);
        if (!input) continue;
        const options = choiceValues(field, fields, values);
        if (options.length || field.dependsOn || field.kind === 'city') {
            const list = document.createElement('datalist');
            list.id = `dw-options-${field.key}`;
            for (const value of options) {
                const option = document.createElement('option'); option.value = value; list.append(option);
            }
            input.closest('label').querySelector('datalist')?.remove();
            input.setAttribute('list', list.id); input.autocomplete = 'off'; input.closest('label').append(list);
        }
        if (field.kind === 'neighborhood') {
            let note = input.closest('label').querySelector('[data-location-note]');
            if (!note) { note = document.createElement('small'); note.dataset.locationNote = ''; input.closest('label').append(note); }
            note.textContent = 'Manuel giriş: Mahalle adını yazın veya mikrofonu kullanın. Bu değer mahalle listesinden doğrulanmaz.';
            input.dataset.locationSource = 'manual';
        }
    }
}

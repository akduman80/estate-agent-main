import {templates} from '../public/js/documents/workspace-model.js';
import {leaseHash,leaseTemplate} from '../public/js/documents/native-lease.js';
import {guarantorFreeHashes} from '../public/js/documents/guarantor-free.js';
import {extendLocationFields} from '../public/js/documents/field-choices.js';
import fs from 'node:fs';
import {createHash} from 'node:crypto';
const sources=['workspace-model.js','original-mappings.js','native-lease.js','guarantor-free.js','field-choices.js'];
const catalog={sources:Object.fromEntries(sources.map(name=>['public/js/documents/'+name,createHash('sha256').update(fs.readFileSync(new URL('../public/js/documents/'+name,import.meta.url),'utf8').replace(/\r\n/g,'\n')).digest('hex')])),templates:{}};
for(const [hash,template]of Object.entries({...templates,[leaseHash]:leaseTemplate})){
    const fields=extendLocationFields(structuredClone(template)).fields.filter(f=>!f.locked&&!f.original).map(f=>Object.fromEntries(['key','label','kind','dependsOn','required'].filter(k=>k in f).map(k=>[k,f[k]])));
    catalog.templates[hash]=fields;
}
catalog.templates[leaseHash]=catalog.templates[guarantorFreeHashes.lease];
catalog.templates['0b354332825d65b42717d973b2a26e67d2afdd8f3653513e26491e5fa93ddfe7']=catalog.templates[guarantorFreeHashes.workplace];
fs.writeFileSync(new URL('../resources/data/assistant-document-fields.json',import.meta.url),JSON.stringify(catalog,null,2)+'\n');
console.log(Object.keys(catalog.templates).length+' hash-pinned document schemas generated.');

import {leaseTemplate} from './native-lease.js?v=documents-20261022';
export const guarantorFreeHashes = {"lease":"587a3231f34efb34064cf0de74caa675f4916eb4597929c0a0fcf06373f86595","workplace":"0e443659a570790c6c2613967671466d2e4a49846b811bf935acb2a775ce9088"};
export function guarantorFreeMappings(templates) {
const lease=structuredClone(leaseTemplate); lease.id='lease-no-guarantor-v3';lease.size=[595.3,841.89];lease.pageSizes=[[595.3,841.89],[595.3,841.89],[595.3,841.89]];lease.fields=lease.fields.filter(f=>f.key!=='guarantor');
lease.fields.find(f=>f.key==='city').positions=[{page:1,x:232,y:733.389,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='county').positions=[{page:1,x:232,y:711.989,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='district').positions=[{page:1,x:232,y:690.589,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='street').positions=[{page:1,x:232,y:669.189,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='number').positions=[{page:1,x:232,y:647.789,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='propertyType').positions=[{page:1,x:232,y:624.789,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='landlord').positions=[{page:1,x:232,y:603.389,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='landlordIdentity').positions=[{page:1,x:232,y:581.989,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='landlordAddress').positions=[{page:1,x:232,y:539.889,width:286,fontSize:11,height:32,lines:2}];
lease.fields.find(f=>f.key==='tenant').positions=[{page:1,x:232,y:497.789,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='tenantIdentity').positions=[{page:1,x:232,y:476.389,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='tenantAddress').positions=[{page:1,x:232,y:454.989,width:286,fontSize:11,height:32,lines:2}];
lease.fields.find(f=>f.key==='startDate').positions=[{page:1,x:232,y:414.339,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='duration').positions=[{page:1,x:232,y:392.939,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='annualRent').positions=[{page:1,x:232,y:371.539,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='monthlyRent').positions=[{page:1,x:232,y:350.139,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='paymentMethod').positions=[{page:1,x:232,y:328.739,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='usage').positions=[{page:1,x:232,y:307.339,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='condition').positions=[{page:1,x:232,y:285.939,width:286,fontSize:11,height:14,lines:1}];
lease.fields.find(f=>f.key==='inventory').positions=[{page:1,x:232,y:264.539,width:286,fontSize:11,height:32,lines:2}];
lease.fields.find(f=>f.key==='bank').positions.push({page:3,x:463.75,y:656.289,width:60,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='branch').positions.push({page:3,x:163.242,y:635.589,width:84,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='bankAccount').positions.push({page:3,x:334.59,y:635.589,width:132,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='court').positions.push({page:3,x:366.84,y:325.089,width:51.28,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='contractDate').positions.push({page:3,x:219.02,y:262.989,width:51.6,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='landlord').positions.push({page:1,x:100,y:109.889,width:175,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='landlord').positions.push({page:2,x:110,y:77.089,width:175,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='landlord').positions.push({page:3,x:180,y:148.389,width:175,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='tenant').positions.push({page:1,x:290,y:109.889,width:175,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='tenant').positions.push({page:2,x:305,y:77.089,width:175,fontSize:10,height:12,lines:1});
lease.fields.find(f=>f.key==='tenant').positions.push({page:3,x:465,y:148.389,width:95,fontSize:10,height:12,lines:1});
templates[guarantorFreeHashes.lease]=lease;
const workplace=structuredClone(Object.values(templates).find(t=>t.id==='original-4-v2'));
workplace.fields=workplace.fields.filter(f=>f.key!=='guarantor');
// Keep the same left-to-right party order as the named signature section on page 4.
for(const page of [1,2]){
    workplace.fields.find(f=>f.key==='row_10').positions.push({page,x:120,y:page===1?130:133,width:115,height:12,fontSize:10,lines:1});
    workplace.fields.find(f=>f.key==='row_7').positions.push({page,x:405,y:page===1?130:133,width:115,height:12,fontSize:10,lines:1});
}
workplace.reviewNotice='Kefalet maddesi 18 kaldırıldı. Son paragraftaki özel koşul sayısı ve kalan madde numaraları hukuki inceleme gerektirir.';
for(const [template,y] of [[lease,165],[workplace,181]]){
    for(const [index,[key,label]] of [['block','Ada'],['parcel','Parsel'],['building','Bina'],['door','Kapı'],['unit','Daire']].entries()){
        template.fields.push({key:'property_'+key,label,kind:'text',outputPrefix:label+': ',positions:[{page:1,x:72+index*94,y,width:90,height:12,fontSize:9,lines:1}]});
    }
}
templates[guarantorFreeHashes.workplace]=workplace;
}

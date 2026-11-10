import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import vm from 'node:vm';
const source=await readFile(new URL('../../public/js/property-voice.js',import.meta.url),'utf8');
function setup(Speech){
    const elements=new Map();
    for(const name of ['dialog','[data-voice-text]','[data-voice-status]','[data-voice-results]','[data-voice-start]','[data-voice-close]','[data-voice-record]','[data-voice-apply]'])elements.set(name,{value:'',textContent:'',hidden:true,disabled:false,events:{},addEventListener(name,fn){this.events[name]=fn;}});
    const dialog=elements.get('dialog');dialog.showModal=()=>{dialog.open=true;};dialog.close=()=>{dialog.open=false;dialog.events.close();};
    const requests=[];const root={dataset:{endpoint:'/ilan-arama/metin'},querySelector:name=>elements.get(name)};
    const context={window:{SpeechRecognition:Speech},document:{querySelectorAll:()=>[root],querySelector:()=>({content:'test-csrf'})},fetch:async(url,options)=>{requests.push({url,options});return {ok:true,json:async()=>({filters:{city:'Osmaniye'},ambiguous:false,unsupported:false,url:'/ilanlar?city=Osmaniye'})};}};
    vm.runInNewContext(source,context);return {elements,requests,dialog};
}
{
    const state=setup();state.elements.get('[data-voice-start]').onclick();state.elements.get('[data-voice-record]').onclick();
    assert.equal(state.dialog.open,true);assert.match(state.elements.get('[data-voice-status]').textContent,/desteklenmiyor/);assert.equal(state.requests.length,0);
}
{
    let recognition,calls=0;
    class Speech{constructor(){recognition=this;calls++;}start(){}abort(){this.aborted=true;}}
    const state=setup(Speech);assert.equal(calls,0);state.elements.get('[data-voice-start]').onclick();state.elements.get('[data-voice-record]').onclick();assert.equal(recognition.lang,'tr-TR');
    recognition.onresult({results:[[{transcript:'Osmaniye satılık arsa'}]]});assert.equal(state.elements.get('[data-voice-text]').value,'Osmaniye satılık arsa');assert.equal(state.requests.length,0);
    const button=state.elements.get('[data-voice-apply]');await button.onclick({currentTarget:button});assert.equal(button.disabled,false);assert.equal(state.requests.length,1);assert.equal(state.elements.get('[data-voice-results]').hidden,false);
    assert.equal(JSON.parse(state.requests[0].options.body).text,'Osmaniye satılık arsa');assert.equal(state.requests[0].options.headers['X-CSRF-TOKEN'],'test-csrf');
    state.elements.get('[data-voice-text]').oninput();assert.equal(state.elements.get('[data-voice-results]').hidden,true);state.elements.get('[data-voice-close]').onclick();assert.equal(recognition.aborted,true);
}
{
    let recognition;
    class Speech{constructor(){recognition=this;}start(){throw new Error('permission denied');}abort(){}}
    const state=setup(Speech);state.elements.get('[data-voice-start]').onclick();state.elements.get('[data-voice-record]').onclick();assert.match(state.elements.get('[data-voice-status]').textContent,/yazılı arama/);recognition.onerror();assert.match(state.elements.get('[data-voice-status]').textContent,/Aramanızı yazabilirsiniz/);
}
console.log('PASS: explicit speech start, Turkish language, unsupported/denied fallback, editable transcript, CSRF and review before navigation.');

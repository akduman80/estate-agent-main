(() => {
 const fold = s => s.toLocaleLowerCase('tr-TR').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/ı/g,'i');
 let active;
 function enhance(select, title) {
  const button=document.createElement('button'), text=document.createElement('span'), arrow=document.createElement('span');
  button.type='button';button.id=select.id+'-control';button.className=select.className;
  button.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0;max-width:100%;cursor:pointer;';
  text.style.cssText='overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';arrow.textContent='▾';arrow.setAttribute('aria-hidden','true');button.append(text,arrow);
  for(const [k,v] of Object.entries({role:'combobox','aria-label':title,'aria-expanded':'false','aria-haspopup':'listbox','aria-required':'true'}))button.setAttribute(k,v);
  select.after(button);const label=document.querySelector(`label[for="${select.id}"]`);if(label)label.htmlFor=button.id;
  select.tabIndex=-1;select.setAttribute('aria-hidden','true');select.style.cssText='position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;';
  const panel=document.createElement('div'), search=document.createElement('input'),list=document.createElement('div');
  panel.hidden=true;panel.style.cssText='position:fixed;z-index:10000;padding:8px;background:white;border:1px solid #cbd5e1;border-radius:12px;box-shadow:0 10px 24px #0f172a26;box-sizing:border-box;';
  search.type='search';search.autocomplete='off';search.placeholder='Liste içinde ara…';search.setAttribute('aria-label',title+' listesinde ara');search.setAttribute('role','combobox');search.setAttribute('aria-expanded','true');
  search.style.cssText='width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;font-size:16px;box-sizing:border-box;';
  list.id=select.id+'-list';list.setAttribute('role','listbox');list.setAttribute('aria-label',title);list.style.cssText='overflow-y:auto;overscroll-behavior:contain;margin-top:6px;';
  button.setAttribute('aria-controls',list.id);search.setAttribute('aria-controls',list.id);panel.append(search,list);document.body.append(panel);
  let matches=[],index=-1;
  function position(){
   if(panel.hidden)return;const rect=button.getBoundingClientRect(),vv=window.visualViewport,top=vv?.offsetTop||0,height=vv?.height||innerHeight;
   const below=top+height-rect.bottom-8,above=rect.top-top-8,up=below<180&&above>below,available=Math.max(100,Math.min(320,up?above:below));
   panel.style.width=Math.min(rect.width,innerWidth-16)+'px';panel.style.left=Math.max(8,Math.min(rect.left,innerWidth-panel.offsetWidth-8))+'px';
   list.style.maxHeight=Math.max(40,available-64)+'px';panel.style.top=(up?Math.max(top+8,rect.top-panel.offsetHeight-4):rect.bottom+4)+'px';
  }
  function highlight(){
   [...list.querySelectorAll('[role="option"]')].forEach((el,i)=>el.style.background=i===index?'#fff7ed':'white');const el=list.children[index];
   if(el?.getAttribute('role')==='option'){search.setAttribute('aria-activedescendant',el.id);el.scrollIntoView({block:'nearest'});}else search.removeAttribute('aria-activedescendant');
  }
  function close(focus=false){panel.hidden=true;button.setAttribute('aria-expanded','false');if(active===api)active=null;if(focus)button.focus();}
  function choose(option){select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));close(true);}
  function filter(){
   matches=[...select.options].filter(o=>o.value&&fold(o.textContent).includes(fold(search.value)));list.replaceChildren();
   matches.forEach((option,i)=>{const item=document.createElement('div');item.id=list.id+'-'+i;item.setAttribute('role','option');item.setAttribute('aria-selected',String(option.value===select.value));item.textContent=option.textContent;item.style.cssText='padding:10px 8px;border-radius:6px;font-size:14px;color:#334155;cursor:pointer;overflow-wrap:anywhere;';item.addEventListener('click',()=>choose(option));list.append(item);});
   if(!matches.length){const empty=document.createElement('p');empty.textContent='Sonuç bulunamadı';empty.setAttribute('role','status');empty.style.cssText='padding:10px;color:#64748b;font-size:14px;';list.append(empty);}
   index=matches.findIndex(o=>o.value===select.value);if(index<0&&matches.length)index=0;position();highlight();
  }
  function open(){if(select.disabled)return;active?.close();active=api;panel.hidden=false;button.setAttribute('aria-expanded','true');search.value='';filter();search.focus({preventScroll:true});position();}
  function refresh(){button.disabled=select.disabled;button.style.opacity=select.disabled?'.55':'1';text.textContent=select.selectedOptions[0]?.textContent||select.options[0]?.textContent;if(!panel.hidden)filter();}
  const api={close,refresh};
  button.addEventListener('click',()=>panel.hidden?open():close());
  button.addEventListener('keydown',e=>{if(['ArrowDown','ArrowUp'].includes(e.key)){e.preventDefault();open();}});
  search.addEventListener('input',filter);search.addEventListener('keydown',e=>{
   if(e.key==='Escape'){e.preventDefault();close(true);}if(e.key==='Tab')close(true);
   if(e.key==='Enter'){e.preventDefault();if(matches[index])choose(matches[index]);}
   if(['ArrowDown','ArrowUp'].includes(e.key)){e.preventDefault();if(matches.length)index=(index+(e.key==='ArrowDown'?1:-1)+matches.length)%matches.length;highlight();}
  });
  select.addEventListener('change',()=>{refresh();button.removeAttribute('aria-invalid');});
  select.addEventListener('invalid',e=>{e.preventDefault();button.setAttribute('aria-invalid','true');open();});
  document.addEventListener('pointerdown',e=>{if(!panel.contains(e.target)&&!button.contains(e.target))close();});
  window.addEventListener('resize',position);window.addEventListener('scroll',position,true);window.visualViewport?.addEventListener('resize',position);refresh();return api;
 }
 document.querySelectorAll('[data-tax-offices]').forEach(root=>{
  const city=root.querySelector('[data-tax-city]'),office=root.querySelector('[data-tax-office]'),offices=JSON.parse(root.querySelector('[data-tax-directory]').textContent),status=root.querySelector('[data-tax-status]');
  const cityControl=enhance(city,'Vergi Dairesi İli'),officeControl=enhance(office,'Vergi Dairesi');
  function update(selected=''){
   const rows=offices.filter(o=>o.city_code===city.value).sort((a,b)=>a.name.localeCompare(b.name,'tr'));
   office.replaceChildren(new Option(city.value?'Vergi dairesi seçiniz':'Önce il seçiniz',''));rows.forEach(o=>office.add(new Option(o.name,o.code)));
   office.value=rows.some(o=>o.code===selected)?selected:'';office.disabled=!city.value;officeControl.close();officeControl.refresh();cityControl.refresh();
   status.textContent=`${city.value?rows.length+' vergi dairesi listelendi. ':''}GİB kaynak tarihi: ${root.dataset.sourceDate}.`;
  }
  city.addEventListener('change',()=>update());update(office.dataset.selected||'');
 });
})();

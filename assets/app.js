document.addEventListener('DOMContentLoaded',()=>{
 const selected=new Set();
 const checks=[...document.querySelectorAll('.photo-select')];
 const count=document.querySelector('[data-selected-count]');
 const download=document.querySelector('[data-download-selected]');
 function update(){if(count)count.textContent=selected.size+' selecionada(s)';if(download)download.disabled=selected.size===0;}
 checks.forEach(c=>c.addEventListener('change',()=>{c.checked?selected.add(c.value):selected.delete(c.value);update();}));
 if(download)download.addEventListener('click',async()=>{
   if(!selected.size)return;
   download.disabled=true;download.textContent='Preparando ZIP…';
   try{
     const r=await fetch('api/download.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids:[...selected]})});
     if(!r.ok){const j=await r.json().catch(()=>({}));throw new Error(j.error||'Não foi possível preparar o download.');}
     const blob=await r.blob(),url=URL.createObjectURL(blob),a=document.createElement('a');
     a.href=url;a.download='fj-fotos-selecionadas.zip';document.body.appendChild(a);a.click();a.remove();URL.revokeObjectURL(url);
   }catch(e){alert(e.message);}finally{download.disabled=false;download.textContent='Baixar selecionadas (.ZIP)';}
 });
 const modal=document.querySelector('.modal');
 document.querySelectorAll('[data-full]').forEach(el=>el.addEventListener('click',()=>{if(modal){modal.querySelector('img').src=el.dataset.full;modal.classList.add('open');}}));
 if(modal){modal.addEventListener('click',e=>{if(e.target===modal||e.target.closest('.modal-close'))modal.classList.remove('open');});document.addEventListener('keydown',e=>{if(e.key==='Escape')modal.classList.remove('open');});}
 const category=document.querySelector('#filter-category'),theme=document.querySelector('#filter-theme'),search=document.querySelector('#filter-search');
 function filter(){const c=category?.value||'',t=theme?.value||'',q=(search?.value||'').toLowerCase();document.querySelectorAll('.photo-card').forEach(card=>{const ok=(!c||card.dataset.category===c)&&(!t||card.dataset.theme===t)&&(!q||card.dataset.search.includes(q));card.hidden=!ok;});}
 [category,theme,search].forEach(x=>x?.addEventListener(x===search?'input':'change',filter));
 const all=document.querySelector('[data-select-visible]');if(all)all.addEventListener('click',()=>{checks.filter(c=>!c.closest('.photo-card')?.hidden).forEach(c=>{c.checked=true;selected.add(c.value);});update();});
});
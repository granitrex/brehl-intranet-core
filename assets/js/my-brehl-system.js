(function(){
  const inputs=document.querySelectorAll('.mbs-search-input');
  inputs.forEach(function(input){
    const box=input.parentElement.querySelector('.mbs-search-results');
    let timer;
    input.addEventListener('input',function(){
      clearTimeout(timer);
      const q=input.value.trim();
      if(q.length<2){box.hidden=true;box.innerHTML='';return;}
      timer=setTimeout(async function(){
        const url=new URL(MyBrehlSystem.ajaxUrl);
        url.searchParams.set('action','my_brehl_global_search');
        url.searchParams.set('nonce',MyBrehlSystem.nonce);
        url.searchParams.set('q',q);
        const res=await fetch(url.toString(),{credentials:'same-origin'});
        const json=await res.json();
        const items=(json&&json.success&&Array.isArray(json.data))?json.data:[];
        box.innerHTML=items.length?items.map(i=>`<a class="mbs-search-item" href="${i.url||'#'}"><strong>${escapeHtml(i.title)}</strong><small>${escapeHtml(i.type+(i.subtitle?' · '+i.subtitle:''))}</small></a>`).join(''):'<div class="mbs-search-item">Keine Treffer gefunden.</div>';
        box.hidden=false;
      },250);
    });
    document.addEventListener('click',function(e){if(!input.parentElement.contains(e.target))box.hidden=true;});
  });
  function escapeHtml(value){return String(value||'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));}
})();

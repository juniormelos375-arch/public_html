<?php
require __DIR__.'/_init.php';require __DIR__.'/_layout.php';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$action=$_POST['action']??'';
 try{
  if($action==='save'){
   $id=(int)($_POST['id']??0);$title=trim($_POST['title']??'');$desc=trim($_POST['description']??'');$cat=(int)($_POST['category_id']??0)?:null;
   if(!$title)throw new RuntimeException('Informe o título da foto.');
   if($id){$s=db()->prepare('SELECT * FROM photos WHERE id=? AND deleted_at IS NULL');$s->execute([$id]);$old=$s->fetch();if(!$old)throw new RuntimeException('Foto não encontrada.');$path=$old['file_path'];$mime=$old['mime_type'];if(!empty($_FILES['image']['name'])){$path=upload_image($_FILES['image']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__).'/'.$path);delete_local_file($old['file_path']);}$s=db()->prepare('UPDATE photos SET category_id=?,title=?,description=?,file_path=?,mime_type=? WHERE id=?');$s->execute([$cat,$title,$desc,$path,$mime,$id]);}
   else{if(empty($_FILES['image']['name']))throw new RuntimeException('Selecione uma imagem.');$path=upload_image($_FILES['image']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__).'/'.$path);$s=db()->prepare('INSERT INTO photos(category_id,title,description,file_path,mime_type) VALUES(?,?,?,?,?)');$s->execute([$cat,$title,$desc,$path,$mime]);}
   flash('Foto salva com sucesso.');
  }elseif($action==='delete'){$id=(int)$_POST['id'];$s=db()->prepare('UPDATE photos SET deleted_at=NOW() WHERE id=? AND deleted_at IS NULL');$s->execute([$id]);flash('Foto movida para a lixeira. Você pode restaurá-la quando quiser.');}
 }catch(Throwable $e){flash($e->getMessage(),'error');}header('Location: photos.php');exit;
}
$edit=null;if(isset($_GET['edit'])){$s=db()->prepare('SELECT * FROM photos WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$cats=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();$rows=db()->query('SELECT p.*,c.name category_name FROM photos p LEFT JOIN categories c ON c.id=p.category_id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC')->fetchAll();
admin_header('Gerenciar fotos','photos');?>
<div class="panel bulk-upload-panel">
 <div class="panel-heading"><div><h2>Cadastro de várias fotos</h2><p class="muted">Envie até 50 imagens de uma só vez. Revise título, descrição e categoria de cada imagem antes de cadastrar.</p></div><span class="bulk-badge">Envio em lote</span></div>
 <form id="bulkUploadForm" class="bulk-upload-form" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
  <div class="form-grid">
   <label class="wide">Selecionar imagens (JPG, PNG, WEBP ou GIF)
    <input id="bulkImages" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
    <small class="muted">Você pode selecionar várias fotos de uma vez. Limite de 50 imagens por envio e <?=e((string)$config['app']['max_upload_mb'])?> MB por imagem.</small>
   </label>
   <label>Categoria para todas as fotos
    <select name="category_id"><option value="">Sem categoria</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?> — <?=e($c['theme'])?></option><?php endforeach;?></select>
   </label>
   <label>Complemento dos títulos (opcional)
    <input name="title_prefix" maxlength="80" placeholder="Ex.: Caneca personalizada">
   </label>
   <label class="wide">Descrição padrão (opcional)
    <textarea name="description" placeholder="Uma descrição aplicada a todas as imagens deste lote."></textarea>
   </label>
  </div>
  <div id="bulkPreview" class="bulk-preview" aria-live="polite"><p class="muted">As miniaturas aparecerão aqui quando você escolher as imagens.</p></div>
  <div class="bulk-progress" id="bulkProgressWrap" hidden><div class="bulk-progress-top"><strong id="bulkProgressLabel">Preparando envio…</strong><span id="bulkProgressPercent">0%</span></div><div class="bulk-progress-track"><div id="bulkProgressBar"></div></div></div>
  <div id="bulkResult" class="bulk-result" aria-live="polite"></div>
  <div class="actions"><button class="btn primary" id="bulkSubmit" type="submit">Enviar e cadastrar fotos</button><button class="btn outline" type="reset" id="bulkReset">Limpar seleção</button></div>
 </form>
</div>
<div class="panel"><h2><?=$edit?'Editar foto':'Adicionar nova foto'?></h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e((string)($edit['id']??''))?>"><div class="form-grid"><label>Título<input name="title" required value="<?=e($edit['title']??'')?>"></label><label>Categoria<select name="category_id"><option value="">Sem categoria</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>" <?=((int)($edit['category_id']??0)===(int)$c['id'])?'selected':''?>><?=e($c['name'])?> — <?=e($c['theme'])?></option><?php endforeach;?></select></label><label class="wide">Descrição<textarea name="description"><?=e($edit['description']??'')?></textarea></label><label class="wide">Imagem (JPG, PNG, WEBP ou GIF)<?=$edit?'<span class="muted"> — deixe vazio para manter a atual</span>':''?><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?=$edit?'':'required'?>></label></div><?php if($edit):?><img class="logo-preview" src="../<?=e($edit['file_path'])?>" alt="Foto atual"><?php endif;?><div class="actions"><button class="btn primary">Salvar foto</button><?php if($edit):?><a class="btn outline" href="photos.php">Cancelar</a><?php endif;?></div></form></div>
<div class="panel"><div class="panel-heading"><div><h2>Biblioteca de fotos (<?=count($rows)?>)</h2><p class="muted">Selecione várias imagens para editar os dados em conjunto.</p></div><a class="btn outline" href="trash.php">🗑 Ver lixeira</a></div>
<div class="batch-tools" id="batchTools">
 <div class="batch-top"><label class="batch-select-all"><input type="checkbox" id="selectAllPhotos"> Selecionar todas</label><strong id="selectedCount">0 selecionadas</strong></div>
 <div class="batch-fields">
  <label>Categoria em lote<select id="batchCategory"><option value="__keep">Manter categoria atual</option><option value="">Sem categoria</option><?php foreach($cats as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?> — <?=e($c['theme'])?></option><?php endforeach;?></select></label>
  <label>Ação para descrição<select id="descriptionMode"><option value="keep">Manter descrições</option><option value="replace">Substituir descrição</option><option value="append">Acrescentar ao final</option><option value="clear">Limpar descrição</option></select></label>
  <label class="batch-description">Texto da descrição<textarea id="batchDescription" rows="2" placeholder="Digite a descrição que será aplicada às fotos selecionadas."></textarea></label>
 </div>
 <div class="actions"><button class="btn primary" type="button" id="applyBatch" disabled>Aplicar alterações às selecionadas</button><button class="btn outline" type="button" id="clearBatch">Limpar seleção</button></div>
 <div id="batchMessage" aria-live="polite"></div>
</div>
<div class="library-toolbar" id="libraryToolbar">
 <label class="library-search"><span>Buscar foto</span><input type="search" id="photoSearch" placeholder="Digite o nome da foto..."></label>
 <label><span>Filtrar por categoria</span><select id="photoCategoryFilter"><option value="">Todas as categorias</option><?php foreach($cats as $c):?><option value="<?=e(mb_strtolower($c['name']))?>"><?=e($c['name'])?></option><?php endforeach;?><option value="__none">Sem categoria</option></select></label>
 <label><span>Ordenar por</span><select id="photoSort"><option value="newest">Mais recentes</option><option value="oldest">Mais antigas</option><option value="az">Nome: A a Z</option><option value="za">Nome: Z a A</option></select></label>
 <div class="library-view"><span>Visualização</span><div class="view-buttons"><button type="button" class="btn soft active" id="gridView" aria-pressed="true" title="Visualizar em grade">▦ Grade</button><button type="button" class="btn soft" id="listView" aria-pressed="false" title="Visualizar em lista">☷ Lista</button></div></div>
 <div class="library-count" id="libraryCount" aria-live="polite"></div>
</div>
<div class="admin-photo-grid" id="photoLibrary"><?php foreach($rows as $r):?><article class="admin-photo-card" data-title="<?=e(mb_strtolower($r['title']))?>" data-category="<?=e(mb_strtolower($r['category_name']??''))?>" data-category-id="<?=e((string)($r['category_id']??''))?>" data-date="<?=e((string)($r['created_at']??''))?>"><label class="photo-select"><input type="checkbox" class="photo-batch-check" value="<?=$r['id']?>"> Selecionar</label><img src="../<?=e($r['file_path'])?>" alt="<?=e($r['title'])?>"><div class="admin-photo-meta"><strong><?=e($r['title'])?></strong><small><?=e($r['category_name']??'Sem categoria')?></small><div class="actions"><a class="btn soft" href="?edit=<?=$r['id']?>">Editar</a><form method="post" onsubmit="return confirm('Mover esta foto para a lixeira?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger">Mover para lixeira</button></form></div></div></article><?php endforeach;?><?php if(!$rows):?><div class="empty">Nenhuma foto ativa cadastrada ainda.</div><?php endif;?></div></div><div class="panel legacy-table"><h2>Lista detalhada</h2><div class="table-wrap"><table><thead><tr><th>Imagem</th><th>Título</th><th>Categoria</th><th>Ações</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><img class="thumb-mini" src="../<?=e($r['file_path'])?>" alt=""></td><td><?=e($r['title'])?></td><td><?=e($r['category_name']??'—')?></td><td><a class="btn soft" href="?edit=<?=$r['id']?>">Editar</a> <form class="inline-form" method="post" onsubmit="return confirm('Mover esta foto para a lixeira?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger">Mover para lixeira</button></form></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4">Nenhuma foto cadastrada.</td></tr><?php endif;?></tbody></table></div></div>

<script>
(()=>{
 const library=document.getElementById('photoLibrary'); if(!library)return;
 const search=document.getElementById('photoSearch'), category=document.getElementById('photoCategoryFilter');
 const sort=document.getElementById('photoSort'), count=document.getElementById('libraryCount');
 const grid=document.getElementById('gridView'), list=document.getElementById('listView');
 const cards=()=>Array.from(library.querySelectorAll('.admin-photo-card'));
 let view='grid';
 function apply(){
  const term=(search.value||'').trim().toLocaleLowerCase('pt-BR');
  const cat=category.value;
  const all=cards();
  all.forEach(card=>{
   const title=(card.dataset.title||'').toLocaleLowerCase('pt-BR');
   const catName=(card.dataset.category||'').toLocaleLowerCase('pt-BR');
   const matchText=!term||title.includes(term);
   const matchCat=!cat||(cat==='__none'?!card.dataset.categoryId:catName===cat);
   card.hidden=!(matchText&&matchCat);
  });
  const mode=sort.value;
  all.sort((a,b)=>{
   if(mode==='az'||mode==='za'){
    const cmp=(a.dataset.title||'').localeCompare(b.dataset.title||'','pt-BR',{sensitivity:'base'});
    return mode==='az'?cmp:-cmp;
   }
   const cmp=(a.dataset.date||'').localeCompare(b.dataset.date||'');
   return mode==='newest'?-cmp:cmp;
  }).forEach(card=>library.appendChild(card));
  library.classList.toggle('library-list-view',view==='list');
  const visible=all.filter(c=>!c.hidden).length;
  count.textContent=visible+' de '+all.length+' foto(s)';
 }
 [search,category,sort].forEach(el=>el.addEventListener('input',apply));
 grid.addEventListener('click',()=>{view='grid';grid.classList.add('active');list.classList.remove('active');grid.setAttribute('aria-pressed','true');list.setAttribute('aria-pressed','false');apply()});
 list.addEventListener('click',()=>{view='list';list.classList.add('active');grid.classList.remove('active');list.setAttribute('aria-pressed','true');grid.setAttribute('aria-pressed','false');apply()});
 apply();
})();
</script>
<script>
(()=>{
 const library=document.getElementById('photoLibrary'); if(!library)return;
 const checks=()=>Array.from(library.querySelectorAll('.photo-batch-check'));
 const all=document.getElementById('selectAllPhotos'), count=document.getElementById('selectedCount');
 const apply=document.getElementById('applyBatch'), msg=document.getElementById('batchMessage');
 const category=document.getElementById('batchCategory'), mode=document.getElementById('descriptionMode'), description=document.getElementById('batchDescription');
 const csrf=document.querySelector('input[name="csrf"]')?.value || '';
 function selected(){return checks().filter(c=>c.checked).map(c=>c.value)}
 function refresh(){const n=selected().length;count.textContent=n+' selecionada(s)';apply.disabled=n===0;all.checked=checks().length>0&&n===checks().length;all.indeterminate=n>0&&n<checks().length}
 checks().forEach(c=>c.addEventListener('change',refresh));
 all.addEventListener('change',()=>{checks().forEach(c=>c.checked=all.checked);refresh()});
 document.getElementById('clearBatch').addEventListener('click',()=>{checks().forEach(c=>c.checked=false);refresh();msg.innerHTML=''});
 mode.addEventListener('change',()=>{description.disabled=['keep','clear'].includes(mode.value);description.required=['replace','append'].includes(mode.value)});
 description.disabled=true;
 apply.addEventListener('click',async()=>{
  const ids=selected(); if(!ids.length)return;
  if(category.value==='__keep'&&mode.value==='keep'){msg.innerHTML='<div class="notice error">Escolha pelo menos uma alteração para aplicar.</div>';return}
  if(['replace','append'].includes(mode.value)&&!description.value.trim()){msg.innerHTML='<div class="notice error">Digite o texto da descrição.</div>';return}
  if(!confirm('Aplicar as alterações em '+ids.length+' foto(s)?'))return;
  apply.disabled=true;apply.textContent='Aplicando…';msg.innerHTML='';
  const body=new URLSearchParams();body.set('csrf',csrf);body.set('category',category.value);body.set('description_mode',mode.value);body.set('description',description.value);ids.forEach(id=>body.append('ids[]',id));
  try{
   const response=await fetch('update-photos-batch.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body});
   const data=await response.json(); if(!response.ok||!data.ok)throw new Error(data.message||'Não foi possível atualizar as fotos.');
   msg.innerHTML='<div class="notice success">'+data.updated+' foto(s) atualizada(s) com sucesso.</div>';
   setTimeout(()=>location.reload(),900);
  }catch(err){msg.innerHTML='<div class="notice error">'+String(err.message||err).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))+'</div>'}
  finally{apply.disabled=selected().length===0;apply.textContent='Aplicar alterações às selecionadas'}
 });
 refresh();
})();
</script>
<script>
(()=>{
 const form=document.getElementById('bulkUploadForm');
 if(!form)return;
 const input=document.getElementById('bulkImages'), preview=document.getElementById('bulkPreview');
 const submit=document.getElementById('bulkSubmit'), wrap=document.getElementById('bulkProgressWrap');
 const bar=document.getElementById('bulkProgressBar'), percent=document.getElementById('bulkProgressPercent');
 const label=document.getElementById('bulkProgressLabel'), result=document.getElementById('bulkResult');
 let chosen=[];
 const allowed=['image/jpeg','image/png','image/webp','image/gif'];
 function formatSize(n){return n<1048576?(n/1024).toFixed(0)+' KB':(n/1048576).toFixed(1)+' MB'}
 function render(){
   chosen=Array.from(input.files||[]);
   if(!chosen.length){preview.innerHTML='<p class="muted">As miniaturas aparecerão aqui quando você escolher as imagens.</p>';return}
   const bad=chosen.filter(f=>!allowed.includes(f.type));
   const commonCategory=form.querySelector('[name="category_id"]').value;
   const commonDescription=form.querySelector('[name="description"]').value;
   const prefix=form.querySelector('[name="title_prefix"]').value.trim();
   preview.innerHTML='<div class="bulk-preview-heading"><strong>'+chosen.length+' arquivo(s) selecionado(s)</strong><span class="muted">Edite os dados de cada foto antes de cadastrar</span></div><div class="bulk-edit-list">'+chosen.map((f,i)=>{
     const base=f.name.replace(/\\.[^.]+$/,'').replace(/[_-]+/g,' ').trim();
     const title=(prefix?prefix+' - ':'')+(base||'Imagem '+(i+1));
     const options=Array.from(form.querySelector('[name="category_id"]').options).map(o=>'<option value="'+escapeHtml(o.value)+'" '+(o.value===commonCategory?'selected':'')+'>'+escapeHtml(o.textContent)+'</option>').join('');
     return '<article class="bulk-edit-card" data-index="'+i+'"><img alt="" src="'+URL.createObjectURL(f)+'"><div class="bulk-edit-fields"><div class="bulk-file-name">'+escapeHtml(f.name)+' <small>'+formatSize(f.size)+'</small></div><label>Título da foto<input type="text" name="item_title['+i+']" maxlength="180" value="'+escapeHtml(title)+'" required></label><label>Descrição<textarea name="item_description['+i+']" rows="2" maxlength="5000">'+escapeHtml(commonDescription)+'</textarea></label><label>Categoria<select name="item_category['+i+']">'+options+'</select></label></div></article>';
   }).join('')+'</div>';
   if(bad.length)result.innerHTML='<div class="notice error">'+bad.length+' arquivo(s) com formato não permitido. Selecione apenas JPG, PNG, WEBP ou GIF.</div>';
   else result.innerHTML='';
 }
 function escapeHtml(s){return s.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
 input.addEventListener('change',render);
 form.addEventListener('reset',()=>{setTimeout(()=>{preview.innerHTML='<p class="muted">As miniaturas aparecerão aqui quando você escolher as imagens.</p>';result.innerHTML='';wrap.hidden=true;},0)});
 form.addEventListener('submit',e=>{
   e.preventDefault();
   if(!chosen.length){result.innerHTML='<div class="notice error">Selecione pelo menos uma imagem.</div>';return}
   if(chosen.length>50){result.innerHTML='<div class="notice error">O limite é de 50 imagens por envio.</div>';return}
   if(chosen.some(f=>!allowed.includes(f.type))){result.innerHTML='<div class="notice error">Há arquivos com formato inválido. Corrija a seleção.</div>';return}
   const data=new FormData(form);
   const xhr=new XMLHttpRequest();
   submit.disabled=true;submit.textContent='Enviando…';wrap.hidden=false;result.innerHTML='';
   bar.style.width='0%';percent.textContent='0%';label.textContent='Enviando imagens…';
   xhr.open('POST','upload-photos.php');
   xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
   xhr.upload.addEventListener('progress',ev=>{
     if(ev.lengthComputable){const p=Math.round(ev.loaded/ev.total*100);bar.style.width=p+'%';percent.textContent=p+'%';label.textContent='Enviando arquivos…'}
   });
   xhr.onload=()=>{
     submit.disabled=false;submit.textContent='Enviar e cadastrar fotos';
     let payload={};try{payload=JSON.parse(xhr.responseText)}catch(_){}
     if(xhr.status>=200&&xhr.status<300&&payload.success>0){
       bar.style.width='100%';percent.textContent='100%';label.textContent='Envio concluído';
       const failures=(payload.results||[]).filter(x=>!x.ok);
       result.innerHTML='<div class="notice success">'+escapeHtml(payload.message||'Cadastro concluído.')+'</div>'+(failures.length?'<ul class="bulk-errors">'+failures.map(x=>'<li>'+escapeHtml(x.name)+': '+escapeHtml(x.error||'Falha no envio')+'</li>').join('')+'</ul>':'');
       setTimeout(()=>location.reload(),failures.length?3500:1200);
     }else{
       label.textContent='Não foi possível concluir';
       result.innerHTML='<div class="notice error">'+escapeHtml(payload.message||'Falha no envio. Verifique o limite de upload do servidor e tente novamente.')+'</div>';
     }
   };
   xhr.onerror=()=>{submit.disabled=false;submit.textContent='Enviar e cadastrar fotos';label.textContent='Erro de conexão';result.innerHTML='<div class="notice error">Não foi possível conectar ao servidor. Tente novamente.</div>'};
   xhr.send(data);
 });
})();
</script>

<?php admin_footer();
<?php
require __DIR__.'/includes/bootstrap.php';
$errors=[]; $success=false;$cartItems=[];$orderTotal=null;$product='';$productId=(int)($_GET['produto']??0)?:null;$orderType='personalizado';
if($productId){$pre=db()->prepare('SELECT id,name FROM products WHERE id=? AND active=1');$pre->execute([$productId]);$preselected=$pre->fetch();if($preselected)$product=$preselected['name'];else $productId=null;}
if(!empty($_SESSION['cart'])){$orderTotal=0.0;$ids=array_map('intval',array_keys($_SESSION['cart']));$in=implode(',',array_fill(0,count($ids),'?'));$st=db()->prepare("SELECT * FROM products WHERE active=1 AND id IN ($in)");$st->execute($ids);$labels=[];foreach($st->fetchAll() as $cp){$cq=(int)$_SESSION['cart'][$cp['id']];$unit=(float)($cp['promotional_price']??$cp['price']);$cartItems[]=['id'=>(int)$cp['id'],'name'=>$cp['name'],'quantity'=>$cq,'unit'=>$unit,'line'=>$unit*$cq];$orderTotal+=$unit*$cq;$labels[]=$cp['name'].' × '.$cq;}if($cartItems){$product=implode(', ',$labels);$orderType='carrinho';}}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $name=trim((string)($_POST['customer_name']??''));
    $phone=trim((string)($_POST['customer_phone']??''));
    $email=trim((string)($_POST['customer_email']??''));
    $product=trim((string)($_POST['product_name']??$product));
    $productId=(int)($_POST['product_id']??0)?:$productId;
    if(!empty($_SESSION['cart'])){ $ids=array_map('intval',array_keys($_SESSION['cart']));$in=implode(',',array_fill(0,count($ids),'?'));$st=db()->prepare("SELECT * FROM products WHERE active=1 AND id IN ($in)");$st->execute($ids);$cartItems=[];$orderTotal=0;$labels=[];foreach($st->fetchAll() as $cp){$cq=(int)$_SESSION['cart'][$cp['id']];$unit=(float)($cp['promotional_price']??$cp['price']);$cartItems[]=['id'=>(int)$cp['id'],'name'=>$cp['name'],'quantity'=>$cq,'unit'=>$unit,'line'=>$unit*$cq];$orderTotal+=$unit*$cq;$labels[]=$cp['name'].' × '.$cq;}$product=implode(', ',$labels);$qty=array_sum(array_column($cartItems,'quantity'));$orderType='carrinho';}
    elseif($productId){$st=db()->prepare('SELECT id,name FROM products WHERE id=? AND active=1');$st->execute([$productId]);$chosen=$st->fetch();if($chosen)$product=$chosen['name'];}
    if(!$cartItems)$qty=max(1,min(500,(int)($_POST['quantity']??1)));else $qty=max(1,min(500,(int)$qty));
    $personal=trim((string)($_POST['personalization_text']??''));
    $details=trim((string)($_POST['details']??''));
    if($name===''||mb_strlen($name)>140) $errors[]='Informe seu nome.';
    if(preg_match('/^[0-9+() .-]{8,40}$/',$phone)!==1) $errors[]='Informe um WhatsApp/telefone válido.';
    if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='E-mail inválido.';
    if($product===''||mb_strlen($product)>160) $errors[]='Informe o produto desejado.';
    $uploads=$_FILES['photos']??null;
    $count=0;
    if($uploads && isset($uploads['name']) && is_array($uploads['name'])) $count=count(array_filter($uploads['name'],fn($v)=>$v!==''));
    if($count>5) $errors[]='Envie no máximo 5 imagens.';
    if(!$errors) {
      $saved=[];
      try {
        $pdo=db(); $pdo->beginTransaction();
        $code='FJ'.date('ymd').strtoupper(bin2hex(random_bytes(3)));
        $q=$pdo->prepare("INSERT INTO orders(order_code,customer_name,customer_phone,customer_email,product_name,quantity,personalization_text,details,order_type,total_amount) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $q->execute([$code,$name,$phone,$email?:null,$product,$qty,$personal?:null,$details?:null,$orderType,$orderTotal]);
        $orderId=(int)$pdo->lastInsertId();
        if($cartItems){$itemStmt=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price,line_total) VALUES(?,?,?,?,?,?)');foreach($cartItems as $ci)$itemStmt->execute([$orderId,$ci['id'],$ci['name'],$ci['quantity'],$ci['unit'],$ci['line']]);}
        $dir=__DIR__.'/uploads/order-attachments';
        if(!is_dir($dir)&&!mkdir($dir,0755,true)) throw new RuntimeException('Não foi possível preparar o armazenamento das imagens.');
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if($count) foreach($uploads['name'] as $i=>$original) {
          if($original==='') continue;
          if(($uploads['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Falha ao receber uma das imagens.');
          if(($uploads['size'][$i]??0)>8*1024*1024) throw new RuntimeException('Cada imagem deve ter até 8 MB.');
          $tmp=$uploads['tmp_name'][$i]; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
          if(!isset($allowed[$mime])||!getimagesize($tmp)) throw new RuntimeException('Formato de imagem inválido. Use JPG, PNG ou WEBP.');
          $file='pedido-'.$orderId.'-'.bin2hex(random_bytes(12)).'.'.$allowed[$mime];
          if(!move_uploaded_file($tmp,$dir.'/'.$file)) throw new RuntimeException('Não foi possível salvar uma imagem enviada.');
          $saved[]=$dir.'/'.$file;
          $ins=$pdo->prepare('INSERT INTO order_photos(order_id,file_path,original_name,mime_type) VALUES(?,?,?,?)');
          $ins->execute([$orderId,'uploads/order-attachments/'.$file,mb_substr(basename((string)$original),0,255),$mime]);
        }
        $pdo->prepare("INSERT INTO order_status_history(order_id,old_status,new_status,note) VALUES(?,NULL,'recebido','Pedido enviado pelo catálogo')")->execute([$orderId]);
        $pdo->commit(); $success=$code;unset($_SESSION['cart']);
      } catch(Throwable $e) {
        if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack();
        foreach($saved as $f) if(is_file($f)) @unlink($f);
        $errors[]='Não foi possível registrar o pedido. Tente novamente ou fale conosco pelo WhatsApp.';
      }
    }
}
$co=company();$waAfter='';if($success&& !empty($co['whatsapp'])){$waAfter='https://wa.me/'.preg_replace('/\D/','',$co['whatsapp']).'?text='.rawurlencode('Olá! Acabei de enviar o pedido '.$success.' pelo catálogo F&J Artes Presentes. Podemos conversar sobre os detalhes?');}
?><!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pedido de personalização | F&J Artes Presentes</title><link rel="stylesheet" href="assets/style.css"></head><body class="install-page"><main class="install-card order-form-card"><a href="index.php" class="text-link">← Voltar ao catálogo</a><div class="brand-mark">F&J</div><h1>Peça sua personalização</h1><p>Conte como deseja seu presente. Nossa equipe receberá os detalhes e entrará em contato.</p>
<?php if($success):?><div class="notice success"><strong>Pedido recebido!</strong><br>Seu código é <strong><?=e($success)?></strong>. Nossa equipe recebeu os itens e os detalhes.</div><?php if($waAfter):?><a class="btn primary full" target="_blank" rel="noopener" href="<?=e($waAfter)?>">Continuar pelo WhatsApp</a><?php endif;?><a class="btn outline full" href="index.php">Voltar ao catálogo</a>
<?php else:?><?php foreach($errors as $err):?><div class="notice error"><?=e($err)?></div><?php endforeach;?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="product_id" value="<?=e((string)($productId??0))?>">
<label>Nome completo<input name="customer_name" maxlength="140" required value="<?=e($_POST['customer_name']??'')?>"></label>
<label>WhatsApp / telefone<input name="customer_phone" maxlength="40" required placeholder="(00) 00000-0000" value="<?=e($_POST['customer_phone']??'')?>"></label>
<label>E-mail (opcional)<input type="email" name="customer_email" value="<?=e($_POST['customer_email']??'')?>"></label>
<?php if(!empty($cartItems)):?><div class="notice success"><strong>Itens do carrinho:</strong><br><?php foreach($cartItems as $ci):?><?=e($ci['name'])?> × <?=e((string)$ci['quantity'])?> — R$ <?=number_format($ci['line'],2,',','.')?><br><?php endforeach;?>Total estimado: <strong>R$ <?=number_format((float)$orderTotal,2,',','.')?></strong></div><input type="hidden" name="product_name" value="<?=e($product)?>"><?php else:?><label>Produto desejado<input name="product_name" maxlength="160" required placeholder="Ex.: caneca personalizada" value="<?=e($_POST['product_name']??$product)?>"></label><?php endif;?>
<label>Quantidade<input type="number" name="quantity" min="1" max="500" value="<?=e((string)($_POST['quantity']??1))?>"></label>
<label>Texto que deseja na arte<textarea name="personalization_text" rows="3" placeholder="Escreva nomes, frases, datas..."><?=e($_POST['personalization_text']??'')?></textarea></label>
<label>Detalhes da personalização<textarea name="details" rows="4" placeholder="Cores, estilo, ocasião, referências e outras orientações..."><?=e($_POST['details']??'')?></textarea></label>
<label>Fotos de referência (até 5 imagens, JPG/PNG/WEBP, 8 MB cada)<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
<button class="btn primary full">Enviar pedido</button></form><?php endif;?></main></body></html>
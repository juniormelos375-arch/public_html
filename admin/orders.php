<?php
require __DIR__.'/_init.php'; require __DIR__.'/_layout.php';
$term=trim((string)($_GET['q']??'')); $status=(string)($_GET['status']??'');
$allowed=['recebido','em_analise','aguardando_cliente','aprovado','em_producao','concluido','entregue','cancelado'];
$where=[];$args=[];
if($term!==''){$where[]='(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR product_name LIKE ?)';$v='%'.$term.'%';array_push($args,$v,$v,$v,$v);}
if(in_array($status,$allowed,true)){$where[]='status=?';$args[]=$status;}
$sql='SELECT * FROM orders'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY created_at DESC LIMIT 300';
$s=db()->prepare($sql);$s->execute($args);$orders=$s->fetchAll();
$labels=['recebido'=>'Recebido','em_analise'=>'Em análise','aguardando_cliente'=>'Aguardando cliente','aprovado'=>'Aprovado','em_producao'=>'Em produção','concluido'=>'Concluído','entregue'=>'Entregue','cancelado'=>'Cancelado'];
admin_header('Pedidos de personalização','orders');?>
<div class="panel"><form class="orders-filter" method="get"><input name="q" placeholder="Buscar código, cliente, telefone ou produto" value="<?=e($term)?>"><select name="status"><option value="">Todos os status</option><?php foreach($labels as $k=>$v):?><option value="<?=e($k)?>" <?=$status===$k?'selected':''?>><?=e($v)?></option><?php endforeach;?></select><button class="btn primary">Filtrar</button><a class="btn outline" href="orders.php">Limpar</a></form><p class="muted"><?=count($orders)?> pedido(s) encontrado(s). Exibindo até 300 registros.</p>
<div class="table-wrap"><table class="orders-table"><thead><tr><th>Pedido</th><th>Cliente</th><th>Produto</th><th>Data</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($orders as $o):?><tr><td><strong><?=e($o['order_code'])?></strong></td><td><?=e($o['customer_name'])?><small><?=e($o['customer_phone'])?></small></td><td><?=e($o['product_name'])?> <small>Qtd.: <?= (int)$o['quantity']?></small></td><td><?=e(date('d/m/Y H:i',strtotime($o['created_at'])))?></td><td><span class="order-status status-<?=e($o['status'])?>"><?=e($labels[$o['status']]??$o['status'])?></span></td>

<td>
    <a class="btn outline"
       href="order.php?id=<?=(int)$o['id']?>">
        Abrir
    </a>

    <?php if ($o['status'] !== 'cancelado'): ?>
        <a class="btn outline"
           href="cancel-order.php?id=<?=(int)$o['id']?>">
            Cancelar
        </a>
    <?php endif; ?>

    <form method="post"
          action="delete-order.php"
          style="display:inline"
          onsubmit="return confirm('Tem certeza de que deseja excluir permanentemente este pedido? Esta ação não poderá ser desfeita.');">

        <input type="hidden"
               name="csrf"
               value="<?=e(csrf_token())?>">

        <input type="hidden"
               name="id"
               value="<?=(int)$o['id']?>">

        <button type="submit" class="btn outline">
            Excluir
        </button>
    </form>
</td>

</tr><?php endforeach;?>
<?php if(!$orders):?><tr><td colspan="6" class="empty">Nenhum pedido encontrado.</td></tr><?php endif;?></tbody></table></div></div>
<?php admin_footer();
<?php
require __DIR__.'/includes/bootstrap.php';
ensure_photo_trash_support();
$co=company();
$theme=['color_primary'=>setting('color_primary','#bd8298'),'color_soft'=>setting('color_soft','#e9b8c8'),'color_background'=>setting('color_background','#fffdfc'),'color_cream'=>setting('color_cream','#f7f0e9')];
$categories=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$products=db()->query('SELECT p.*,c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.featured DESC,p.created_at DESC')->fetchAll();
$cartCount=array_sum($_SESSION['cart']??[]);
$photos=db()->query('SELECT p.*, c.name AS category_name, c.theme AS category_theme FROM photos p LEFT JOIN categories c ON c.id=p.category_id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC')->fetchAll();
$themes=array_values(array_unique(array_filter(array_column($categories,'theme'))));
?><!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?=e($co['description'] ?? 'Catálogo digital de fotos da F&J Artes Presentes')?>"><title><?=e($co['name'] ?? 'F&J Artes Presentes')?> | Catálogo</title><link rel="stylesheet" href="assets/style.css"><style>:root{--pink-dark:<?=e($theme['color_primary'])?>;--pink:<?=e($theme['color_soft'])?>;--cream:<?=e($theme['color_cream'])?>;--page-bg:<?=e($theme['color_background'])?>}</style></head><body>
<div class="topbar"><?=e(setting('announcement','Onde o carinho ganha forma em porcelana e 3D ✦'))?></div><header class="header"><div class="container nav"><a class="brand" href="index.php"><?php if(!empty($co['logo_path'])):?><img src="<?=e($co['logo_path'])?>" alt="Logo"><?php else:?><span class="brand-mark">F&J</span><?php endif;?><span><?=e($co['name'] ?? 'F&J Artes Presentes')?><small><?=e($co['slogan'] ?? 'Afeto em cada detalhe')?></small></span></a><nav class="nav-links"><a href="#produtos">Produtos</a><a href="#catalogo">Galeria de inspirações</a><a href="carrinho.php">Carrinho (<?=e((string)$cartCount)?>)</a><a href="pedido.php">Personalize seu presente</a><a href="#sobre">Sobre</a><?php if(!empty($co['whatsapp'])):?><a href="https://wa.me/<?=e(preg_replace('/\D/','',$co['whatsapp']))?>" target="_blank">Contato</a><?php endif;?></nav></div></header>
<section class="hero"><div class="container"><div class="eyebrow">Catálogo digital</div><h1>Ideias que viram <em style="color:var(--pink-dark)">lembranças especiais.</em></h1><p><?=e($co['description'] ?? 'Explore nossa galeria de inspirações, organizada por temas e categorias. Escolha suas imagens favoritas e baixe tudo de uma só vez.')?></p></div></section>
<main class="section container" id="produtos"><div class="section-head"><div><h2>Nossos produtos</h2><p>Escolha seus presentes favoritos ou personalize do seu jeito.</p></div><a class="btn outline" href="carrinho.php">Ver carrinho (<?=e((string)$cartCount)?>)</a></div><div class="product-grid"><?php if(!$products):?><div class="empty">Estamos preparando nossos produtos. Em breve teremos novidades!</div><?php endif;?><?php foreach($products as $p):$unit=(float)($p['promotional_price']??$p['price']);?><article class="store-product"><?php if(!empty($p['image_path'])):?><img src="<?=e($p['image_path'])?>" alt="<?=e($p['name'])?>" loading="lazy"><?php else:?><div class="product-placeholder">F&amp;J</div><?php endif;?><div class="store-product-info"><?php if(!empty($p['category_name'])):?><span class="category-pill"><?=e($p['category_name'])?></span><?php endif;?><h3><?=e($p['name'])?></h3><p><?=e(mb_strimwidth((string)$p['description'],0,130,'…'))?></p><div class="product-prices"><?php if($p['promotional_price']!==null):?><del>R$ <?=number_format((float)$p['price'],2,',','.')?></del><?php endif;?><strong>R$ <?=number_format($unit,2,',','.')?></strong></div><form method="post" action="carrinho.php" class="add-cart-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?=$p['id']?>"><label>Qtd. <input type="number" name="quantity" value="1" min="1" max="99"></label><button class="btn primary">Adicionar ao carrinho</button></form><a class="text-link" href="pedido.php?produto=<?=$p['id']?>">Quero personalizar este produto →</a></div></article><?php endforeach;?></div></main>
<main class="section container" id="catalogo"><div class="section-head"><div><h2>Explore nosso catálogo</h2><p>Encontre a inspiração perfeita para seu próximo presente.</p></div><a class="btn primary" href="pedido.php">Solicitar personalização</a></div>
<div class="filters"><input class="field search" id="filter-search" placeholder="Buscar fotos..."><select class="field" id="filter-category"><option value="">Todas as categorias</option><?php foreach($categories as $c):?><option value="<?=e((string)$c['id'])?>"><?=e($c['name'])?></option><?php endforeach;?></select><select class="field" id="filter-theme"><option value="">Todos os temas</option><?php foreach($themes as $t):?><option value="<?=e($t)?>"><?=e($t)?></option><?php endforeach;?></select></div>
<div class="selection-bar"><span data-selected-count>0 selecionada(s)</span><button class="btn outline" data-select-visible>Selecionar visíveis</button><button class="btn primary" data-download-selected disabled>Baixar selecionadas (.ZIP)</button></div>
<div class="gallery"><?php if(!$photos):?><div class="empty">Nosso catálogo está sendo preparado. Volte em breve!</div><?php endif;foreach($photos as $p):?><article class="photo-card" data-category="<?=e((string)($p['category_id'] ?? ''))?>" data-theme="<?=e($p['category_theme'] ?? '')?>" data-search="<?=e(mb_strtolower(($p['title']??'').' '.($p['description']??'').' '.($p['category_name']??'')))?>"><input class="pick photo-select" type="checkbox" value="<?=e((string)$p['id'])?>" aria-label="Selecionar <?=e($p['title'])?>"><img class="thumb" src="<?=e($p['file_path'])?>" alt="<?=e($p['title'])?>" loading="lazy" data-full="<?=e($p['file_path'])?>"><div class="photo-info"><strong><?=e($p['title'])?></strong><?php if(!empty($p['category_name'])):?><span class="category-pill"><?=e($p['category_name'])?></span><?php endif;?></div></article><?php endforeach;?></div></main>
<section class="section" id="sobre" style="background:#fbf5f2"><div class="container" style="text-align:center"><h2 style="font:500 30px Georgia,serif"><?=e($co['name'] ?? 'F&J Artes Presentes')?></h2><p class="muted"><?=e($co['slogan'] ?? 'Afeto em cada detalhe')?></p><?php if(!empty($co['phone'])):?><p><?=e($co['phone'])?></p><?php endif;?><?php if(!empty($co['email'])):?><p><?=e($co['email'])?></p><?php endif;?><?php if(!empty($co['address'])):?><p><?=e($co['address'])?></p><?php endif;?></div></section>

<footer class="footer">
    <div class="container">

        <strong><?=e($co['name'] ?? 'F&J Artes Presentes')?></strong>

        <p>Feito com carinho, pensado para presentear.</p>

        <div class="institutional-links">
            <?php
            $footerPages = db()->query(
                "SELECT title, slug
                 FROM institutional_pages
                 WHERE status = 'published'
                 ORDER BY sort_order ASC, title ASC"
            )->fetchAll();

            foreach ($footerPages as $item):
            ?>
                <a href="page.php?slug=<?=urlencode($item['slug'])?>">
                    <?=e($item['title'])?>
                </a>
            <?php endforeach; ?>
        </div>

    </div>
</footer>

<div class="modal"><button class="modal-close" aria-label="Fechar">×</button><img src="" alt="Visualização ampliada"></div><script src="assets/app.js"></script></body></html>
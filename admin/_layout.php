<?php
function admin_header(string $title,string $active=''): void { ?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> | F&J Admin</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin-shell">

<aside class="sidebar">
<h2>F&J Admin</h2>

<a class="<?=$active==='home'?'active':''?>" href="index.php">Visão geral</a>

<a class="<?=$active==='products'?'active':''?>" href="products.php">Produtos</a>

<a class="<?=$active==='photos'?'active':''?>" href="photos.php">Fotos</a>

<a class="<?=$active==='orders'?'active':''?>" href="orders.php">Pedidos</a>

<a class="<?=$active==='trash'?'active':''?>" href="trash.php">Lixeira</a>

<a class="<?=$active==='categories'?'active':''?>" href="categories.php">Categorias e temas</a>

<a class="<?=$active==='company'?'active':''?>" href="company.php">Dados da empresa</a>

<!-- NOVO MENU -->
<a class="<?=$active==='pages'?'active':''?>" href="pages.php">Páginas institucionais</a>

<a class="<?=$active==='appearance'?'active':''?>" href="appearance.php">Identidade visual</a>

<a class="<?=$active==='backup'?'active':''?>" href="backup.php">Backup e portabilidade</a>

<a href="../index.php" target="_blank">Ver vitrine ↗</a>

<a href="logout.php">Sair</a>
</aside>

<main class="admin-main">

<div class="admin-top">
<h1><?=e($title)?></h1>
<span class="muted">
Olá, <?=e($_SESSION['admin_name']??'Administrador')?>
</span>
</div>

<?php show_flash(); ?>

<?php
}

function admin_footer(): void { ?>
</main>
</div>
</body>
</html>
<?php } ?>

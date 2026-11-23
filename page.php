
<?php
require __DIR__ . '/includes/bootstrap.php';

$co = company();

$slug = trim($_GET['slug'] ?? '');
$slug = strtolower($slug);

$stmt = db()->prepare(
    "SELECT * FROM institutional_pages
     WHERE slug = ? AND status = 'published'
     LIMIT 1"
);
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
}

$publishedPages = db()->query(
    "SELECT title, slug FROM institutional_pages
     WHERE status = 'published'
     ORDER BY sort_order ASC, title ASC"
)->fetchAll();

$theme = [
    'color_primary' => setting('color_primary', '#bd8298'),
    'color_soft' => setting('color_soft', '#e9b8c8'),
    'color_background' => setting('color_background', '#fffdfc'),
    'color_cream' => setting('color_cream', '#f7f0e9')
];

$siteName = $co['name'] ?? 'F&J Artes Presentes';
$title = $page
    ? ($page['meta_title'] ?: $page['title'])
    : 'Página não encontrada';

$description = $page
    ? ($page['meta_description'] ?: $siteName)
    : 'A página solicitada não foi encontrada.';
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?=e($title)?> | <?=e($siteName)?></title>

    <meta name="description" content="<?=e($description)?>">

    <link rel="stylesheet" href="assets/style.css">

    <style>
        :root {
            --pink-dark: <?=e($theme['color_primary'])?>;
            --pink: <?=e($theme['color_soft'])?>;
            --cream: <?=e($theme['color_cream'])?>;
            --page-bg: <?=e($theme['color_background'])?>;
        }

        body {
            background: var(--page-bg);
        }

        .institutional-content {
            max-width: 900px;
            margin: 55px auto;
            padding: 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 25px rgba(0,0,0,.05);
        }

        .institutional-content h1 {
            color: var(--pink-dark);
            font: 500 34px Georgia, serif;
            margin-bottom: 28px;
        }

        .institutional-text {
            color: #555;
            font-size: 16px;
            line-height: 1.9;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .institutional-back {
            display: inline-block;
            margin-top: 30px;
            color: var(--pink-dark);
            text-decoration: none;
            font-weight: bold;
        }

        .institutional-links {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px 22px;
        }

        .institutional-links a {
            color: inherit;
            text-decoration: none;
        }

        @media(max-width: 600px) {
            .institutional-content {
                margin: 25px 15px;
                padding: 25px 20px;
            }

            .institutional-content h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <?=e(setting('announcement', 'Onde o carinho ganha forma em porcelana e 3D ✦'))?>
</div>

<header class="header">
    <div class="container nav">

        <a class="brand" href="index.php">
            <?php if (!empty($co['logo_path'])): ?>
                <img src="<?=e($co['logo_path'])?>" alt="Logo">
            <?php else: ?>
                <span class="brand-mark">F&J</span>
            <?php endif; ?>

            <span>
                <?=e($siteName)?>
                <small><?=e($co['slogan'] ?? 'Afeto em cada detalhe')?></small>
            </span>
        </a>

        <nav class="nav-links">
            <a href="index.php">Início</a>
            <a href="index.php#produtos">Produtos</a>
            <a href="carrinho.php">Carrinho</a>
            <a href="pedido.php">Personalize seu presente</a>
        </nav>

    </div>
</header>

<?php if ($page): ?>

    <main class="container">
        <article class="institutional-content">

            <h1><?=e($page['title'])?></h1>

            <div class="institutional-text">
                <?=nl2br(e($page['content'] ?? ''))?>
            </div>

            <a class="institutional-back" href="index.php">
                ← Voltar para a loja
            </a>

        </article>
    </main>

<?php else: ?>

    <main class="container">
        <article class="institutional-content">
            <h1>Página não encontrada</h1>
            <p>Desculpe, esta página não existe ou ainda não foi publicada.</p>
            <a class="institutional-back" href="index.php">
                ← Voltar para a loja
            </a>
        </article>
    </main>

<?php endif; ?>

<footer class="footer">
    <div class="container">

        <strong><?=e($siteName)?></strong>

        <p>Feito com carinho, pensado para presentear.</p>

        <div class="institutional-links">
            <?php foreach ($publishedPages as $item): ?>
                <a href="page.php?slug=<?=urlencode($item['slug'])?>">
                    <?=e($item['title'])?>
                </a>
            <?php endforeach; ?>
        </div>

    </div>
</footer>

</body>
</html>

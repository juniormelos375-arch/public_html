
<?php
require __DIR__.'/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pages.php');
    exit;
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('Página inválida.', 'error');
    header('Location: pages.php');
    exit;
}

$stmt = db()->prepare('DELETE FROM institutional_pages WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    flash('Página excluída com sucesso!');
} else {
    flash('Página não encontrada.', 'error');
}

header('Location: pages.php');
exit;

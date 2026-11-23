
<?php
require __DIR__ . '/_init.php';

// A exclusão só pode acontecer por POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

// Verifica a proteção CSRF.
verify_csrf();

// Identifica o pedido.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    flash('Pedido inválido.', 'error');
    header('Location: orders.php');
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Confere se o pedido existe e bloqueia sua linha.
    $check = $pdo->prepare(
        'SELECT id FROM orders WHERE id = ? FOR UPDATE'
    );
    $check->execute([$id]);

    if (!$check->fetch()) {
        $pdo->rollBack();
        flash('Pedido não encontrado.', 'error');
        header('Location: orders.php');
        exit;
    }

    // Guarda os caminhos das fotos antes de apagar os registros.
    $photoQuery = $pdo->prepare(
        'SELECT file_path FROM order_photos WHERE order_id = ?'
    );
    $photoQuery->execute([$id]);
    $photoPaths = $photoQuery->fetchAll(PDO::FETCH_COLUMN);

    // Apaga os registros relacionados ao pedido.
    $pdo->prepare(
        'DELETE FROM order_items WHERE order_id = ?'
    )->execute([$id]);

    $pdo->prepare(
        'DELETE FROM order_status_history WHERE order_id = ?'
    )->execute([$id]);

    $pdo->prepare(
        'DELETE FROM order_photos WHERE order_id = ?'
    )->execute([$id]);

    // Apaga o pedido principal.
    $pdo->prepare(
        'DELETE FROM orders WHERE id = ?'
    )->execute([$id]);

    $pdo->commit();

    // Depois da confirmação no banco, remove as fotos locais.
    foreach ($photoPaths as $path) {
        delete_local_file((string)$path);
    }

    flash('Pedido excluído com sucesso.');

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('Não foi possível excluir o pedido.', 'error');
}

header('Location: orders.php');
exit;

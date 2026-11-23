
<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

$labels = [
    'recebido' => 'Recebido',
    'em_analise' => 'Em análise',
    'aguardando_cliente' => 'Aguardando cliente',
    'aprovado' => 'Aprovado',
    'em_producao' => 'Em produção',
    'concluido' => 'Concluído',
    'entregue' => 'Entregue',
    'cancelado' => 'Cancelado'
];

// Processamento do cancelamento.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $note = trim((string)($_POST['note'] ?? ''));

    if (!$id || $id < 1) {
        flash('Pedido inválido.', 'error');
        header('Location: orders.php');
        exit;
    }

    if ($note === '') {
        flash('Informe o motivo do cancelamento.', 'error');
        header('Location: cancel-order.php?id=' . $id);
        exit;
    }

    if (mb_strlen($note) > 500) {
        flash('O motivo deve ter no máximo 500 caracteres.', 'error');
        header('Location: cancel-order.php?id=' . $id);
        exit;
    }

    $pdo = db();

    try {
        $pdo->beginTransaction();

        $q = $pdo->prepare(
            'SELECT id, status FROM orders WHERE id = ? FOR UPDATE'
        );
        $q->execute([$id]);
        $order = $q->fetch();

        if (!$order) {
            $pdo->rollBack();
            flash('Pedido não encontrado.', 'error');
            header('Location: orders.php');
            exit;
        }

        if ($order['status'] === 'cancelado') {
            $pdo->rollBack();
            flash('Este pedido já está cancelado.', 'error');
            header('Location: orders.php');
            exit;
        }

        $pdo->prepare(
            'UPDATE orders SET status = ? WHERE id = ?'
        )->execute(['cancelado', $id]);

        $pdo->prepare(
            'INSERT INTO order_status_history
            (order_id, old_status, new_status, note, admin_id)
            VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $id,
            $order['status'],
            'cancelado',
            $note,
            $_SESSION['admin_id'] ?? null
        ]);

        $pdo->commit();

        flash('Pedido cancelado com sucesso.');

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('Não foi possível cancelar o pedido.', 'error');
    }

    header('Location: orders.php');
    exit;
}

// Exibição da tela de confirmação.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    flash('Pedido inválido.', 'error');
    header('Location: orders.php');
    exit;
}

$q = db()->prepare(
    'SELECT id, order_code, customer_name, status
     FROM orders WHERE id = ?'
);
$q->execute([$id]);
$order = $q->fetch();

if (!$order) {
    http_response_code(404);
    admin_header('Pedido não encontrado', 'orders');
    echo '<div class="panel">Pedido não encontrado. <a href="orders.php">Voltar</a></div>';
    admin_footer();
    exit;
}

admin_header('Cancelar pedido', 'orders');
?>

<div class="panel">
    <h2>Confirmar cancelamento</h2>

    <p>
        Você está prestes a cancelar o pedido
        <strong><?=e($order['order_code'])?></strong>.
    </p>

    <p>
        Cliente: <strong><?=e($order['customer_name'])?></strong>
    </p>

    <p>
        Status atual:
        <strong><?=e($labels[$order['status']] ?? $order['status'])?></strong>
    </p>

    <p>
        O pedido continuará salvo no sistema.
        Os itens e o histórico serão preservados.
    </p>

    <form method="post" action="cancel-order.php">
        <input type="hidden" name="csrf"
               value="<?=e(csrf_token())?>">

        <input type="hidden" name="id"
               value="<?=(int)$order['id']?>">

        <label for="note">Motivo do cancelamento</label>

        <textarea
            id="note"
            name="note"
            rows="4"
            maxlength="500"
            required
            placeholder="Ex.: Cliente desistiu da compra."
        ></textarea>

        <div class="actions" style="margin-top:16px">
            <button type="submit" class="btn primary">
                Confirmar cancelamento
            </button>

            <a href="orders.php" class="btn outline">
                Voltar sem cancelar
            </a>
        </div>
    </form>
</div>

<?php admin_footer(); ?>

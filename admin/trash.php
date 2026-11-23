<?php
require __DIR__.'/_init.php';
require __DIR__.'/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($id < 1) throw new RuntimeException('Foto inválida.');
        if ($action === 'restore') {
            $q = db()->prepare('UPDATE photos SET deleted_at=NULL WHERE id=? AND deleted_at IS NOT NULL');
            $q->execute([$id]);
            if (!$q->rowCount()) throw new RuntimeException('A foto não foi encontrada na lixeira.');
            flash('Foto restaurada e disponível novamente na biblioteca e na vitrine.');
        } elseif ($action === 'purge') {
            $q = db()->prepare('SELECT file_path FROM photos WHERE id=? AND deleted_at IS NOT NULL');
            $q->execute([$id]); $photo = $q->fetch();
            if (!$photo) throw new RuntimeException('A foto não foi encontrada na lixeira.');
            db()->prepare('DELETE FROM photos WHERE id=? AND deleted_at IS NOT NULL')->execute([$id]);
            delete_local_file($photo['file_path']);
            flash('Foto excluída definitivamente.');
        } else {
            throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
    header('Location: trash.php'); exit;
}
$rows = db()->query('SELECT p.*, c.name AS category_name FROM photos p LEFT JOIN categories c ON c.id=p.category_id WHERE p.deleted_at IS NOT NULL ORDER BY p.deleted_at DESC')->fetchAll();
admin_header('Lixeira de fotos', 'trash');
?>
<div class="panel">
  <div class="panel-heading">
    <div><h2>Fotos na lixeira (<?=count($rows)?>)</h2><p class="muted">As fotos removidas ficam guardadas aqui até serem restauradas ou excluídas definitivamente.</p></div>
    <a class="btn outline" href="photos.php">← Voltar à biblioteca</a>
  </div>
  <?php if (!$rows): ?>
    <div class="empty">A lixeira está vazia. As fotos removidas aparecerão aqui.</div>
  <?php else: ?>
    <div class="admin-photo-grid">
    <?php foreach ($rows as $r): ?>
      <article class="admin-photo-card">
        <img src="../<?=e($r['file_path'])?>" alt="<?=e($r['title'])?>">
        <div class="admin-photo-meta">
          <strong><?=e($r['title'])?></strong>
          <small><?=e($r['category_name'] ?? 'Sem categoria')?> · Removida em <?=e(date('d/m/Y H:i', strtotime($r['deleted_at'])))?></small>
          <div class="actions">
            <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn primary">Restaurar</button></form>
            <form method="post" onsubmit="return confirm('Excluir esta foto e o arquivo permanentemente? Esta ação não pode ser desfeita.')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="purge"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger">Excluir definitivamente</button></form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>

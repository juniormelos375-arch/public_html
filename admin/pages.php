
<?php
require __DIR__.'/_init.php';
require __DIR__.'/_layout.php';

$rows = db()->query(
    'SELECT * FROM institutional_pages
     ORDER BY sort_order ASC, title ASC'
)->fetchAll();

admin_header('Páginas institucionais', 'pages');
?>

<div class="panel">
    <div class="panel-heading">
        <div>
            <h2>Gerenciar páginas</h2>
            <p class="muted">
                Crie, edite, publique ou exclua páginas da sua loja.
            </p>
        </div>

        <a class="btn primary" href="page-edit.php">
            + Nova página
        </a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Endereço</th>
                    <th>Status</th>
                    <th>Ordem</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <strong><?=e($r['title'])?></strong>
                        </td>

                        <td>
                            <small><?=e($r['slug'])?></small>
                        </td>

                        <td>
                            <?php if ($r['status'] === 'published'): ?>
                                <span class="status-local">Publicado</span>
                            <?php else: ?>
                                <span class="muted">Rascunho</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= (int)$r['sort_order'] ?>
                        </td>

                        <td>
                            <a class="btn soft"
                               href="page-edit.php?id=<?=(int)$r['id']?>">
                                Editar
                            </a>

                            <?php if ($r['status'] === 'published'): ?>
                                <a class="btn outline"
                                   href="../page.php?slug=<?=urlencode($r['slug'])?>"
                                   target="_blank">
                                    Visualizar
                                </a>
                            <?php endif; ?>

                            <form class="inline-form"
                                  method="post"
                                  action="page-delete.php"
                                  onsubmit="return confirm('Tem certeza de que deseja excluir esta página? Esta ação não poderá ser desfeita.');">

                                <input type="hidden"
                                       name="csrf"
                                       value="<?=e(csrf_token())?>">

                                <input type="hidden"
                                       name="id"
                                       value="<?=(int)$r['id']?>">

                                <button type="submit" class="btn danger">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5">
                            Nenhuma página cadastrada.
                            Clique em "Nova página" para começar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>

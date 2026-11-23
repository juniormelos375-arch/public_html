
<?php
require __DIR__.'/_init.php';
require __DIR__.'/_layout.php';

$id = (int)($_GET['id'] ?? 0);

$page = [
    'title' => '',
    'slug' => '',
    'content' => '',
    'meta_title' => '',
    'meta_description' => '',
    'status' => 'draft',
    'sort_order' => 0
];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM institutional_pages WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();

    if (!$found) {
        flash('Página não encontrada.', 'error');
        header('Location: pages.php');
        exit;
    }

    $page = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($title === '') {
        $errors[] = 'Informe o título da página.';
    }

    if ($slug === '') {
        $errors[] = 'Informe um endereço válido.';
    }

    if (!in_array($status, ['draft', 'published'], true)) {
        $status = 'draft';
    }

    $page = [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'meta_title' => $meta_title,
        'meta_description' => $meta_description,
        'status' => $status,
        'sort_order' => $sort_order
    ];

    if (!$errors) {
        try {
            if ($id > 0) {
                $stmt = db()->prepare(
                    'UPDATE institutional_pages
                     SET title=?, slug=?, content=?, meta_title=?,
                         meta_description=?, status=?, sort_order=?
                     WHERE id=?'
                );

                $stmt->execute([
                    $title, $slug, $content, $meta_title,
                    $meta_description, $status, $sort_order, $id
                ]);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO institutional_pages
                     (title, slug, content, meta_title,
                      meta_description, status, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $title, $slug, $content, $meta_title,
                    $meta_description, $status, $sort_order
                ]);
            }

            flash('Página salva com sucesso!');
            header('Location: pages.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Este endereço já está sendo utilizado por outra página.';
            } else {
                $errors[] = 'Não foi possível salvar a página. Verifique os dados.';
            }
        }
    }
}

admin_header($id > 0 ? 'Editar página' : 'Nova página', 'pages');
?>

<div class="panel">
    <div class="panel-heading">
        <div>
            <h2><?= $id > 0 ? 'Editar página institucional' : 'Cadastrar nova página' ?></h2>
            <p class="muted">Preencha as informações da página da sua loja.</p>
        </div>
        <a class="btn outline" href="pages.php">Voltar</a>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="notice error"><?=e($error)?></div>
    <?php endforeach; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

        <div class="form-grid">
            <div>
                <label>Título da página</label>
                <input type="text" name="title" required
                       value="<?=e($page['title'])?>">
            </div>

            <div>
                <label>Endereço (slug)</label>
                <input type="text" name="slug" required
                       placeholder="quem-somos"
                       value="<?=e($page['slug'])?>">
                <small class="muted">Use letras sem acento, números e hífens.</small>
            </div>

            <div>
                <label>Título para SEO</label>
                <input type="text" name="meta_title"
                       value="<?=e($page['meta_title'])?>">
            </div>

            <div>
                <label>Descrição para SEO</label>
                <input type="text" name="meta_description"
                       value="<?=e($page['meta_description'])?>">
            </div>

            <div>
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?=$page['status']==='draft'?'selected':''?>>
                        Rascunho
                    </option>
                    <option value="published" <?=$page['status']==='published'?'selected':''?>>
                        Publicado
                    </option>
                </select>
            </div>

            <div>
                <label>Ordem de exibição</label>
                <input type="number" name="sort_order"
                       value="<?=(int)$page['sort_order']?>">
            </div>
        </div>

        <div style="margin-top:20px">
            <label>Conteúdo da página</label>
            <textarea name="content" rows="16"
                      placeholder="Escreva aqui o conteúdo da página..."><?=e($page['content'])?></textarea>
            <small class="muted">
                Por enquanto, o conteúdo será salvo como texto. Depois vamos preparar sua exibição na loja.
            </small>
        </div>

        <div class="actions" style="margin-top:20px">
            <button type="submit" class="btn primary">Salvar página</button>
            <a href="pages.php" class="btn outline">Cancelar</a>
        </div>
    </form>
</div>

<?php admin_footer(); ?>

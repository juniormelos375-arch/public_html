<?php
require __DIR__.'/_init.php';
ensure_photo_trash_support();
header('Content-Type: application/json; charset=utf-8');
function batch_json(array $data, int $status=200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') batch_json(['ok'=>false,'message'=>'Método não permitido.'],405);
verify_csrf();
$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) batch_json(['ok'=>false,'message'=>'Seleção inválida.'],422);
$ids = array_values(array_unique(array_filter(array_map('intval',$ids),fn($id)=>$id>0)));
if (!$ids || count($ids)>500) batch_json(['ok'=>false,'message'=>'Selecione entre 1 e 500 fotos.'],422);
$categoryRaw = (string)($_POST['category'] ?? '__keep');
$mode = (string)($_POST['description_mode'] ?? 'keep');
$description = trim((string)($_POST['description'] ?? ''));
if (!in_array($mode,['keep','replace','append','clear'],true)) batch_json(['ok'=>false,'message'=>'Ação de descrição inválida.'],422);
if ($categoryRaw === '__keep' && $mode === 'keep') batch_json(['ok'=>false,'message'=>'Nenhuma alteração foi escolhida.'],422);
if (in_array($mode,['replace','append'],true) && $description === '') batch_json(['ok'=>false,'message'=>'Informe o texto da descrição.'],422);
if (mb_strlen($description)>5000) batch_json(['ok'=>false,'message'=>'A descrição deve ter até 5.000 caracteres.'],422);
$category = null;
if ($categoryRaw !== '__keep' && $categoryRaw !== '') {
    $category=(int)$categoryRaw;
    $check=db()->prepare('SELECT id FROM categories WHERE id=?');
    $check->execute([$category]);
    if (!$check->fetchColumn()) batch_json(['ok'=>false,'message'=>'A categoria selecionada não existe.'],422);
}
try {
    $pdo=db(); $pdo->beginTransaction();
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $query=$pdo->prepare("SELECT id,description FROM photos WHERE deleted_at IS NULL AND id IN ($marks) FOR UPDATE");
    $query->execute($ids); $photos=$query->fetchAll();
    if (count($photos)!==count($ids)) throw new RuntimeException('Uma ou mais fotos não foram encontradas. Atualize a página e tente novamente.');
    foreach ($photos as $photo) {
        $sets=[]; $values=[];
        if ($categoryRaw !== '__keep') { $sets[]='category_id=?'; $values[]=$category; }
        if ($mode==='replace') { $sets[]='description=?'; $values[]=$description; }
        elseif ($mode==='append') {
            $old=trim((string)($photo['description']??''));
            $sets[]='description=?'; $values[]=$old===''?$description:$old."\n".$description;
        } elseif ($mode==='clear') { $sets[]='description=NULL'; }
        $values[]=(int)$photo['id'];
        $update=$pdo->prepare('UPDATE photos SET '.implode(',',$sets).' WHERE id=?');
        $update->execute($values);
    }
    $pdo->commit();
    batch_json(['ok'=>true,'updated'=>count($photos)]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    batch_json(['ok'=>false,'message'=>$e->getMessage()],422);
}

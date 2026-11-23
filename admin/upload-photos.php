<?php
require __DIR__.'/_init.php';
header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status=200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Método não permitido.'],405);
verify_csrf();

$files = $_FILES['images'] ?? null;
if (!$files || !isset($files['name']) || !is_array($files['name'])) {
    json_response(['ok'=>false,'message'=>'Selecione pelo menos uma imagem.'],422);
}
$count = count($files['name']);
if ($count < 1) json_response(['ok'=>false,'message'=>'Selecione pelo menos uma imagem.'],422);
if ($count > 50) json_response(['ok'=>false,'message'=>'Envie no máximo 50 imagens por lote.'],422);

$categoryId = (int)($_POST['category_id'] ?? 0);
if ($categoryId > 0) {
    $check = db()->prepare('SELECT id FROM categories WHERE id=?');
    $check->execute([$categoryId]);
    if (!$check->fetchColumn()) json_response(['ok'=>false,'message'=>'A categoria selecionada não existe.'],422);
} else {
    $categoryId = null;
}
$prefix = trim((string)($_POST['title_prefix'] ?? ''));
$defaultDescription = trim((string)($_POST['description'] ?? ''));
$titles = is_array($_POST['item_title'] ?? null) ? $_POST['item_title'] : [];
$descriptions = is_array($_POST['item_description'] ?? null) ? $_POST['item_description'] : [];
$categories = is_array($_POST['item_category'] ?? null) ? $_POST['item_category'] : [];
$defaultCategory = $categoryId;
$titles = $_POST['item_title'] ?? [];
$descriptions = $_POST['item_description'] ?? [];
$categories = $_POST['item_category'] ?? [];
if (!is_array($titles)) $titles = [];
if (!is_array($descriptions)) $descriptions = [];
if (!is_array($categories)) $categories = [];
$insert = db()->prepare('INSERT INTO photos(category_id,title,description,file_path,mime_type) VALUES(?,?,?,?,?)');
$categoryCheck = db()->prepare('SELECT id FROM categories WHERE id=?');
$categoryCheck = db()->prepare('SELECT id FROM categories WHERE id=?');
$results = [];
$success = 0;

for ($i=0; $i<$count; $i++) {
    $original = (string)($files['name'][$i] ?? '');
    $file = [
        'name'=>$original,
        'type'=>$files['type'][$i] ?? '',
        'tmp_name'=>$files['tmp_name'][$i] ?? '',
        'error'=>$files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size'=>$files['size'][$i] ?? 0,
    ];
    try {
        $path = upload_image($file);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file(dirname(__DIR__).'/'.$path);
        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = preg_replace('/[\x00-\x1F<>:"\/\\\\|?*]+/u', ' ', $base);
        $base = trim(preg_replace('/\s+/u', ' ', $base));
        if ($base === '') $base = 'Imagem '.($i+1);
        $suggestedTitle = $prefix !== '' ? $prefix.' - '.$base : $base;
        $title = trim((string)($titles[$i] ?? $suggestedTitle));
        if ($title === '') $title = $suggestedTitle;
        $title = mb_substr($title, 0, 180);
        $itemDescription = mb_substr(trim((string)($descriptions[$i] ?? $defaultDescription)), 0, 5000);
        $rawCategory = (int)($categories[$i] ?? ($categoryId ?? 0));
        $itemCategory = $rawCategory > 0 ? $rawCategory : null;
        if ($itemCategory !== null) {
            $categoryCheck->execute([$itemCategory]);
            if (!$categoryCheck->fetchColumn()) throw new RuntimeException('A categoria selecionada para esta foto não existe.');
        }
        $insert->execute([$itemCategory,$title,$itemDescription,$path,$mime]);
        $success++;
        $results[] = ['name'=>$original,'ok'=>true,'title'=>$title];
    } catch (Throwable $e) {
        $results[] = ['name'=>$original,'ok'=>false,'error'=>$e->getMessage()];
    }
}
json_response([
    'ok'=>$success > 0,
    'message'=>$success.' de '.$count.' imagem(ns) cadastrada(s).',
    'success'=>$success,
    'total'=>$count,
    'results'=>$results
], $success > 0 ? 200 : 422);

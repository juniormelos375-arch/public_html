<?php
declare(strict_types=1);
session_start();
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $d = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $d['host'], $d['port'], $d['name'], $d['charset']);
    $pdo = new PDO($dsn, $d['user'], $d['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_url(string $path=''): string {
    global $config;
    $base = trim((string)($config['app']['base_url'] ?? ''));
    if ($base === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($dir !== '' && $dir !== '/' && !str_contains($dir, '/admin') && !str_contains($dir, '/api')) $base .= $dir;
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419); exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}
function is_admin(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: ' . base_url('admin/login.php')); exit; }
}
function company(): array {
    try { return db()->query('SELECT * FROM company WHERE id=1')->fetch() ?: []; }
    catch (Throwable $e) { return ['name'=>'F&J Artes Presentes','slogan'=>'Afeto em cada detalhe']; }
}
function flash(string $msg, string $type='success'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        $f=$_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="notice '.e($f['type']).'">'.e($f['msg']).'</div>';
    }
}
function upload_image(array $file, string $prefix='photo'): string {
    global $config;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no envio da imagem.');
    if (($file['size'] ?? 0) > ((int)$config['app']['max_upload_mb'] * 1024 * 1024)) throw new RuntimeException('Imagem acima do limite de tamanho.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Formato inválido. Use JPG, PNG, WEBP ou GIF.');
    $dir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('A pasta uploads precisa ter permissão de escrita.');
    $name = $prefix . '-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) throw new RuntimeException('Não foi possível salvar a imagem.');
    return 'uploads/' . $name;
}
function delete_local_file(?string $path): void {
    if (!$path || !str_starts_with($path, 'uploads/')) return;
    $full = dirname(__DIR__) . '/' . $path;
    if (is_file($full)) @unlink($full);
}

function setting(string $key, string $default=''): string {
    try { $q=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?'); $q->execute([$key]); $v=$q->fetchColumn(); return $v===false?$default:(string)$v; } catch(Throwable $e) { return $default; }
}

function ensure_photo_trash_support(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $column = db()->query("SHOW COLUMNS FROM photos LIKE 'deleted_at'")->fetch();
        if (!$column) {
            db()->exec("ALTER TABLE photos ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER created_at");
            db()->exec("ALTER TABLE photos ADD INDEX idx_photos_deleted_at (deleted_at)");
        }
    } catch (Throwable $e) {
        // A tabela ainda pode não existir durante a instalação inicial.
    }
}

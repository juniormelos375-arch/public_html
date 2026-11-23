<?php
require dirname(__DIR__).'/includes/bootstrap.php';
ensure_photo_trash_support();
header('Content-Type: application/json; charset=utf-8');
try {
    if (!class_exists('ZipArchive')) throw new RuntimeException('A hospedagem precisa habilitar a extensão PHP zip.');
    $data=json_decode(file_get_contents('php://input'),true);
    $ids=array_values(array_unique(array_filter(array_map('intval',$data['ids']??[]),fn($v)=>$v>0)));
    if (!$ids) throw new RuntimeException('Selecione ao menos uma foto.');
    if (count($ids)>200) throw new RuntimeException('Selecione no máximo 200 fotos por vez.');
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $s=db()->prepare("SELECT id,title,file_path FROM photos WHERE deleted_at IS NULL AND id IN ($marks)");
    $s->execute($ids);$rows=$s->fetchAll();
    if (!$rows) throw new RuntimeException('Nenhuma foto encontrada.');
    $tmp=tempnam(sys_get_temp_dir(),'fjzip');
    $zip=new ZipArchive();
    if ($zip->open($tmp,ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Não foi possível criar o ZIP.');
    $n=0;
    foreach($rows as $row){
        $path=dirname(__DIR__).'/'.$row['file_path'];
        if (!is_file($path)) continue;
        $ext=pathinfo($path,PATHINFO_EXTENSION);
        $safe=preg_replace('/[^a-zA-Z0-9_-]+/','-',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$row['title']) ?: 'foto');
        $zip->addFile($path, sprintf('%02d-%s.%s',++$n,trim($safe,'-') ?: 'foto',$ext));
    }
    $zip->close();
    if ($n===0) { @unlink($tmp); throw new RuntimeException('Os arquivos das fotos não foram encontrados.'); }
    header_remove('Content-Type');header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="fj-fotos-selecionadas.zip"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);exit;
} catch(Throwable $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}

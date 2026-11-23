<?php
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();
if(!class_exists('ZipArchive')){http_response_code(500);exit('A extensão PHP zip precisa estar habilitada.');}
$tmp=tempnam(sys_get_temp_dir(),'fjmedia');$zip=new ZipArchive();$zip->open($tmp,ZipArchive::OVERWRITE);
$root=dirname(__DIR__);$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/uploads',FilesystemIterator::SKIP_DOTS));
foreach($it as $f){if($f->isFile())$zip->addFile($f->getPathname(),'uploads/'.substr($f->getPathname(),strlen($root.'/uploads/')));}
$zip->close();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="fj-catalogo-midias-'.date('Y-m-d').'.zip"');header('Content-Length: '.filesize($tmp));readfile($tmp);unlink($tmp);

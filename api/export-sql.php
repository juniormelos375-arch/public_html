<?php
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();
header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="fj-catalogo-backup-'.date('Y-m-d').'.sql"');
echo "SET FOREIGN_KEY_CHECKS=0;\n";
$tables=['admins','company','categories','photos'];
foreach($tables as $table){
 echo "\nDROP TABLE IF EXISTS `$table`;\n";
 $create=db()->query("SHOW CREATE TABLE `$table`")->fetch();echo $create['Create Table'].";\n";
 $rows=db()->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_NUM);
 foreach($rows as $row){$vals=[];foreach($row as $v)$vals[]=$v===null?'NULL':db()->quote((string)$v);echo "INSERT INTO `$table` VALUES (".implode(',',$vals).");\n";}
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";

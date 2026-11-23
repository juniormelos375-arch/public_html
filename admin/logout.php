<?php
require dirname(__DIR__).'/includes/bootstrap.php';
$_SESSION=[];session_destroy();header('Location: login.php');exit;

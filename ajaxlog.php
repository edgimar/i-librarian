<?php
include_once 'data.php';
session_write_close();

$rem = array("\\", "/");
$_GET['user'] = str_replace($rem, '', $_GET['user']);

$log = $temp_dir . DIRECTORY_SEPARATOR .$_GET['user'].'-librarian-import.log';

if(!file_exists($log) || !is_readable($log)) die();
$string = file_get_contents($log);
die($string);
?>
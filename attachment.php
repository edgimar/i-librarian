<?php
include_once 'data.php';
include_once 'functions.php';

$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.'supplement';

if (!empty($_GET['attachment']) && is_file($path.DIRECTORY_SEPARATOR.$_GET['attachment']) && !preg_match("/\\\\\\//", $_GET['attachment'])) {

$filename = substr(urldecode($_GET['attachment']),5);

$content = file_get_contents($path.DIRECTORY_SEPARATOR.$_GET['attachment']);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Pragma: no-cache");
	header("Expires: 0");
	print $content;
}
?>
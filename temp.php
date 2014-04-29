<?php

include_once 'data.php';

$pdf = '';
if (preg_match('/^lib_\S{15}\.pdf$/i', $_GET['tempfile']) > 0) $pdf = @file_get_contents ($temp_dir.DIRECTORY_SEPARATOR.$_GET['tempfile']);

if (!empty($pdf)) {

	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="'.$_GET['tempfile'].'"');
	print $pdf;
} else {

	print "File $_GET[tempfile] does not exist.";
}
?>
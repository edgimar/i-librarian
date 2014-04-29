<?php
$id = sprintf("%05d",$_GET['id']);
$delimiter = PHP_EOL;
$output = 'var tinyMCEImageList = new Array(';
$directory = dirname(__FILE__).DIRECTORY_SEPARATOR."library".DIRECTORY_SEPARATOR."supplement";

if (is_dir($directory)) {

	$direc = opendir($directory);

	while ($file = readdir($direc)) {

		if (is_file($directory.DIRECTORY_SEPARATOR.$file) && preg_match('/^'.$id.'.*/i', $file)) {

			$isimage = null;
			$image_array = array();
			$image_array = @getimagesize('library/supplement/'.$file);
			$image_mime = $image_array['mime'];
			if($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png') $isimage = true;

			if($isimage) {

				$output .= $delimiter
				. '["'
				. utf8_encode(substr($file,5))
				. '", "'
				. utf8_encode("library/supplement/$file")
				. '"],';
			}
		}
	}

	$output = substr($output, 0, -1);
	$output .= $delimiter;

	closedir($direc);
}

$output .= ');';

header('Content-type: text/javascript');

echo $output;
?>
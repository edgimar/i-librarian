<?php
include_once 'data.php';
include_once '../functions.php';
session_write_close();

// Files
$file = preg_replace('/^0-9\.pdf/', '', $_GET['file']);
$png = '../library/pngs/' . $file . '.1.png';
$icon = $temp_dir . DIRECTORY_SEPARATOR . $file . '.1.icon.png';

// Paths
chdir('..');
$pdf_path = getcwd() . DIRECTORY_SEPARATOR . 'library';
$png_path = getcwd() . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';
chdir('m');

// Content type
header('Content-Type: image/png');

// Output from cache
if (is_readable($icon) &&
        filemtime($pdf_path . DIRECTORY_SEPARATOR . $file) < filemtime($icon)) {
    echo file_get_contents($icon);
    die();
}

// Make PNG if not found
if (!is_readable($png) ||
        filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.1.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
    exec(select_ghostscript() . " -dSAFER -sDEVICE=png16m -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE -dFirstPage=1 -dLastPage=1 -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . ".1.png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");
}

if (!is_readable($png)) {
    // Error! Ghostscript DOES NOT WORK
    $image_p = @imagecreate(360, 240);
    $background_color = imagecolorallocate($image_p, 255, 255, 255);
    $text_color = imagecolorallocate($image_p, 255, 0, 0);
    imagestring($image_p, 3, 20, 20,  "Error! Program Ghostscript not functional.", $text_color);
    
} else {

    // Icon dimensions
    $new_width = 360;
    $new_height = 240;

    // Resample
    list($width, $height) = getimagesize($png);
    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefrompng($png);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $width / 1.5);
}

//Color index
imagetruecolortopalette($image_p, false, 255);

// Output
imagepng($image_p, $icon, 1);
imagepng($image_p, null, 1);

imagedestroy($image_p);
?>

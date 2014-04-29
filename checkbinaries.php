<?php
include_once 'data.php';
include_once 'functions.php';

session_write_close();

if ($_GET['binary'] == 'pdftotext') {

    exec(select_pdftotext().' test.pdf "'.$temp_dir.DIRECTORY_SEPARATOR.'test.txt"');

    if(file_exists($temp_dir.DIRECTORY_SEPARATOR.'test.txt')) {
        unlink($temp_dir.DIRECTORY_SEPARATOR.'test.txt');
        die ('OK');
    } else {
        die ();
    }

} elseif ($_GET['binary'] == 'pdfinfo') {

    exec(select_pdfinfo().' test.pdf', $output);

    if(!empty($output)) {
        die ('OK');
    } else {
        die ();
    }

} elseif ($_GET['binary'] == 'pdftohtml') {

    exec(select_pdftohtml().' -q -noframes -enc UTF-8 -nomerge -c -xml test.pdf "'.$temp_dir.DIRECTORY_SEPARATOR.'test"');

    if(file_exists($temp_dir.DIRECTORY_SEPARATOR.'test.xml')) {
        unlink($temp_dir.DIRECTORY_SEPARATOR.'test.xml');
        die ('OK');
    } else {
        die ();
    }

} elseif ($_GET['binary'] == 'ghostscript') {

    exec(select_ghostscript().' -sDEVICE=png16m -r15 -dTextAlphaBits=1 -dGraphicsAlphaBits=1 -dFirstPage=1 -dLastPage=1 -o "'.$temp_dir.DIRECTORY_SEPARATOR.'test.png" test.pdf');

    if(file_exists($temp_dir.DIRECTORY_SEPARATOR.'test.png')) {
        unlink($temp_dir.DIRECTORY_SEPARATOR.'test.png');
        die ('OK');
    } else {
        die ();
    }

} elseif ($_GET['binary'] == 'bibutils') {

    exec(select_bibutil("ris2xml").' -i unicode test.ris > "'.$temp_dir.DIRECTORY_SEPARATOR.'test2.xml"');

    if(is_readable($temp_dir.DIRECTORY_SEPARATOR.'test2.xml') && filesize($temp_dir.DIRECTORY_SEPARATOR.'test2.xml') > 0) {
        unlink($temp_dir.DIRECTORY_SEPARATOR.'test2.xml');
        die ('OK');
    } else {
        if (is_writable($temp_dir.DIRECTORY_SEPARATOR.'test2.xml')) unlink($temp_dir.DIRECTORY_SEPARATOR.'test2.xml');
        die ();
    }

} elseif ($_GET['binary'] == 'pdftk') {

    exec(select_pdftk().'test.pdf dump_data output "'.$temp_dir.DIRECTORY_SEPARATOR.'test-pdftk.txt"');

    if(is_readable($temp_dir.DIRECTORY_SEPARATOR.'test-pdftk.txt') && filesize($temp_dir.DIRECTORY_SEPARATOR.'test-pdftk.txt') > 0) {
        unlink($temp_dir.DIRECTORY_SEPARATOR.'test-pdftk.txt');
        die ('OK');
    } else {
        die ();
    }
}
?>

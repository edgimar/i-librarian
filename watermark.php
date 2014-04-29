<?php
include_once 'data.php';
session_write_close();

if ($_SESSION['watermarks'] == 'nocopy') {
    $text = 'DO NOT COPY';
} elseif ($_SESSION['watermarks'] == 'confidential') {
    $text = 'CONFIDENTIAL';
} elseif ($_SESSION['watermarks'] == '') {
    $text = '';
}

die($text);
?>
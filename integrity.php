<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$allowed_databases = array ('library', 'fulltext', 'users');

if (!empty($_GET['db']) && in_array($_GET['db'], $allowed_databases)) {
    if ($_GET['db'] == 'users') {
        database_connect($usersdatabase_path, 'users');
    } else {
        database_connect($database_path, $_GET['db']);
    }
    $result = $dbHandle->query('PRAGMA integrity_check');
    $answer = $result->fetchColumn();
    $dbHandle = null;
    print $answer;
}
?>
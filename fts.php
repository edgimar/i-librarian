<?php
include_once 'data.php';
include_once 'functions.php';

/*FULL TEXT INDEX
database_connect($database_path, 'fulltext');
$dbHandle->exec("CREATE VIRTUAL TABLE full_text_fts USING fts3(full_text)");
$dbHandle->exec("INSERT INTO full_text_fts (docid,full_text) SELECT id,full_text FROM full_text");
*/

//database_connect($database_path, 'fulltext');
//$dbHandle->exec("DROP TABLE full_text_fts");

#print_r($dbHandle->errorInfo);
$dbHandle = null;
?>
<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!empty($_GET['file']) && !empty($_GET['page'])) {
    
    if (substr($_GET['file'], 0, 4) == 'lib_') die();
    
    $userID = intval($_SESSION['user_id']);
    $page = intval($_GET['page']);
    $file = preg_replace('/[^0-9\.pdf]/', '', $_GET['file']);

    database_connect($database_path, 'history');
    
    $dbHandle->exec("CREATE TABLE IF NOT EXISTS bookmarks (
                    id INTEGER PRIMARY KEY,
                    userID INTEGER NOT NULL DEFAULT '',
                    file TEXT NOT NULL DEFAULT '',
                    page INTEGER NOT NULL DEFAULT 1,
                    UNIQUE(userID,file)
                    )");
    
    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM bookmarks WHERE userID=$userID AND file='$file'");
    if ($page > 1) $dbHandle->exec("INSERT INTO bookmarks (userID,file,page) VALUES ($userID,'$file',$page)");
    $dbHandle->commit();
    $dbHandle = null;
}
?>

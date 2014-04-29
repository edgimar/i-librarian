<?php
if(!empty($_GET['file'])) {
    
    include_once 'data.php';
    include_once 'functions.php';
    session_write_close();
    
    $path = dirname(__FILE__).DIRECTORY_SEPARATOR.'library';
    $file = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
    $file_name = $path.DIRECTORY_SEPARATOR.$file;

    if (is_readable($file_name)) {
        
        //ADD WATERMARKS
        if ($_SESSION['watermarks'] == 'nocopy') {
            $temp_file = $temp_dir.DIRECTORY_SEPARATOR.$file.'-nocopy.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk().'"'.$file_name.'" multistamp "'.dirname(__FILE__).DIRECTORY_SEPARATOR.'nocopy.pdf'.'"  output "'.$temp_file.'"', $ret);
            $file_name = $temp_file;
        } elseif ($_SESSION['watermarks'] == 'confidential') {
            $temp_file = $temp_dir.DIRECTORY_SEPARATOR.$file.'-confidential.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk().'"'.$file_name.'" multistamp "'.dirname(__FILE__).DIRECTORY_SEPARATOR.'confidential.pdf'.'"  output "'.$temp_file.'"', $ret);
            $file_name = $temp_file;
        }
        
        //ATTACH FILES
        if (isset($_GET['attachments'])) {
            $supfile_arr = array ();
            
            //ATTACH PDF NOTES
            if (in_array('notes', $_GET['attachments'])) {
                database_connect($database_path, 'library');
                $userid = $dbHandle->quote($_SESSION['user_id']);
                $qfile = $dbHandle->quote($_GET['file']);
                
                //ATTACH PDF NOTES FROM USERS
                if (in_array('allusers', $_GET['attachments'])) {
                    $result = $dbHandle->query("SELECT id,annotation,page FROM annotations
                                                WHERE filename=$qfile
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
                    
                //ATTACH PDF NOTES FROM THIS USER
                } else {
                    $result = $dbHandle->query("SELECT id,annotation,page FROM annotations
                                                WHERE filename=$qfile
                                                AND userID=$userid
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
                }
                $notetxt = '';
                while ($annotations = $result -> fetch(PDO::FETCH_NAMED)) {
                    $notetxt = $notetxt.'Page '.$annotations['page'].', note '.$annotations['id'].PHP_EOL.PHP_EOL.$annotations['annotation'].PHP_EOL.PHP_EOL;
                }
                if (!empty($notetxt)) {
                    file_put_contents($temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'annotations.txt', $notetxt);
                    $supfile_arr[] = $temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'annotations.txt';
                }
            }
            
            //ATTACH RICH-TEXT NOTES
            if (in_array('richnotes', $_GET['attachments'])) {
                database_connect($database_path, 'library');
                $userid = $dbHandle->quote($_SESSION['user_id']);
                $qfile = $dbHandle->quote($_GET['file']);
                $result = $dbHandle->query("SELECT notes FROM notes
                                            WHERE fileID=(SELECT id FROM library WHERE file=$qfile)
                                            AND userID=$userid LIMIT 1");
                $notetxt = '';
                $notetxt = $result -> fetchColumn();
                if (!empty($notetxt)) {
                    $notetxt = '<!DOCTYPE html><html style="width:100%;height:100%"><head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <title>I, Librarian - 2.4 Notes</title></head><body>'.$notetxt.'</body></html>';
                    file_put_contents($temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'richnotes.html', $notetxt);
                    $supfile_arr[] = $temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'richnotes.html';
                }
            }
            
            //ATTACH SUPPLEMENTARY FILES
            if (in_array('supp', $_GET['attachments'])) {
                $supfiles = array ();
	        $integer = sprintf ("%05d", intval($_GET['file']));
                $supfiles = glob($path.DIRECTORY_SEPARATOR.'supplement'.DIRECTORY_SEPARATOR.$integer.'*');
                $supfile_arr = array_merge($supfiles, $supfile_arr);
            }
            $supfile_str = join ('" "', $supfile_arr);
            $supfile_str = trim($supfile_str);
            if (!empty($supfile_str)) {
                $temp_file = $temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.$file.'-attachments.pdf';
                system(select_pdftk().'"'.$file_name.'" attach_files "'.$supfile_str.'" output "'.$temp_file.'"', $ret);
                $file_name = $temp_file;
            }
        }
        
        //RENDER FINISHED PDF
        header("Content-type: application/pdf");
        if (!isset($_GET['mode'])) header("Content-Disposition: inline; filename=$file");
        if (isset($_GET['mode']) && $_GET['mode'] == 'download') header("Content-Disposition: attachment; filename=$file");
        header("Pragma: no-cache");
        header("Expires: 0");
        print file_get_contents($file_name);
    }
} else {
    die();
}
?>

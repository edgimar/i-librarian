<?php

include_once 'data.php';

if (isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    if (!empty($_POST['submit'])) {

        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';
        print '<tr><td class="details alternating_row" style="font-weight:bold">Detection of duplicate records</td></tr></table>';

        while (list($key, $file_to_delete) = each($_POST)) {

            if (preg_match('/id:/', $key)) {

                $array = explode("->", $file_to_delete);
                $files_to_delete[$array[0]] = $array[1];
            }
        }

        if (empty($files_to_delete))
            die('No items to delete.');

        while (list($key, $file_to_keep) = each($files_to_delete)) {

            if (array_key_exists($file_to_keep, $files_to_delete))
                unset($files_to_delete[$file_to_keep]);
        }

        reset($files_to_delete);

        while (list($id_to_delete, $id_to_keep) = each($files_to_delete)) {

            database_connect($database_path, 'library');

            $dbHandle->beginTransaction();

            $file_to_delete = $dbHandle->query("SELECT file FROM library WHERE id='$id_to_delete' LIMIT 1");
            $file_to_keep = $dbHandle->query("SELECT file FROM library WHERE id='$id_to_keep' LIMIT 1");

            $file_to_delete = $file_to_delete->fetchColumn();
            $file_to_keep = $file_to_keep->fetchColumn();

            ##########	update shelves	##########

            $dbHandle->exec("DELETE FROM shelves WHERE fileID=" . intval($id_to_delete));

            ##########	categories	##########

            $dbHandle->exec("DELETE FROM filescategories WHERE fileID=" . intval($id_to_delete));

            ##########	desktop	##########

            $dbHandle->exec("DELETE FROM projectsfiles WHERE fileID=" . intval($id_to_delete));

            ##########	PDF annotations ##########

            $dbHandle->exec("DELETE FROM yellowmarkers WHERE filename='$file_to_delete'");
            $dbHandle->exec("DELETE FROM annotations WHERE filename='$file_to_delete'");

            ##########	update notes	##########

            $result = $dbHandle->query("SELECT notesID,userID FROM notes WHERE fileID=$id_to_delete");

            while ($notes = $result->fetch(PDO::FETCH_ASSOC)) {

                $result2 = $dbHandle->query("SELECT notesID FROM notes WHERE userID=$notes[userID] AND fileID=$id_to_keep");
                $notes2 = $result2->fetchColumn();
                $result2 = null;

                if (empty($notes2)) {
                    $dbHandle->exec("UPDATE notes SET fileID=$id_to_keep WHERE notesID=$notes[notesID]");
                } else {
                    $dbHandle->exec("UPDATE notes SET notes=notes || ' ' || (SELECT notes FROM notes WHERE notesID=$notes[notesID]) WHERE notesID=$notes2");
                    $dbHandle->exec("DELETE FROM notes WHERE notesID=$notes[notesID]");
                }
            }

            $result = null;

            ##########	delete record from library	##########

            $delete = $dbHandle->exec("DELETE FROM library WHERE id=" . intval($id_to_delete));

            $dbHandle->commit();

            ##########	discussion	##########

            $discussion_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'filediscussion.sq3';
            if (file_exists($database_path)) {
                $database_path_query = $dbHandle->quote($discussion_path);
                $dbHandle->exec("ATTACH DATABASE " . $database_path_query . " AS database3");
                $dbHandle->exec("DELETE FROM database3.discussion WHERE fileID=$id_to_delete");
                $dbHandle->exec("DETACH DATABASE " . $database_path_query);
            }
            
            
            ##########	PDF bookmarks	##########

            $history_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'history.sq3';
            if (file_exists($history_path)) {
                $database_path_query = $dbHandle->quote($history_path);
                $dbHandle->exec("ATTACH DATABASE " . $database_path_query . " AS database4");
                $dbHandle->exec("DELETE FROM database4.bookmarks WHERE file='$file_to_delete'");
                $dbHandle->exec("DETACH DATABASE " . $database_path_query);
            }

            $dbHandle = null;

            ##########	delete full text file	##########

            if (is_writable("library" . DIRECTORY_SEPARATOR . "$file_to_delete") && is_file("library" . DIRECTORY_SEPARATOR . $file_to_delete))
                $unlink = unlink("library" . DIRECTORY_SEPARATOR . $file_to_delete);

            $integer1 = sprintf("%05d", intval($file_to_delete));
            $png_files = glob('library/pngs/' . $integer1 . '*.png', GLOB_NOSORT);

            foreach ($png_files as $png_file) {
                @unlink($png_file);
            }

            ##########	rename supplementary files	##########

            $supplementary_files = glob('library/supplement/' . $integer1 . '*', GLOB_NOSORT);

            foreach ($supplementary_files as $supplementary_file) {
                rename($supplementary_file, "library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . substr($file_to_keep, 0, 5) . substr(basename($supplementary_file), 5));
            }

            ##########	delete record from clipboard	##########

            if (!empty($_SESSION['session_clipboard'])) {

                $key = array_search($id_to_delete, $_SESSION['session_clipboard']);
                unset($_SESSION['session_clipboard'][$key]);
            }

            ##########	delete full text index	##########

            database_connect($database_path, 'fulltext');
            $dbHandle->exec("DELETE FROM full_text WHERE fileID=" . intval($id_to_delete));
            $dbHandle = null;

            // CLEAR SHELF AND DESK CACHE
            cache_clear();

            if ($delete == 1)
                print '&nbsp;Record ' . $id_to_delete . ' deleted.<br>';
        }
    } elseif (isset($_GET['find_duplicates'])) {

        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';
        print '<tr><td class="details alternating_row" style="font-weight:bold">Detection of duplicate records</td></tr>';
        print '<tr><td class="details">Possible Duplicates&#172;</td></tr></table>';

        print '<form action="duplicates.php" method="POST">';

        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

        print '<tr><td class="details alternating_row" style="width: 5.8em;text-align: right">ID&nbsp;&nbsp;</td><td class="details alternating_row">Title</td>';

        $duplicates_array = array();

        database_connect($database_path, 'library');

        $dbHandle->exec("PRAGMA cache_size = 200000");
        $dbHandle->exec("PRAGMA temp_store = MEMORY");
        $dbHandle->exec("PRAGMA synchronous = OFF");

        $dbHandle->exec("CREATE TEMPORARY TABLE search_result (id INTEGER PRIMARY KEY,title)");
        $dbHandle->exec("INSERT INTO search_result SELECT id,title FROM library");
        $result = $dbHandle->query("SELECT id,title FROM search_result ORDER BY id ASC");

        $i = 0;

        while ($title = $result->fetch(PDO::FETCH_ASSOC)) {

            $title_query = $dbHandle->quote($title['title']);
            $id_result = $dbHandle->query("SELECT id,title FROM search_result WHERE id > $title[id] AND title=$title_query LIMIT 1");

            $id_result = $id_result->fetch(PDO::FETCH_ASSOC);

            if (!empty($id_result['id'])) {
                $duplicates_array[$i][$title['id']] = $title['title'];
                $duplicates_array[$i][$id_result['id']] = $id_result['title'];
                $i = $i + 1;
            }
        }

        $dbHandle = null;

        while (list($key, $duplicate_pair) = each($duplicates_array)) {

            ksort($duplicate_pair);

            $id1 = key($duplicate_pair);
            $title1 = current($duplicate_pair);

            next($duplicate_pair);

            $id2 = key($duplicate_pair);
            $title2 = current($duplicate_pair);

            $duplicate_pair = array();


            print "\n<tr><td style=\"width: 5.8em\">";
            print "\n<div style=\"float: right\"><a href=\"stable.php?id=$id1\" target=\"_blank\">$id1&nbsp;&nbsp;</a></div><input type=\"checkbox\" name=\"id:$id1-$id2\" value=\"$id1->$id2\">";
            print "\n</td>";
            print "\n<td>";
            print "\n$title1";
            print "\n</td></tr>";
            print "\n<tr><td style=\"width: 5.8em\">";
            print "\n<div style=\"float: right\"><a href=\"stable.php?id=$id2\" target=\"_blank\">$id2&nbsp;&nbsp;</a></div><input type=\"checkbox\" name=\"id:$id1-$id2\" value=\"$id2->$id1\">";
            print "\n</td>";
            print "\n<td>";
            print "\n$title2";
            print "\n<br><br></td></tr>";
        }

        if (count($duplicates_array) == 0)
            print "\n<tr><td colspan=2>No duplicates found.</td></tr>";

        print "\n</table>";

        if (count($duplicates_array) > 0) {

            print "\n&nbsp;<input type=\"submit\" name=\"submit\" value=\"Delete selected\">";

            print "\n<br><b>&nbsp;Warning! This action cannot be undone.</b>";
        }

        print "\n</form>";
    } else {
        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';
        print '<tr><td class="details alternating_row" style="font-weight:bold">Detection of duplicate records</td></tr>';
        print '<tr><td class="details">Searching for duplicates.&nbsp;<img src="img/ajaxloader.gif"></td></tr></table>';
    }
} else {
    print 'Super user or User permissions required.';
}
?>
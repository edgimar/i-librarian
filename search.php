<?php
$microtime1 = microtime(true);

include_once 'data.php';
include_once 'functions.php';

function save_search($filename, $searches) {
    $searches_content = gzcompress(serialize($searches), 1);
    file_put_contents($filename, $searches_content, LOCK_EX);
}

if (isset($_GET['newsearch'])) {
    unset($_SESSION['session_anywhere']);
    unset($_SESSION['session_anywhere_separator']);
    unset($_SESSION['session_authors']);
    unset($_SESSION['session_authors_separator']);
    unset($_SESSION['session_journal']);
    unset($_SESSION['session_journal_separator']);
    unset($_SESSION['session_title']);
    unset($_SESSION['session_title_separator']);
    unset($_SESSION['session_abstract']);
    unset($_SESSION['session_abstract_separator']);
    unset($_SESSION['session_keywords']);
    unset($_SESSION['session_keywords_separator']);
    unset($_SESSION['session_secondary_title']);
    unset($_SESSION['session_secondary_title_separator']);
    unset($_SESSION['session_affiliation']);
    unset($_SESSION['session_affiliation_separator']);
    unset($_SESSION['session_search_id']);
    unset($_SESSION['session_year']);
    unset($_SESSION['session_fulltext']);
    unset($_SESSION['session_fulltext_separator']);
    unset($_SESSION['session_notes']);
    unset($_SESSION['session_notes_separator']);
    unset($_SESSION['session_category']);
    unset($_SESSION['session_whole_words']);
    unset($_SESSION['session_case']);
    unset($_SESSION['session_search-metadata']);
    unset($_SESSION['session_search-pdfs']);
    unset($_SESSION['session_search-notes']);
    unset($_SESSION['session_include-categories']);
    unset($_SESSION['rating[]']);
    die();
}

if (isset($_GET['savesearch']) && !empty($_GET['searchname'])) {

    database_connect($database_path, 'library');

    $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,:searchfield,:searchvalue)");

    $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
    $stmt2->bindParam(':searchfield', $searchfield, PDO::PARAM_STR);
    $stmt2->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

    $dbHandle->beginTransaction();

    $user = intval($_SESSION['user_id']);
    $searchname = 'advancedsearch#' . trim($_GET['searchname']);

    $stmt->execute();

    unset($_GET['_']);
    unset($_GET['savesearch']);
    unset($_GET['searchname']);
    reset($_GET);
    while (list($key, $field) = each($_GET)) {
        if (is_array($_GET[$key])) {
            while (list(, $value2) = each($_GET[$key])) {
                $searchfield = $key . '[]';
                $searchvalue = $value2;
                $stmt2->execute();
            }
        } elseif ($key != 'from' && $key != 'limit' && $key != 'display' && $key != 'orderby' && !empty($_GET[$key])) {
            $searchfield = $key;
            $searchvalue = $field;
            $stmt2->execute();
        }
    }

    $dbHandle->commit();
    die();
}

if (isset($_GET['loadsearch']) && !empty($_GET['searchname'])) {

    database_connect($database_path, 'library');

    $stmt = $dbHandle->prepare("SELECT searchfield,searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $user = $_SESSION['user_id'];
    $searchname = "advancedsearch#" . trim($_GET['searchname']);

    $stmt->execute();

    $url_string_array = array();

    while ($search = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url_string_array[] = urlencode($search['searchfield']) . '=' . urlencode($search['searchvalue']);
    }
    $url_string = join('&', $url_string_array);
    die($url_string);
}

if (isset($_GET['deletesearch']) && !empty($_GET['searchname'])) {

    database_connect($database_path, 'library');

    $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $user = $_SESSION['user_id'];
    $searchname = "advancedsearch#" . trim($_GET['searchname']);

    $stmt->execute();

    die('OK');
}

if (isset($_GET['renamesearch']) && !empty($_GET['searchname']) && !empty($_GET['searchname2'])) {

    database_connect($database_path, 'library');

    $stmt = $dbHandle->prepare("UPDATE searches SET searchname=:searchname2 WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
    $stmt->bindParam(':searchname2', $searchname2, PDO::PARAM_STR);

    $user = $_SESSION['user_id'];
    $searchname = "advancedsearch#" . trim(urldecode($_GET['searchname']));
    $searchname2 = "advancedsearch#" . trim(urldecode($_GET['searchname2']));

    $stmt->execute();

    die('OK');
}

if (!isset($_GET['project'])) {
    $project = '';
} else {
    $project = $_GET['project'];
}

if (!isset($_SESSION['limit'])) {
    $limit = 10;
} else {
    settype($_SESSION['limit'], "integer");
    $limit = $_SESSION['limit'];
}

if (!isset($_SESSION['orderby'])) {
    $orderby = 'id';
} else {
    $orderby = $_SESSION['orderby'];
}

if (!isset($_SESSION['display'])) {
    $display = 'summary';
} else {
    $display = $_SESSION['display'];
}

if (!isset($_GET['from'])) {
    $from = '0';
} else {
    settype($_GET['from'], "integer");
    $from = $_GET['from'];
}

if (empty($_GET['select']) ||
        ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard')) {

    $_GET['select'] = 'library';
}

if (!empty($_GET['searchmode'])) {
    
     ######## REGISTER SESSION VARIABLES ##############

    $get_array = array('anywhere', 'authors', 'affiliation', 'title', 'journal', 'secondary_title', 'abstract', 'keywords',
        'year', 'fulltext', 'notes', 'category', 'whole_words', 'case', 'rating', 'search_id', 'search-metadata', 'search-pdfs', 'search-notes', 'include-categories');

    while (list($key, $index) = each($get_array)) {

        if (isset($_GET[$index]) && !empty($_GET[$index])) {

            ${$index} = $_GET[$index];
            $_SESSION['session_' . $index] = $_GET[$index];

            if ($index != 'category' && $index != 'year' && $index != 'whole_words' && $index != 'case' && $index != 'rating' && $index != 'search_id'
                && $index != 'search-metadata' && $index != 'search-pdfs' && $index != 'search-notes' && $index != 'include-categories') {

                ${$index} = $_GET[$index];
                ${$index . '_separator'} = $_GET[$index . '_separator'];
                $_SESSION['session_' . $index . '_separator'] = $_GET[$index . '_separator'];
            }
        } else {

            ${$index} = '';
            unset($_SESSION['session_' . $index]);

            if ($index != 'category' && $index != 'year' && $index != 'whole_words' && $index != 'case' && $index != 'rating' && $index != 'search_id'
                && $index != 'search-metadata' && $index != 'search-pdfs' && $index != 'search-notes' && $index != 'include-categories') {
                ${$index . '_separator'} = '';
                unset($_SESSION['session_' . $index . '_separator']);
            }
        }
    }
    session_write_close();
    
    // CACHING
    
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
    ), array(
        'full_text'
    ));
    
    if (isset($_GET['from'])) {
        $cache_name = cache_name();
        cache_start($db_change);
    }
    
    //READ CACHED SEARCHES
    
    $searches = array();
    $searches_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'searches';
    if (is_readable($searches_file)) {
        $searches_content = file_get_contents($searches_file);
        $searches = unserialize(gzuncompress($searches_content));
    }
    if (count($searches) > 0) {
        foreach ($searches as $md5 => $search) {
            if ($search['query_time'] < $db_change)
                unset($searches[$md5]);
        }
    }
    
    //READ SHELF AND PROJECTS

    database_connect($database_path, 'library');

    $shelf_files = array();
    $shelf_files = read_shelf($dbHandle);

    $desktop_projects = array();
    $desktop_projects = read_desktop($dbHandle);

    ######### LIMITS ################

    ######### RATING ################

    $rating_search = '';
    $rating_searches = array();

    if (!isset($_GET['rating'])) {
        $_GET['rating'][] = 1;
        $_GET['rating'][] = 2;
        $_GET['rating'][] = 3;
    } else {
        $ratings = array(1, 2, 3);
        $missing_ratings = array_diff($ratings, $_GET['rating']);
        foreach ($missing_ratings as $missing_rating) {
            $rating_searches[] = 'rating!=' . intval($missing_rating);
        }
        if (!empty($rating_searches)) {
            $rating_search = join(' AND ', $rating_searches);
            $rating_search = $rating_search . ' AND ';
        }
    }

    ######### CATEGORIES ################

    $category_search = '';

    if (isset($_GET['category'])) {
        $category_array_unassigned = array ();
        if (in_array(0, $_GET['category'])) {
            if (!isset($_GET['include-categories']) || (isset($_GET['include-categories']) && $_GET['include-categories'] == 1)) {
                $result = $dbHandle->query("SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM filescategories)");
            } elseif (isset($_GET['include-categories']) && $_GET['include-categories'] == 2) {
                $result = $dbHandle->query("SELECT DISTINCT fileID FROM filescategories");
            }
            $category_array_unassigned = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
        }
        $_GET['category'] = array_diff($_GET['category'], array(0));
        $categories = join (',', $_GET['category']);
        if (preg_match('/[^\d\,]/', $categories) > 0) $categories = '';
        if (!isset($_GET['include-categories']) || (isset($_GET['include-categories']) && $_GET['include-categories'] == 1)) {
            $result = $dbHandle->query("SELECT DISTINCT fileID FROM filescategories WHERE categoryID IN (".$categories.")");
        } elseif (isset($_GET['include-categories']) && $_GET['include-categories'] == 2) {
            $result = $dbHandle->query("SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM filescategories WHERE categoryID IN (".$categories."))
                                        EXCEPT SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM filescategories)");
        }
        $category_array = $result->fetchAll(PDO::FETCH_COLUMN);
        $category_array = array_merge ($category_array, $category_array_unassigned);
        $category_string = implode(',', $category_array);
        $category_search = "id IN (" . $category_string . ") AND ";
        $category_array = null;
        $result = null;
    }

    ######### SHELF, CLIPBOARD, DESK ################

    $in = '';

    if ($_GET['select'] == 'shelf') {
        $in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']) . " AND";
    }

    if ($_GET['select'] == 'clipboard') {
        $in = "id IN () AND";
        if (!empty($_SESSION['session_clipboard'])) {
            $clipboard_files = join(",", $_SESSION['session_clipboard']);
            $in = "WHERE id IN ($clipboard_files) AND";
        }
    }

    if ($_GET['select'] == 'desk') {
        $project_name = '';
        $in = 'WHERE id IN () AND ';
        if (is_numeric($_GET['project'])) {
            $result = $dbHandle->query("SELECT fileID FROM projectsfiles WHERE projectID=" . intval($_GET['project']));
            $project_files = $result->fetchAll(PDO::FETCH_COLUMN);
            $project_files = implode(',', $project_files);
            $in = "WHERE id IN (" . $project_files . ") AND ";
            $result = null;
            $result = $dbHandle->query("SELECT project FROM projects WHERE projectID=" . intval($_GET['project']));
            $project_name = $result->fetchColumn();
            $result = null;
        }
    }

    if (isset($orderby) && ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')) {
        $ordering = 'DESC';
    } else {
        $ordering = 'ASC';
    }

    empty($in) ? $where = 'WHERE' : $where = '';

    $anywhere_array = array();
    $authors_array = array();
    $title_array = array();
    $journal_array = array();
    $secondary_title_array = array();
    $abstract_array = array();
    $keywords_array = array();
    $affiliation_array = array();
    $search_id_array = array();
    $year_array = array();
    $fulltext_array = array();
    $notes_array = array();
    $case2 = $case;
    if (empty($case)) $case2 = 0;
    $rating_array = array();


    if (isset($_GET['searchmode']) && $_GET['searchmode'] == 'quick') {
        include 'quicksearch.php';
    } elseif (isset($_GET['searchmode']) && $_GET['searchmode'] == 'advanced') {
        include 'advancedsearch.php';
    } elseif (isset($_GET['searchmode']) && $_GET['searchmode'] == 'expert') {
        include 'expertsearch.php';
    }
    $search_query_array = array_merge((array) $anywhere_array, (array) $authors_array,
            (array) $journal_array, (array) $secondary_title_array,
            (array) $affiliation_array, (array) $keywords_array, (array) $title_array,
            (array) $abstract_array, (array) $year_array, (array) $search_id_array,
            (array) $fulltext_array, (array) $notes_array);

    $search_query = join(' ', $search_query_array);
    $_GET['search_query'] = $search_query;
    
    //CREATE SEARCH MD5 HASH
    $md5_query = '';
    unset($_GET['_']);
    $_GET['orderby'] = $orderby;
    ksort($_GET);

    while (list($key, $value) = each($_GET)) {
        if (is_array($_GET[$key])) {
            while (list(, $value2) = each($_GET[$key])) {
                if (!empty($value2))
                    $md5_query .= $key . $value2;
            }
        } elseif ($key != 'from' && $key != 'limit' && $key != 'display' && !empty($value))
            $md5_query .= $key . $value;
    }
    $md5_query = md5($md5_query);
    
    //SEARCH
    
    $result_array = array();
    $rows = 0;
    
    if (isset($searches[$md5_query])) {

        $result_array = $searches[$md5_query]['result'];
        $rows = count($result_array);
        
    } elseif (!empty($search_string) && $_GET['searchtype'] == 'metadata') {

        $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);
        if ($case == 1)
            $dbHandle->exec("PRAGMA case_sensitive_like = 1");
        
        $dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        $result = $dbHandle->query("SELECT id FROM library $in $where $rating_search $category_search $search_string ORDER BY $orderby COLLATE NOCASE $ordering");
        if ($result) {
            $result_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $rows = count($result_array);
        }
        $result = null;
        
    } elseif (!empty($search_string) && $_GET['searchtype'] == 'notes') {

        $notes_in = str_replace("id IN", "fileID IN", $in);
        $notes_category_search = str_replace("id IN", "fileID IN", $category_search);

        $dbHandle->sqliteCreateFunction('search_strip_tags', 'sqlite_strip_tags', 1);
        $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);
        if ($case == 1)
            $dbHandle->exec("PRAGMA case_sensitive_like = 1");

        if ($_GET['select'] == 'shelf') {
            $notes_query = "SELECT fileID FROM notes INNER JOIN shelves USING (fileID,userID) WHERE shelves.userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";
        } elseif ($_GET['select'] == 'desk') {
            $notes_query = "SELECT fileID FROM notes $notes_in userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";
        } elseif ($_GET['select'] == 'clipboard') {
            $notes_query = "SELECT fileID FROM notes $notes_in userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";
        } else {
            $notes_query = "SELECT fileID FROM notes WHERE userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";
        }

        $result = $dbHandle->query("SELECT id FROM library WHERE id IN ($notes_query) ORDER BY $orderby COLLATE NOCASE $ordering");
        if ($result) {
            $result_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $rows = count($result_array);
        }
        
    } elseif ($_GET['searchtype'] == 'pdf') {

        $dbHandle = null;

        $fulltext_in = str_replace("id IN", "fileID IN", $in);
        $fulltext_category_search = str_replace("id IN", "fileID IN", $category_search);

        database_connect($database_path, 'fulltext');

        $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

        if ($case == 1)
            $dbHandle->exec("PRAGMA case_sensitive_like = 1");

        ###no index search
        $result = $dbHandle->query("SELECT fileID FROM full_text WHERE $fulltext_category_search $fulltext_in $search_string");

        /*
          ###fts3 index search
          if ($_GET['fulltext_separator'] == 'AND') {
          $result = $dbHandle->query("SELECT full_text.fileID AS fileID
          FROM full_text INNER JOIN full_text_fts ON full_text.id=full_text_fts.docid
          WHERE $fulltext_category_search full_text_fts.full_text MATCH '$search_string'");
          } else {
          foreach($fulltext_array as $term) {
          $queries[] = "SELECT full_text.fileID AS fileID
          FROM full_text INNER JOIN full_text_fts ON full_text.id=full_text_fts.docid
          WHERE $fulltext_category_search full_text_fts.full_text MATCH '$term'";
          }
          $query = join (" UNION ", $queries);
          $result = $dbHandle->query($query);
          }
         */

        $result_ids = array();
        if ($result) $result_ids = $result->fetchAll(PDO::FETCH_COLUMN);
        $result = null;
        $dbHandle = null;

        $result_string = join(",", $result_ids);
        $result_string = "id IN ($result_string)";

        database_connect($database_path, 'library');
        $result = $dbHandle->query("SELECT id FROM library $where $in $result_string ORDER BY $orderby COLLATE NOCASE $ordering");
        $result_array = $result->fetchAll(PDO::FETCH_COLUMN);
        $rows = count($result_array);
        $result = null;
    }
    
    //PULL DATA FOR ITEMS TO DISPLAY FROM DATABASE

    $limited_result = array();

    if (!empty($result_array)) {
        save_export_files($result_array);
        $limited_result = array_slice($result_array, $from, $limit);
        $result_string = join(",", $limited_result);
        $result_string = "id IN ($result_string)";
        $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating FROM library WHERE $result_string ORDER BY $orderby COLLATE NOCASE $ordering");
        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        $dbHandle = null;
    }
    
    //SAVE SEARCH IN CACHE

    if ($_GET['select'] != 'clipboard' && !isset($searches[$md5_query])) {
        $searches[$md5_query]['result'] = $result_array;
        $searches[$md5_query]['query_time'] = time();
        save_search($searches_file, $searches);
    }
    
    //TRUNCATE SHELF FILES ARRAY TO ONLY DISPLAYED FILES IMPROVES PERFROMANCE FOR LARGE SHELVES
    if(count($shelf_files) > 5000) $shelf_files = array_intersect((array) $limited_result, (array) $shelf_files);

    //PRE-FETCH CATEGORIES, PROJECTS FOR DISPLAYED ITEMS IN A BATCH INTO TEMP DATABASE TO OFFLOAD THE MAIN DATABASE
    if (!empty($result_array)) {

        $display_files2 = join(",", $limited_result);
        try {
            $tempdbHandle = new PDO('sqlite::memory:');
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage() . "<br/>";
            die();
        }
        $quoted_path = $tempdbHandle->quote($database_path . 'library.sq3');
        $tempdbHandle->exec("ATTACH DATABASE $quoted_path AS librarydb");

        $tempdbHandle->beginTransaction();

        $tempdbHandle->exec("CREATE TABLE temp_categories (
                fileID integer NOT NULL,
                categoryID integer NOT NULL,
                category text NOT NULL)");
        $tempdbHandle->exec("CREATE TABLE temp_projects (
                fileID integer NOT NULL,
                projectID integer NOT NULL)");
        $tempdbHandle->exec("CREATE TABLE temp_notes (
                fileID integer NOT NULL,
                notesID integer NOT NULL,
                notes text NOT NULL)");

        $tempdbHandle->exec("INSERT INTO temp_categories SELECT fileID,filescategories.categoryID,category
                            FROM librarydb.categories INNER JOIN librarydb.filescategories ON filescategories.categoryID=categories.categoryID
                            WHERE fileID IN ($display_files2)");

        $tempdbHandle->exec("INSERT INTO temp_projects SELECT fileID,projectID
                            FROM librarydb.projectsfiles WHERE fileID IN ($display_files2)");

        if (isset($_SESSION['auth']))
            $tempdbHandle->exec("INSERT INTO temp_notes SELECT fileID,notesID,notes
                            FROM librarydb.notes WHERE fileID IN ($display_files2) AND userID=" . intval($_SESSION['user_id']));

        $tempdbHandle->commit();
        $tempdbHandle->exec("DETACH DATABASE $quoted_path");
    }

    $microtime2 = microtime(true);
    $microtime = $microtime2 - $microtime1;
    $microtime = sprintf("%01.2f sec", $microtime);

    if ($rows > 0) {

        print '<div id="display-content">';
        ?>
        <table cellspacing="1" id="customization" style="border-spacing:2px 0px;cursor:pointer">
            <tr>
                <td class="ui-state-highlight" id="displaybutton">&nbsp;Display&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $display == 'brief' ? 'on' : 'off'; ?>" style="float:left"></span>Title&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $display == 'summary' ? 'on' : 'off'; ?>" style="float:left"></span>Summary&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $display == 'abstract' ? 'on' : 'off'; ?>" style="float:left"></span>Abstract&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $display == 'icons' ? 'on' : 'off'; ?>" style="float:left"></span>Icons&nbsp;</td>
                <td class="ui-state-highlight" id="orderbybutton">&nbsp;Order by&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $orderby == 'year' ? 'on' : 'off'; ?>" style="float:left"></span>Date Published&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $orderby == 'id' ? 'on' : 'off'; ?>" style="float:left"></span>Date Added&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $orderby == 'rating' ? 'on' : 'off'; ?>" style="float:left"></span>Rating&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $orderby == 'journal' ? 'on' : 'off'; ?>" style="float:left"></span>Journal&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $orderby == 'title' ? 'on' : 'off'; ?>" style="float:left"></span>Title&nbsp;</td>
                <td class="ui-state-highlight" id="showbutton">&nbsp;Show&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 5 ? 'on' : 'off'; ?>" style="float:left"></span>5&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 10 ? 'on' : 'off'; ?>" style="float:left"></span>10&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 15 ? 'on' : 'off'; ?>" style="float:left"></span>15&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 20 ? 'on' : 'off'; ?>" style="float:left"></span>20&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 50 ? 'on' : 'off'; ?>" style="float:left"></span>50&nbsp;</td>
                <td class="alternating_row"><span class="ui-icon ui-icon-radio-<?php print $limit == 100 ? 'on' : 'off'; ?>" style="float:left"></span>100&nbsp;</td>
            </tr>
        </table>
        <script type="text/javascript">
            $('#customization').data('redirection','<?php print preg_replace('/(from=\d*)(\&|$)/', '$2', basename($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING']); ?>')
            .find('span.ui-icon-radio-off').closest('td').find("td:not([id])").addBack().hide();
        </script>
        <?php
    }

    if (!empty($_GET['search_query'])) $search_query = htmlspecialchars($_GET['search_query']);
    if (!empty($_GET['search-metadata'])) $search_query = htmlspecialchars($_GET['search-metadata']);
    if (!empty($_GET['search-pdfs'])) $search_query = htmlspecialchars($_GET['search-pdfs']);
    if (!empty($_GET['search-notes'])) $search_query = htmlspecialchars($_GET['search-notes']);

    if (isset($_GET['select']) && $_GET['select'] == 'shelf' && isset($_SESSION["auth"])) {
        $what = "Shelf";
    } elseif (isset($_GET['select']) && $_GET['select'] == 'desk') {
        $what = "Project: " . $project_name;
    } elseif (isset($_GET['select']) && $_GET['select'] == 'clipboard') {
        $what = "Clipboard";
    } else {
        $what = "Library";
    }

    if (!empty($search_query))
        print '<div id="list-title" style="font-weight: bold; padding: 2px">' . $what . ' &raquo; Query: ' . htmlspecialchars($search_query) . '</div>';

    $url_array = array();
    reset($_GET);
    while (list($name, $value) = each($_GET)) {
        if (is_array($_GET[$name])) {
            while (list(, $value2) = each($_GET[$name])) {
                if (!empty($value2))
                    $url_array[] = urlencode($name . '[]') . '=' . urlencode($value2);
            }
        } elseif ($name != 'from') {
            if (!empty($value))
                $url_array[] = urlencode($name) . '=' . urlencode($value);
        }
    }
    $url_string = join('&', $url_array);

    if ($rows > 0) {

        $items_from = $from + 1;
        (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

        print '<table cellspacing="0" class="top" style="margin-bottom:1px"><tr><td style="width: 21em">';

        print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                . ($from == 0 ? '' : '<a href="' . htmlspecialchars("search.php?$url_string&from=0") . '" class="navigation" style="display:block;width:26px">') .
                '<span class="ui-icon ui-icon-seek-first" style="margin-left:5px"></span>'
                . ($from == 0 ? '' : '</a>') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px">'
                . ($from == 0 ? '' : '<a title="Shortcut: A" class="prevpage navigation" href="' . htmlspecialchars("search.php?$url_string&from=" . ($from - $limit)) . '" style="color:black;display:block;width:100%">') .
                '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;&nbsp;'
                . ($from == 0 ? '' : '</a>') .
                '</div>';

        if (isset($_SESSION['auth']))
            print '<div id="exportbutton" class="ui-state-highlight ui-corner-top" style="float:left;margin-left:2px">
			 <span class="ui-icon ui-icon-suitcase" style="float:left"></span>Export&nbsp;
			</div>
			<div class="ui-state-highlight ui-corner-top" style="float:left;margin-left:2px" onclick="window.print()">
			 <span class="ui-icon ui-icon-print" style="float:left"></span>Print&nbsp;
			</div>';

        print '</td><td style="text-align: center">Items ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span> in ' . $microtime . '.</td>';

        print '<td style="width: 22em">';

        (($rows % $limit) == 0) ? $lastpage = $rows - $limit - ($rows % $limit) : $lastpage = $rows - ($rows % $limit);

        print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("search.php?$url_string&from=$lastpage") . '" style="display:block;width:26px">' : '') .
                '<span class="ui-icon ui-icon-seek-end" style="margin-left:5px"></span>'
                . (($rows > ($from + $limit)) ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px">'
                . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="nextpage navigation" href="' . htmlspecialchars("search.php?$url_string&from=" . ($from + $limit)) . '" style="color:black;display:block;width:100%">' : '') .
                '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;&nbsp;Next'
                . (($rows > ($from + $limit)) ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 5em;margin-right:2px">PgDown</div>';

        if (isset($_SESSION['auth']))
            print '<div id="omnitoolbutton" class="ui-state-highlight ui-corner-top" style="float:right;margin-right:2px">'
                    . '<span class="ui-icon ui-icon-wrench" style="float:left"></span>Omnitool&nbsp;</div>';

        print '</td></tr></table>';

        show_search_results($result, $_GET['select'], $display, $shelf_files, $desktop_projects, $tempdbHandle);

        print '<table cellspacing="0" class="top" style="margin:1px 0px 2px 0px"><tr><td style="width:50%">';

        print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                . ($from == 0 ? '' : '<a href="' . htmlspecialchars("search.php?$url_string&from=0") . '" class="navigation" style="display:block;width:26px">') .
                '<span class="ui-icon ui-icon-seek-first" style="margin-left:5px"></span>'
                . ($from == 0 ? '' : '</a>') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px">'
                . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation" href="' . htmlspecialchars("search.php?$url_string&from=" . ($from - $limit)) . '" style="color:black;display:block;width:100%">') .
                '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;&nbsp;'
                . ($from == 0 ? '' : '</a>') .
                '</div>';

        print '</td><td style="width:50%">';

        print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("search.php?$url_string&from=$lastpage") . '" style="display:block;width:26px">' : '') .
                '<span class="ui-icon ui-icon-seek-end" style="margin-left:5px"></span>'
                . (($rows > ($from + $limit)) ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px">'
                . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation" href="' . htmlspecialchars("search.php?$url_string&from=" . ($from + $limit)) . '" style="color:black;display:block;width:100%">' : '') .
                '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;&nbsp;Next'
                . (($rows > ($from + $limit)) ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:5em;margin-right:2px">PgUp</div>';

        print "\n  </td>\n </tr>\n</table>";
    } else {
        print '<div style="position:relative;top:43%;left:43%;color:#bfbeb9;font-size:28px;width:200px"><b>No Hits</b></div>';
    }
    print '</div>';
    
    if (isset($_GET['from'])) cache_store();
}
?>
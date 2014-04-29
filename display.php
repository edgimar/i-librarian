<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!isset($_GET['from'])) {
    $from = '0';
} else {
    settype($_GET['from'], "integer");
    $from = $_GET['from'];
}

// CACHING

if (isset($_GET['from']) && !isset($_GET['browse']['No PDF']) && !isset($_GET['browse']['Not Indexed'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projects',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
    ));
    cache_start($db_change);
    $export_files = read_export_files($db_change);
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

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

if (isset($_GET['browse'])) {

    $in = '';
    $all_in = '';
    $total_files_array = array();

    database_connect($database_path, 'library');

    $shelf_files = array();
    $shelf_files = read_shelf($dbHandle);

    $desktop_projects = array();
    $desktop_projects = read_desktop($dbHandle);

    if ($_GET['select'] == 'shelf') {
        $all_in = "INNER JOIN shelves ON library.id=shelves.fileID WHERE shelves.userID=" . intval($_SESSION['user_id']);
    }

    if ($_GET['select'] == 'desk') {
        $project_id = '';
        $display_project = '';
        $all_in = "WHERE id IN ()";
        if (!empty($desktop_projects)) {
            $project_id = $desktop_projects[0]['projectID'];
            $display_project = $desktop_projects[0]['project'];
        }

        if (isset($_GET['project']))
            $project_id = $_GET['project'];
        $project_files = $dbHandle->query("SELECT fileID FROM projectsfiles WHERE projectID=" . intval($project_id));
        $project_files = $project_files->fetchAll(PDO::FETCH_COLUMN);
        $in = implode(',', $project_files);
        $all_in = "WHERE id IN ($in)";
        $project_files = null;

        if (!empty($desktop_projects)) {
            foreach ($desktop_projects as $desktop_project) {
                if ($desktop_project['projectID'] == $project_id)
                    $display_project = $desktop_project['project'];
            }
        }
    }

    if ($_GET['select'] == 'clipboard' && !empty($_SESSION['session_clipboard'])) {
        $in = join(",", $_SESSION['session_clipboard']);
        $all_in = "WHERE id IN ($in)";
    }

    if ($_GET['select'] == 'clipboard' && empty($_SESSION['session_clipboard'])) {
        $all_in = "WHERE id IN ()";
    }

    empty($all_in) ? $where = 'WHERE' : $where = 'AND';

    $category_sql_array = array();

    while (list($query, $column) = each($_GET['browse'])) {

        $query2 = str_replace("\\", "\\\\", $query);
        $query2 = str_replace('%', '\%', $query2);
        $query2 = str_replace('_', '\_', $query2);
        $query2 = str_replace("'", "''", $query2);

        if ($column == 'category') {
            $query = intval($query);
            if ($query == 0) {
                $category_sql_array[] = "SELECT id FROM library WHERE id NOT IN (SELECT fileID FROM filescategories)";
                $query_translation = '!unassigned';
            } else {
                $category_sql_array[] = "SELECT fileID FROM filescategories WHERE categoryID=$query";
                $result = $dbHandle->query("SELECT category FROM categories WHERE categoryID=$query LIMIT 1");
                $query_translation = $result->fetchColumn();
                $result = null;
            }
            $category_sql = implode(" INTERSECT ", $category_sql_array);
        } elseif ($column == 'keywords') {
            $browse_string_array[] = "(keywords LIKE '$query2' ESCAPE '\' OR keywords LIKE '%/ $query2' ESCAPE '\' OR keywords LIKE '%/ $query2 /%' ESCAPE '\' OR keywords LIKE '$query2 /%' ESCAPE '\'
						 OR keywords LIKE '%/$query2' ESCAPE '\' OR keywords LIKE '%/$query2/%' ESCAPE '\' OR keywords LIKE '$query2/%' ESCAPE '\')";
        } elseif ($column == 'authors') {
            $query2_array = explode(',', $query2);
            $query2 = 'L:"' . trim($query2_array[0]) . '",F:"' . trim($query2_array[1]) . '"';
            $browse_string_array[] = "(authors LIKE '%$query2%' ESCAPE '\' OR authors_ascii LIKE '%$query2%' ESCAPE '\') AND (regexp_match(authors, '$query2', 0) OR regexp_match(authors_ascii, '$query2', 0))";
        } elseif ($column == 'year') {
            $browse_string_array[] = "(year LIKE '$query2%' ESCAPE '\')";
        } else {
            $browse_string_array[] = "$column='$query2'";
        }

        if ($column != 'all')
            $query_array[] = "$column: " . (empty($query_translation) ? $query : $query_translation);

        $column_string = $column;

        $browse_url_array[] = "browse[" . urlencode($query) . "]=" . urlencode($column);
    }

    $browse_url_string = join("&", $browse_url_array);

    if (isset($browse_string_array) && is_array($browse_string_array))
        $browse_string = join(' AND ', $browse_string_array);
    if (isset($query_array) && is_array($query_array))
        $query_display_string = join(' AND ', $query_array);

    $ordering = 'ASC';
    if ($orderby == 'year' || $orderby == 'addition_date' || $orderby == 'rating' || $orderby == 'id')
        $ordering = 'DESC';

    $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

    if ($column_string == 'all') {

        if (isset($export_files)) {
            $total_files_array = $export_files;
        } else {
            $result = $dbHandle->query("SELECT id FROM library $all_in ORDER BY $orderby COLLATE NOCASE $ordering");
            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);
        }

        $display_files_array = array_slice($total_files_array, $from, $limit);
        $display_files = join(",", $display_files_array);
        $display_files = "id IN ($display_files)";

        $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
					FROM library WHERE $display_files");
    } elseif ($column_string == 'miscellaneous') {

        if (array_key_exists('No PDF', $_GET['browse'])) {

            $pdfs = array();

            chdir('library');

            $pdfs = glob('*.pdf', GLOB_NOSORT);
            $pds_string = join("','", $pdfs);
            $pds_string = "file NOT IN ('" . $pds_string . "')";

            chdir('..');

            $result = $dbHandle->query("SELECT id FROM library WHERE $pds_string ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
						FROM library WHERE $display_files");
        }

        if (array_key_exists('My Items', $_GET['browse'])) {

            $result = $dbHandle->query("SELECT id FROM library WHERE added_by=" . intval($_SESSION['user_id']) . " ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
						FROM library WHERE $display_files");
        }

        if (array_key_exists("Others' Items", $_GET['browse'])) {

            $result = $dbHandle->query("SELECT id FROM library WHERE added_by!=" . intval($_SESSION['user_id']) . " ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
						FROM library WHERE $display_files");
        }

        if (array_key_exists('Not in Shelf', $_GET['browse'])) {

            $not_shelf = join(",", $shelf_files);
            $not_shelf = "id NOT IN(" . $not_shelf . ")";

            $result = $dbHandle->query("SELECT id FROM library WHERE $not_shelf ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
						FROM library WHERE $display_files");
        }

        if (array_key_exists('Not Indexed', $_GET['browse'])) {

            $dbHandle->exec("ATTACH DATABASE '" . $database_path . "fulltext.sq3' AS fulltextdatabase");

            $result = $dbHandle->query("SELECT fileID FROM fulltextdatabase.full_text");
            $indexed = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            $dbHandle->exec("DETTACH DATABASE '" . $database_path . "fulltext.sq3'");

            $not_indexed = join(",", $indexed);
            $not_indexed = "id NOT IN(" . $not_indexed . ")";

            $result = $dbHandle->query("SELECT id FROM library WHERE $not_indexed ORDER BY $orderby COLLATE NOCASE $ordering");

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
						FROM library WHERE $display_files");
        }
    } elseif ($column_string == 'history') {

        $quoted_history = $dbHandle->quote($database_path . 'history.sq3');
        $dbHandle->exec("ATTACH DATABASE $quoted_history AS history");

        $dbHandle->exec("DELETE FROM history.usersfiles WHERE " . time() . "-viewed>28800");

        $result = $dbHandle->query("SELECT id FROM library
                    WHERE id IN (SELECT fileID FROM history.usersfiles WHERE userID=" . intval($_SESSION['user_id']) . ")
                    ORDER BY $orderby COLLATE NOCASE $ordering");

        if (is_object($result)) {

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;

            $dbHandle->exec("DETACH DATABASE history");

            save_export_files($total_files_array);

            $display_files_array = array_slice($total_files_array, $from, $limit);
            $display_files = join(",", $display_files_array);
            $display_files = "id IN ($display_files)";

            $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
                                                FROM library WHERE $display_files");
        }
    } else {

        if (isset($export_files)) {
            $total_files_array = $export_files;
        } else {
            if (!empty($category_sql)) {
                $result = $dbHandle->query("SELECT id FROM library $all_in $where id IN (" . $category_sql . ") ORDER BY $orderby COLLATE NOCASE $ordering");
            } else {
                $result = $dbHandle->query("SELECT id FROM library $all_in $where $browse_string ORDER BY $orderby COLLATE NOCASE $ordering");
            }

            $total_files_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            save_export_files($total_files_array);
        }

        $display_files_array = array_slice($total_files_array, $from, $limit);
        $display_files = join(",", $display_files_array);
        $display_files = "id IN ($display_files)";

        $result = $dbHandle->query("SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,uid,doi,url,addition_date,rating
                                    FROM library WHERE $display_files");
    }

    $rows = count($total_files_array);

    if ($rows > 0) {

        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        $dbHandle = null;

        //SORT QUERY RESULTS
        $tempresult = array();
        foreach ($result as $row) {
            $key = array_search($row['id'], $display_files_array);
            $tempresult[$key] = $row;
        }
        ksort($tempresult);
        $result = $tempresult;

        //TRUNCATE SHELF FILES ARRAY TO ONLY DISPLAYED FILES IMPROVES PERFROMANCE FOR LARGE SHELVES
        if (count($shelf_files) > 5000)
            $shelf_files = array_intersect((array) $display_files_array, (array) $shelf_files);

        //PRE-FETCH CATEGORIES, PROJECTS, NOTES FOR DISPLAYED ITEMS INTO TEMP DATABASE TO OFFLOAD THE MAIN DATABASE
        $display_files2 = join(",", $display_files_array);
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
        ?>
        <div id="display-content" style="width:100%;height:100%">
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

        if (isset($_GET['select']) && $_GET['select'] == 'shelf' && isset($_SESSION["auth"])) {
            $what = "Shelf";
        } elseif (isset($_GET['select']) && $_GET['select'] == 'clipboard') {
            $what = "Clipboard";
        } elseif (isset($_GET['select']) && $_GET['select'] == 'desk') {
            $what = htmlspecialchars("Project: $display_project");
        } else {
            $what = "Library";
        }

        print '<div id="list-title" style="font-weight: bold; padding: 2px">' . $what;

        if (!empty($query_display_string))
            print ' &raquo; ' . htmlspecialchars($query_display_string);

        print '</div>';

        if ($rows > 0) {

            $items_from = $from + 1;
            (($from + $limit) > $rows) ? $items_to = $rows : $items_to = $from + $limit;

            print '<table cellspacing="0" class="top" style="margin-bottom:1px"><tr><td style="width: 21em">';

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '<span class="ui-icon ui-icon-seek-first" style="margin-left:5px"></span>'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation prevpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;&nbsp;'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            if (isset($_SESSION['auth']))
                print '<div id="exportbutton" class="ui-state-highlight ui-corner-top" style="float:left;margin-left:2px">
			 <span class="ui-icon ui-icon-suitcase" style="float:left"></span>Export&nbsp;
			</div>
			<div class="ui-state-highlight ui-corner-top" style="float:left;margin-left:2px" id="printlist">
			 <span class="ui-icon ui-icon-print" style="float:left"></span>Print&nbsp;
			</div>';

            print '</td><td style="text-align: center">Items ' . $items_from . '-' . $items_to . ' of <span id="total-items">' . $rows . '</span>.</td><td style="width:22em">';

            (($rows % $limit) == 0) ? $lastpage = $rows - $limit : $lastpage = $rows - ($rows % $limit);

            print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<span class="ui-icon ui-icon-seek-end" style="margin-left:5px"></span>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation nextpage" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;&nbsp;Next'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 5em;margin-right:2px">PgDown</div>';

            if (isset($_SESSION['auth']))
                print '<div id="omnitoolbutton" class="ui-state-highlight ui-corner-top" style="float:right;margin-right:2px">'
                        . '<span class="ui-icon ui-icon-wrench" style="float:left"></span>Omnitool&nbsp;</div>';

            print '</td></tr></table>';

            show_search_results($result, $_GET['select'], $display, $shelf_files, $desktop_projects, $tempdbHandle);

            print '<table cellspacing="0" class="top" style="margin:1px 0px 2px 0px"><tr><td style="width: 50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px">'
                    . ($from == 0 ? '' : '<a href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=0&$browse_url_string") . '" class="navigation" style="display:block;width:26px">') .
                    '<span class="ui-icon ui-icon-seek-first" style="margin-left:5px"></span>'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px">'
                    . ($from == 0 ? '' : '<a title="Shortcut: A" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from - $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;&nbsp;'
                    . ($from == 0 ? '' : '</a>') .
                    '</div>';
            
            if ($_GET['select'] == 'desk') {
                
                print '<div class="ui-state-highlight ui-corner-bottom ui-state-error-text" style="float:left;margin-left:2px;padding-right:4px">
                <a href="rss.php?project='.$project.'" target="_blank"><span class="ui-icon ui-icon-signal-diag" style="float:left"></span>Project RSS</a></div>';
                
            } else {
                
                print '<div class="ui-state-highlight ui-corner-bottom ui-state-error-text" style="float:left;margin-left:2px;padding-right:4px">
                <a href="rss.php" target="_blank"><span class="ui-icon ui-icon-signal-diag" style="float:left"></span>RSS</a></div>';
            }

            print '</td><td style="width:50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                    . (($rows > ($from + $limit)) ? '<a class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=$lastpage&$browse_url_string") . '" style="display:block;width:26px">' : '') .
                    '<span class="ui-icon ui-icon-seek-end" style="margin-left:5px"></span>'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . (($rows > ($from + $limit)) ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px">'
                    . (($rows > ($from + $limit)) ? '<a title="Shortcut: D" class="navigation" href="' . htmlspecialchars("display.php?select=$_GET[select]&project=$project&from=" . ($from + $limit) . "&$browse_url_string") . '" style="color:black;display:block;width:100%">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;&nbsp;Next'
                    . (($rows > ($from + $limit)) ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:5em;margin-right:2px">PgUp</div>';

            print '</td></tr></table><br>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bfbeb9;font-size:28px;width:200px"><b>No Items</b></div>';
        }
    }
    ?>
</div>
<?php
if (isset($_GET['from'])) cache_store();
?>
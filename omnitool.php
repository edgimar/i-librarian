<?php
include_once 'data.php';
include_once 'functions.php';

$export_files = read_export_files(0);

if (!empty($_POST['omnitool']) && !empty($export_files)) {
    
    database_connect($database_path, 'library');
    $user_query = $dbHandle->quote($_SESSION['user_id']);

    $dbHandle->beginTransaction();

    if ($_POST['omnitool'] == '1') {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
            $exists = $result->fetchColumn();
            $result = null;
            if ($exists == 1) $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
        }
        @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
    }

    if ($_POST['omnitool'] == '2') {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $dbHandle->exec("DELETE FROM shelves WHERE fileID=$file_query AND userID=$user_query");
        }
        @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
    }

    if ($_POST['omnitool'] == '3' && !empty($_POST['project3'])) {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
            $exists = $result->fetchColumn();
            $result = null;
            if ($exists == 1) $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (".intval($_POST['project3']).",$file_query)");
        }
        $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR .'desk_files', GLOB_NOSORT);
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }

    if ($_POST['omnitool'] == '4' && !empty($_POST['project4'])) {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=".intval($_POST['project4'])." AND fileID=$file_query");
        }
        $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR .'desk_files', GLOB_NOSORT);
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }

    if ($_POST['omnitool'] == '5') {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
            $exists = $result->fetchColumn();
            $result = null;
            if ($exists == 1) $_SESSION['session_clipboard'][] = $value;
        }
        $_SESSION['session_clipboard'] = array_unique($_SESSION['session_clipboard']);
    }

    if ($_POST['omnitool'] == '6') {
        while (list(,$value) = each($export_files)) {
            $key = array_search($value, $_SESSION['session_clipboard']);
            unset($_SESSION['session_clipboard'][$key]);
        }
    }

    if ($_POST['omnitool'] == '7' && !empty($_POST['category2'])) {

        $_POST['category2'] = preg_replace('/\s{2,}/', '', $_POST['category2']);
        $_POST['category2'] = preg_replace('/^\s$/', '', $_POST['category2']);
        $_POST['category2'] = array_filter($_POST['category2']);

        $query = "INSERT INTO categories (category) VALUES (:category)";
        $stmt = $dbHandle->prepare($query);
        $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

        while (list($key,$new_category) = each($_POST['category2'])) {
            $new_category_quoted = $dbHandle->quote($new_category);
            $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
            $exists = $result->fetchColumn();
            $result = null;
            if(empty($exists)) {
                $stmt->execute();
                $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                $last_insert_rowid = $last_id->fetchColumn();
                $last_id = null;
                while (list(,$value) = each($export_files)) {
                    $file_query = $dbHandle->quote($value);
                    $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
                    $exists = $result->fetchColumn();
                    $result = null;
                    if ($exists == 1) $dbHandle->exec("INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES ($file_query,$last_insert_rowid)");
                }
                reset($export_files);
            }
        }
    }

    if ($_POST['omnitool'] == '7' && !empty($_POST['category'])) {
        while (list(,$value) = each($export_files)) {
            $file_query = $dbHandle->quote($value);
            $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
            $exists = $result->fetchColumn();
            $result = null;
            if ($exists == 1) {
                while (list(,$cat) = each($_POST['category'])) {
                    $dbHandle->exec("INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES ($file_query,".intval($cat).")");
                }
            }
            reset($_POST['category']);
        }
    }

    $dbHandle->commit();
    
    if ($_POST['omnitool'] == '8') delete_record($dbHandle, $export_files);

    $dbHandle = null;
    
} elseif (isset($_SESSION['auth'])) {
    ?>

<div>
    <table class="threed" cellspacing=0 style="width:100%">
        <tr>
            <td class="threed select_span omnitooltd" style="width:32%">
                <input type="radio" style="display:none" name="omnitool" value="1">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Save to Shelf
            </td>
            <td class="threed select_span omnitooltd" style="width:32%">
                <input type="radio" style="display:none" name="omnitool" value="5">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Save to Clipboard
            </td>
            <td class="threed select_span omnitooltd" style="width:36%">
                <input type="radio" style="display:none" name="omnitool" value="3">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Save to Project
                <select name="project3" style="width:100px">
                        <?php
                        database_connect($database_path,'library');
                        $desktop_projects = array();
                        $desktop_projects = read_desktop($dbHandle);

                        while(list(,$value)=each($desktop_projects)) {
                            print '<option value="'.$value['projectID'].'">'.$value['project'].'</option>';
                        }
                        reset($desktop_projects);
                        ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="threed select_span omnitooltd">
                <input type="radio" style="display:none" name="omnitool" value="2">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Remove from Shelf
            </td>
            <td class="threed select_span omnitooltd">
                <input type="radio" style="display:none" name="omnitool" value="6">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Remove from Clipboard
            </td>
            <td class="threed select_span omnitooltd">
                <input type="radio" style="display:none" name="omnitool" value="4">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Remove from Project
                <select name="project4" style="width:100px">
                        <?php
                        while(list(,$value)=each($desktop_projects)) {
                            print '<option value="'.$value['projectID'].'">'.$value['project'].'</option>';
                        }
                        reset($desktop_projects);
                        ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="threed select_span omnitooltd" colspan=3>
                <input type="radio" style="display:none" name="omnitool" value="7">
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                Add to Categories:
                <div style="width:99.5%;overflow:auto;height:240px;background-color:#fff;border:1px solid #C5C6C9;margin-top:2px;margin-bottom:10px">
                    <form action="display.php" id="omnitoolcategories">
                        <table cellspacing=0 style="width: 99.5%">
                            <tr>
                                <td style="width: 33.2%;padding:2px">
                                    <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                </td>
                                <td style="width: 33.2%;padding:2px">
                                    <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                </td>
                                <td style="width: 33.2%;padding:2px">
                                    <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                </td>
                            </tr>
                        </table>
                        <table cellspacing=0 style="float:left;width: 33.2%;padding:2px">
                                <?php
                                $category_string = null;

                                $result3 = $dbHandle->query("SELECT count(*) FROM categories");
                                $totalcount = $result3->fetchColumn();
                                $result3 = null;

                                $i=1;
                                $isdiv = null;
                                $isdiv2 = null;
                                $result3 = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                                while ($category = $result3->fetch(PDO::FETCH_ASSOC)) {
                                    $cat_all[$category['categoryID']]=$category['category'];
                                    if ($i > 1 && $i > ($totalcount/3) && !$isdiv) {
                                        print '</table><table cellspacing=0 style="width: 33.2%;float: left;padding:2px">';
                                        $isdiv = true;
                                    }
                                    if ($i > 2 && $i > (2*$totalcount/3) && !$isdiv2) {
                                        print '</table><table cellspacing=0 style="width: 33.2%;float: left;padding:2px">';
                                        $isdiv2 = true;
                                    }
                                    print PHP_EOL.'<tr><td class="select_span">';
                                    print "<input type=\"checkbox\" name=\"category[]\" value=\"".htmlspecialchars($category['categoryID'])."\"";
                                    print " style=\"display:none\"><span class=\"ui-icon ui-icon-close\" style=\"float:left\"></span>".htmlspecialchars($category['category'])."</td></tr>";
                                    $i=$i+1;
                                }
                                $result3 = null;
                                ?>
                        </table>
                    </form>
                </div>
            </td>
        </tr>
        <?php
            if ($_SESSION['permissions'] != 'G') {
                ?>
        <tr>
            <td class="select_span omnitooltd" id="lock" style="padding-top:10px">
                <span class="ui-icon ui-icon-locked" style="float:left"></span>
                unlock
            </td>
            <td class="omnitooltd" colspan=2>
                &nbsp;
            </td>
        </tr>
        <tr>
            <td class="omnitooltd ui-state-disabled" colspan=2>
                <input type="radio" style="display:none" name="omnitool" value="8" disabled>
                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                <div class="ui-state-error-text" style="float:left;margin-right:6px"><span class="ui-icon ui-icon-alert"></span></div>
                Permanently delete from Library
            </td>
            <td class="omnitooltd">
                &nbsp;
            </td>
        </tr>
                <?php
            }
            ?>
    </table>
</div>

    <?php
}
?>
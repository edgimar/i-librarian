<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (isset($_GET['delete']) && isset($_GET['file'])) {
    database_connect($database_path, 'library');
    $error = null;
    $error = delete_record($dbHandle, $_GET['file']);
    die($error);
}

if (isset($_GET['neighbors']) && isset($_GET['file'])) {
    
    $export_files = read_export_files(0);
    
    $current_record = array_search($_GET['file'], $export_files);
    isset($export_files[$current_record - 1]) ? $prevrecord = $export_files[$current_record - 1] : $prevrecord = 'none';
    isset($export_files[$current_record + 1]) ? $nextrecord = $export_files[$current_record + 1] : $nextrecord = 'none';
    die($prevrecord.':'.$nextrecord);
}

$export_files = read_export_files(0);
if (empty($export_files)) {
    //HACK, SOMETIMES CLIENT IS REFRESHING EXPORT FILES
    sleep(1);
    $export_files = read_export_files(0);
}
if (empty($export_files)) die('Error! No files to display.');
?>
<div id="items-left" class="noprint alternating_row" style="position:relative;float:left;width:233px;height:100%;overflow:scroll;border:0;margin:0">
    <?php
    if(empty($_GET['file'])) $_GET['file'] = $export_files[0];
    $key = array_search(intval($_GET['file']), $export_files);
    $offset = max($key-9, 0);
    if ($offset > count($export_files) - 20) {
        $offset = max(count($export_files) - 20, 0);
    }
    $show_items = array_slice($export_files, $offset, 20);
    if ($offset > 0) {
        print '<div class="ui-state-highlight lib-shadow-bottom" style="margin-bottom:4px;height:17px" title="Previous">';
        print '<a href="items.php?file='.$export_files[$offset-1].'" class="navigation" style="display:block;width:100%" id="'.$export_files[$offset-1].'">';
        print '<span class="ui-icon ui-icon-triangle-1-n" style="margin:auto"></span>';
        print '</a></div>';
    }
    print '<div id="list-title-copy" class="items" style="font-weight:bold"></div><div class="separator"></div>';

    $divs = array();
    
    database_connect($database_path, 'library');
    
    $query = join (",", $show_items);
    $result = $dbHandle->query("SELECT id,title FROM library WHERE id IN (".$query.")");
    
    $result = $result->fetchAll(PDO::FETCH_ASSOC);

    //SORT QUERY RESULTS
    $tempresult = array();
    foreach ($result as $row) {
        $key = array_search($row['id'], $show_items);
        $tempresult[$key] = $row;
    }
    ksort($tempresult);
    $result = $tempresult;
    
    foreach ($result as $item) {

        $divs[] = '<div id="list-item-'.$item['id'].'" class="items listleft">'.
                $item['title'].
                '</div>';
    }
    $result = null;
    
    if (isset($_GET['file'])) {
        $id_query = $dbHandle->quote($_GET['file']);
        $result = $dbHandle->query("SELECT * FROM library WHERE id=$id_query LIMIT 1");
        $paper = $result->fetch(PDO::FETCH_ASSOC);
        $result = null;

        $current_record = array_search($_GET['file'], $export_files);
        isset($export_files[$current_record - 1]) ? $prevrecord = $export_files[$current_record - 1] : $prevrecord = null;
        isset($export_files[$current_record + 1]) ? $nextrecord = $export_files[$current_record + 1] : $nextrecord = null;
    }
    $dbHandle = null;

    $hr = '<div class="separator"></div>';

    print join ($hr, $divs);

    if ($offset < count($export_files) - 20) {
        print '<div class="ui-state-highlight lib-shadow-top" style="margin-top:4px" title="Next">';
        print '<a href="items.php?file='.$export_files[$offset+20].'" class="navigation" style="display:block;width:100%" id="file-'.$export_files[$offset+20].'">';
        print '<span class="ui-icon ui-icon-triangle-1-s" style="margin:auto"></span>';
        print '</a></div>';
    }
    ?>
</div>
<div class="alternating_row middle-panel"
     style="float:left;width:6px;height:100%;overflow:hidden;border-right:1px solid #b5b6b8;cursor:pointer">
    <span class="ui-icon ui-icon-triangle-1-w" style="position:relative;left:-5px;top:49%"></span>
</div>
<div style="width:100%;height:100%;overflow:hidden" id="items-right" data-file="<?php echo $paper['id'] ?>">
    <?php
    if (!empty($paper['id'])) {
    ?>
        <table cellspacing="0" class="top" style="margin-top:2px;margin-bottom:1px">
            <tr>
                <td class="top">
                    <div class="ui-state-highlight ui-corner-top" id="file-item" style="float:left;margin-left:2px;padding-right:4px">
                        <span class="ui-icon ui-icon-home" style="float:left;width:16px"></span>Item
                    </div>
                    <?php
                    if (isset($_SESSION['auth'])) {
                        ?>
                        <div class="ui-state-highlight ui-corner-top" id="file-pdf" style="float:left;margin-left:2px;padding-right:4px">
                            <span class="ui-icon ui-icon-document" style="float:left"></span>PDF
                        </div>
                        <?php
                        if ($_SESSION['permissions'] != 'G') {
                            ?>
                            <div class="ui-state-highlight ui-corner-top" id="file-edit" style="float:left;margin-left:2px;padding-right:4px">
                                <span class="ui-icon ui-icon-gear" style="float:left"></span>Edit
                            </div>
                            <?php
                        }
                        ?>
                        <div class="ui-state-highlight ui-corner-top" id="file-notes" style="float:left;margin-left:2px;padding-right:4px">
                            <span class="ui-icon ui-icon-pencil" style="float:left"></span>Notes
                        </div>
                        <div class="ui-state-highlight ui-corner-top" id="file-categories" style="float:left;margin-left:2px;padding-right:4px">
                            <span class="ui-icon ui-icon-tag" style="float:left"></span>Categories
                        </div>
                        <div class="ui-state-highlight ui-corner-top" id="file-files" style="float:left;margin-left:2px;padding-right:4px">
                            <span class="ui-icon ui-icon-script" style="float:left"></span>Files
                        </div>
                        <div class="ui-state-highlight ui-corner-top" id="file-discussion" style="float:left;margin-left:2px;padding-right:4px">
                            <span class="ui-icon ui-icon-comment" style="float:left"></span>Discuss
                        </div>
                        <?php
                    }
                    ?>
                </td>
                <td class="top">
                    <div class="ui-state-highlight backbutton ui-corner-top" style="float:right;margin-left:2px;margin-right:4px" title="Back to list view (Q)">
                        <span class="ui-icon ui-icon-squaresmall-close" style="float:left"></span>Close&nbsp;
                    </div>
                    <?php
                    if (isset($_SESSION['auth'])) {

                        if ($_SESSION['permissions'] != 'G') {
                            ?>
                                <div class="ui-state-highlight ui-corner-top" style="float:right;margin-left:2px" id="deletebutton">
                                    <span class="ui-icon ui-icon-trash" style="float:left"></span>Delete&nbsp;
                                </div>
                            <?php
                        }
                        ?>
                        <div class="ui-state-highlight ui-corner-top" style="float:right;margin-left:2px" id="printbutton">
                            <span class="ui-icon ui-icon-print" style="float:left"></span>Print&nbsp;
                        </div>
                        <div id="exportfilebutton" class="ui-state-highlight ui-corner-top" style="float:right;margin-left:2px">
                            <span class="ui-icon ui-icon-suitcase" style="float:left"></span>Export&nbsp;
                        </div>
                        <div class="ui-state-highlight ui-corner-top" style="float:right;margin-left:2px">
                            <a href="mailto:?subject=Paper in I, Librarian&body=<?php
                print htmlspecialchars(wordwrap($paper['title_ascii'], "75", "%0A", false))
                        . '%0A%0A' . htmlspecialchars(wordwrap(substr($paper['abstract_ascii'], 0, 512), "75", "%0A", false))
                        . htmlspecialchars(empty($paper['doi']) ? '' : '%0A%0APublisher link:%0Ahttp://dx.doi.org/' . $paper['doi'])
                        . '%0A%0AI, Librarian link:%0A' . htmlspecialchars($url . '?id=' . $paper['id'])
                        . (file_exists('library/' . $paper['file']) ? '%0A%0ADirect link to the PDF:%0A' . htmlspecialchars($url.'downloadpdf.php?file='.$paper['file']) : '')
                        ?>"
                               target="_blank" style="color:black;display:inline-block">
                                <span class="ui-icon ui-icon-mail-closed" style="float:left"></span>E-Mail&nbsp;</a>
                        </div>
                        <?php
                    }
                    ?>
                </td>
            </tr>
        </table>
        <div id="file-panel" style="width:100%;height:48%;border-top:1px solid #c6c8cc;border-bottom:1px solid #c6c8cc;overflow:auto">
        </div>
        <table class="top" cellspacing=0 style="width:100%">
            <tr>
                <td style="text-align:center">
                    <table cellspacing=0 style="margin:auto">
                        <tr>
                            <td>
                                <div title="Shortcut: W" class="prevrecord ui-state-highlight<?php print empty($prevrecord) ? ' ui-state-disabled' : ''  ?>" id="prev-item-<?php print $prevrecord ?>">
                                    <span class="ui-icon ui-icon-triangle-1-n" style="float:left"></span>Prev&nbsp;
                                </div>
                            </td>
                            <td style="padding:0px 8px">
                                <div class="ui-state-highlight backbutton noprint" title="Back to list view (Q)">
                                    <span class="ui-icon ui-icon-squaresmall-close" style="float:left"></span>Close&nbsp;
                                </div>
                            </td>
                            <td>
                                <div title="Shortcut: S" class="nextrecord ui-state-highlight<?php print empty($nextrecord) ? ' ui-state-disabled' : '' ?>" id="next-item-<?php print $nextrecord ?>">
                                    <span class="ui-icon ui-icon-triangle-1-s" style="float:left"></span>Next&nbsp;
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    <?php
        include_once 'coins.php';
    } else {
        print '<h3>&nbsp;Error! This item does not exist.<br>&nbsp;Reload of the library is recommended.</h3>';
    }
    ?>
</div>
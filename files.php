<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    if (isset($_POST['filename']))
        $_GET['filename'] = $_POST['filename'];
    if (isset($_POST['file']))
        $_GET['file'] = $_POST['file'];

    ##########	read reference data	##########

    database_connect($database_path, 'library');

    $file_query = $dbHandle->quote($_GET['file']);
    $user_query = $dbHandle->quote($_SESSION['user']);

    $record = $dbHandle->query("SELECT id,file FROM library WHERE id=$file_query LIMIT 1");
    $paper = $record->fetch(PDO::FETCH_ASSOC);

    $record = null;
    $dbHandle = null;
    ?>
    <div id="preview" style="display:none;position:fixed;top:1px;right:1px;background-color:#CFCECC;z-index:100"></div>
    <table cellspacing="0" style="width:100%;height:100%;margin-top:0px">
        <tr>
            <td class="alternating_row" style="padding: 5px">
                <form id="uploadfiles" enctype="multipart/form-data" action="ajaxsupplement.php" method="POST">
                    <input type="hidden" name="MAX_FILE_SIZE" value="100000000">
                    <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
                    <input type="hidden" name="filename" value="<?php print htmlspecialchars($paper['file']) ?>">
                    <button id="submituploadfiles">Save</button><br>
                    <div class="separator" style="margin:6px 0 2px 0"></div>
                    <b>Add/replace PDF:</b><br>
                    Local file:<br>
                    <input type="file" name="form_new_file" accept="application/pdf"><br>
                    PDF from the Web:<br>
                    <input type="text" name="form_new_file_link" style="width: 99%"><br>
                    <div class="separator" style="margin:6px 0 2px 0"></div>
                    <b>Add graphical abstract:</b><br>
                    <input type="file" name="form_graphical_abstract" accept="image/*"><br>
                    <div class="separator" style="margin:6px 0 2px 0"></div>
                    <b>Add supplementary files:</b><br>
                    <input type="file" name="form_supplementary_file1"><br>
                    <input type="file" name="form_supplementary_file2"><br>
                    <input type="file" name="form_supplementary_file3"><br>
                    <input type="file" name="form_supplementary_file4"><br>
                    <input type="file" name="form_supplementary_file5"><br>
                </form>
            </td>
            <td style="width:90%;padding: 2px">
                <div style="border-bottom:1px solid #cfcecc;font-weight:bold">PDF file:</div>
    <?php if (file_exists("library/$paper[file]")) { ?>
                    <table border=0 cellspacing=0 cellpadding=0 style="width:100%;margin:0px">
                        <tr class="file-highlight" id="file<?php print $_GET['file'] ?>">
                            <td style="height:22px;line-height:22px">
                                <span class="ui-icon ui-icon-document" style="float:left;margin:3px 2px 0 0"></span>
        <?php print $paper['file']; ?></td>
                            <td style="width:8em">
                                <div class="ui-state-highlight" style="float:right;margin-top:2px">
                                    <a href="viewindex.php?file=<?php print $_GET['file'] ?>" target="_blank" style="display:block;width:100%;color:#000000">
                                        <span class="ui-icon ui-icon-search" style="float:left"></span>See Text&nbsp;</a>
                                </div>
                            </td>
                            <td style="width:6.5em">
                                <div class="ui-state-highlight reindex" id="reindex-<?php print $_GET['file'] ?>" style="float:right;margin-top:2px">
                                    <span class="ui-icon ui-icon-arrowrefresh-1-e" style="float:left"></span>Reindex&nbsp;
                                </div>
                            </td>
                        </tr>
                    </table>
    <?php } ?>
                <form id="filesform" enctype="multipart/form-data" action="ajaxsupplement.php" method="POST">
                    <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
                    <div id="filelist">
                        <div style="width:100%;margin:0px;border-bottom:1px solid #cfcecc;font-weight:bold">Graphical Abstract:</div>
                        <?php
                        $integer = sprintf("%05d", intval($paper['file']));
                        $gr_abs = glob("library/supplement/" . $integer . "graphical_abstract.*");
                        if (!empty($gr_abs[0])) {
                            $url_filename = htmlspecialchars(substr(basename($gr_abs[0]), 5));

                            print '<table cellspacing=0 style="width:100%">
 <tr class="file-highlight" id="file' . htmlspecialchars(basename($gr_abs[0])) . '">
  <td style="height:22px;line-height:22px"><span class="image ui-icon ui-icon-image" style="float:left;margin-top:2px"></span>
  <a class="image" href="' . htmlspecialchars('library/supplement/' . basename($gr_abs[0])) . '" target="_blank">' . $url_filename . '</a></td>
  <td style="height:22px;line-height:22px">
   <div class="ui-state-highlight file-remove" style="float:right;margin-top:2px"><span class="ui-icon ui-icon-trash" style="float:left"></span>Remove&nbsp;</div>
  </td>
 </tr>
</table>';
                        }
                        ?>
                        <div style="width:100%;margin:0px;border-bottom:1px solid #cfcecc;font-weight:bold">Supplementary files:</div>
                        <table cellspacing=0 style="width:100%">
                            <tr><td></td><td></td><td></td></tr>
                            <?php
                            $files_to_display = glob('library/supplement/' . $integer . '*');

                            if (count($files_to_display) > 0) {

                                foreach ($files_to_display as $supplementary_file) {

                                    $url_filename = substr(basename($supplementary_file), 5);

                                    if (strstr($url_filename, 'graphical_abstract') === false) {

                                        $extension = pathinfo($supplementary_file, PATHINFO_EXTENSION);

                                        $isimage = null;
                                        if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif' || $extension == 'png') {
                                            $image_array = array();
                                            $image_array = @getimagesize($supplementary_file);
                                            $image_mime = $image_array['mime'];
                                            if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                                                $isimage = true;
                                        }

                                        $isaudio = null;
                                        if ($extension == 'ogg' || $extension == 'oga' || $extension == 'wav' || $extension == 'mp3' || $extension == 'm4a' || $extension == 'fla'  || $extension == 'webma')
                                            $isaudio = true;

                                        $isvideo = null;
                                        if ($extension == 'ogv' || $extension == 'webmv' || $extension == 'm4v' || $extension == 'flv')
                                            $isvideo = true;

                                        print '<tr class="file-highlight" id="file' . htmlspecialchars(basename($supplementary_file)) . '">' . PHP_EOL;

                                        print '<td style="height:22px;line-height:22px">' . PHP_EOL;

                                        if ($isimage) {
                                            print '<span class="image ui-icon ui-icon-image" style="float:left;margin:3px 2px 0px 0px"></span>';
                                            print '<a class="rename_container image" href="' . htmlspecialchars($supplementary_file) . '" target="_blank">';
                                        } elseif ($isaudio) {
                                            print '<span class="audio ui-icon ui-icon-volume-on" style="float:left;margin:3px 2px 0px 0px" title="Click to play"></span>';
                                            print '<a class="rename_container" href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        } elseif ($isvideo) {
                                            print '<span class="video ui-icon ui-icon-video" style="float:left;margin:3px 2px 0px 0px" title="Click to play"></span>';
                                            print '<a class="rename_container" href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        } else {
                                            print '<span class="ui-icon ui-icon-document-b" style="float:left;margin:3px 2px 0px 0px"></span>';
                                            print '<a class="rename_container" href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">';
                                        }

                                        print htmlspecialchars($url_filename) . '</a>' . PHP_EOL;

                                        print '<input class="rename_container" type="text" size="35" name="rename[' . htmlspecialchars(basename($supplementary_file)) . ']" value="' . htmlspecialchars($url_filename) . '" style="float:left;display:none;margin-top:2px;width:90%">' . PHP_EOL;

                                        print '</td>' . PHP_EOL;

                                        print '<td style="width:6.5em;height:22px">' . PHP_EOL;

                                        print '<div class="ui-state-highlight file-rename" style="float:right;margin-top:2px"><span class="ui-icon ui-icon-pencil" style="float:left"></span>Rename&nbsp;</div>' . PHP_EOL;

                                        print '</td>' . PHP_EOL;

                                        print '<td style="width:6.5em;height:22px">' . PHP_EOL;

                                        print '<div class="ui-state-highlight file-remove" style="float:right;margin-top:2px"><span class="ui-icon ui-icon-trash" style="float:left"></span>Remove&nbsp;</div>' . PHP_EOL;

                                        print '</td></tr>' . PHP_EOL;

                                        print '<tr><td colspan=3 style="text-align:center">' . PHP_EOL;

                                        if ($isvideo)
                                            print '<div class="videocontainer" style="text-align:center;display:none"></div>';

                                        if ($isaudio)
                                            print '<div class="audiocontainer" style="text-align:center;display:none"></div>';

                                        print '</td></tr>' . PHP_EOL;
                                    }
                                }
                            }
                            ?>
                        </table>
                    </div>
                </form>
            </td>
        </tr>
    </table>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>

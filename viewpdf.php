<?php
//CROP IMAGE USING GD
if (!empty($_GET['cropimage'])) {

    if (!extension_loaded('gd'))
        die('PHP GD extension not installed.');

    $src = $_GET['image'];
    $w = $_GET['width'];
    $h = $_GET['height'];
    $x = $_GET['x'];
    $y = $_GET['y'];

    if (strpos($_GET['image'], "library/pngs") !== 0)
        die('Invalid image.');
    if ($_GET['width'] > 10000)
        die('Invalid input.');
    if ($_GET['height'] > 10000)
        die('Invalid input.');
    if ($_GET['x'] < 0 || $_GET['x'] > 10000)
        die('Invalid input.');
    if ($_GET['y'] < 0 || $_GET['y'] > 10000)
        die('Invalid input.');

    $img_r = imagecreatefrompng($src);
    $dst_r = imagecreatetruecolor($w, $h);

    imagecopy($dst_r, $img_r, 0, 0, $x, $y, $w, $h);

    header('Content-type: image/png');
    header("Content-Disposition: attachment; filename=image.png");
    header("Pragma: no-cache");
    header("Expires: 0");

    imagepng($dst_r, null, 9);

    imagedestroy($dst_r);
    imagedestroy($img_r);

    die();
}

include_once 'data.php';
include_once 'functions.php';
session_write_close();

$pdf_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
$png_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';

if (!empty($_GET['file'])) {
    $file = preg_replace('/[^a-zA-z0-9\_\.pdf]/', '', $_GET['file']);
    if (substr($_GET['file'], 0, 4) == 'lib_') {
        $pdf_path = $temp_dir;
    }
} else {
    die('Error! PDF does not exist.');
}

$page = 1;
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
} else {
    $userID = intval($_SESSION['user_id']);
    database_connect($database_path, 'history');
    $result = $dbHandle->query("SELECT page FROM bookmarks WHERE userID=$userID AND file='$file'");
    if (is_object($result)) $page = $result->fetchColumn();
    if (!$page) $page = 1;
    $dbHandle = null;
}

if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {
    exec(select_pdfinfo() . '"' . $pdf_path . DIRECTORY_SEPARATOR . $file . '"', $output);
    $output = implode('#', $output);
    $page_number = preg_replace('/(.*#Pages:\s+)(\d+)(#.*)/', '$2', $output);
    if ($page > $page_number)
        $page = $page_number;
    if (empty($page_number))
        die('Error! Program pdfinfo not functional.');
}

if (isset($_GET['renderpdf'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        if (!file_exists($png_path . DIRECTORY_SEPARATOR . $file . '.' . $page . '.png')
                || filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.' . $page . '.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
            exec(select_ghostscript() . " -dSAFER -sDEVICE=png16m -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE -dFirstPage=" . $page . " -dLastPage=" . $page . " -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");
        }
        if (file_exists($png_path . DIRECTORY_SEPARATOR . $file . "." . $page . ".png")) {
            $img_size_array = getimagesize('library' . DIRECTORY_SEPARATOR . 'pngs' . DIRECTORY_SEPARATOR . $file . "." . $page . ".png");
            print $img_size_array[0];
        } else {
            die('Program Ghostscript not functional.');
        }
    } else {
        die('PDF does not exist.');
    }
    die();
}

if (isset($_GET['renderthumbs'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        if (!file_exists($png_path . DIRECTORY_SEPARATOR . $file . ".t1.png")
                || filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.t1.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
            exec(select_ghostscript() . " -dSAFER -sDEVICE=png16m -r15 -dTextAlphaBits=1 -dGraphicsAlphaBits=1 -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . ".t%d.png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");
        }
    }
    die();
}

if (isset($_GET['renderbookmarks'])) {

    if (file_exists($pdf_path . DIRECTORY_SEPARATOR . $file)) {

        $safe_file_name = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
        $file_name = $pdf_path.DIRECTORY_SEPARATOR.$safe_file_name;
        $temp_file = $temp_dir.DIRECTORY_SEPARATOR.$safe_file_name.'-bookmarks.txt';
        if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name)) system(select_pdftk().'"'.$file_name.'" dump_data output "'.$temp_file.'"', $ret);

        if (file_exists($temp_file)) {
            $i = 0;
            $bookmark = array ();
            $pdftk_array = file ($temp_file, FILE_IGNORE_NEW_LINES);
            foreach ($pdftk_array as $pdftk_line) {
                if (stripos($pdftk_line, 'BookmarkTitle') === 0) {
                    $bookmark[$i]['title'] = trim(stristr($pdftk_line, ' '));
                    $j = $i;
                }
                if (stripos($pdftk_line, 'BookmarkLevel') === 0) $bookmark[$j]['level'] = trim(stristr($pdftk_line, ' '));
                if (stripos($pdftk_line, 'BookmarkPageNumber') === 0) {
                    $bookmark[$j]['page'] = trim(stristr($pdftk_line, ' '));
                    if ($bookmark[$j]['page'] == 0) unset($bookmark[$j]);
                }
                $i++;
            }
            $bookmark = array_values($bookmark);
            die(json_encode($bookmark));
        }
    }

    die();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="shortcut icon" href="red.ico">
        <title>
        <?php print !empty($_GET['title']) ? 'PDF: '.$_GET['title'] : 'PDF viewer'; ?>
        </title>
        <link type="text/css" href="css/fonts.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/custom-theme/jquery-ui-custom.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/static.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/tipsy.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/jquery.jgrowl.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/jquery.Jcrop.css?v=<?php print $version ?>" rel="stylesheet">
        <style type="text/css">
<?php include_once 'style.php'; ?>
            @media print
            {
                #pdf-viewer-controls, #thumbs {display: none}
                #pdf-viewer-img {display:inline}
            }
            @page {
                margin: 0;
            }
        </style>
        <script type="text/javascript" src="js/jquery.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery-ui-custom.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.tipsy.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.clicknscroll.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.jgrowl.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.Jcrop.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.form.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.hotkeys.js?v=<?php print $version ?>"></script>
    </head>
    <body class="alternating_row" style="padding:0;margin:0;border:0;overflow:hidden">
        <div style="<?php if (isset($_GET['toolbar']) && $_GET['toolbar'] == 0)
    print ';display:none'; ?>" id="pdf-viewer-controls">
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="save">Export PDF</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="size1" title="Actual size">100%</button>
                            <button id="size2">Fit the page width</button>
                            <button id="size3">Fit the page height</button>
                        </td>
                        <td style="padding-left:4px;padding-top:8px;line-height:28px">
                            <div id="zoom" style="margin-top:4px"></div><div style="float:left;position:relative;top:-4px;width:3em;text-align:right"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="control-first">First page</button>
                            <button id="control-prev">Previous page (E)</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="text" id="control-page" size="3" style="width:3em;padding:2px" value="<?php print intval($page) ?>"> / <?php print $page_number ?>
                        </td>
                        <td style="padding:2px 0 0 4px">
                            <button id="control-next">Next page (D)</button>
                            <button id="control-last">Last page</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-copy-image" <?php if (!extension_loaded('gd'))
                         print 'disabled' ?>>Copy image</button>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="separator" style="margin:0"></div>
            <div class="pdf-viewer-control-row">
                <table>
                    <tr>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="toggle">Toggle left panel</button>
                            <button id="pageprev-button">Page previews</button>
                            <button id="bookmarks-button">Bookmarks</button>
                            <button id="notes-button">List notes</button>
                            <button id="print-notes">Print notes</button>
                            <button id="search-results-button">Search results</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="checkbox" id="pdf-viewer-annotations"><label for="pdf-viewer-annotations">Toggle annotations</label>
                            <input type="checkbox" id="pdf-viewer-marker"><label for="pdf-viewer-marker">Marker</label>
                            <input type="checkbox" id="pdf-viewer-note"><label for="pdf-viewer-note">Pinned note</label>
                            <input type="checkbox" id="pdf-viewer-marker-erase"><label for="pdf-viewer-marker-erase">Erase annotations</label>
                            <input type="checkbox" id="pdf-viewer-others-annotations"><label for="pdf-viewer-others-annotations">Others' annotations</label>
                            <div id="pdf-viewer-delete-menu" class="ui-corner-all alternating_row" style="display:none">
                                <div style="margin-left:20px;cursor: pointer">
                                    Erase individually
                                </div>
                                <div style="margin-left:20px;cursor: pointer">
                                    Erase all markers
                                </div>
                                <div style="margin-left:20px;cursor: pointer">
                                    Erase all notes
                                </div>
                                <div style="margin-left:20px;cursor: pointer">
                                    Erase all
                                </div>
                            </div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <div class="vertical-separator"></div>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <input type="text" id="pdf-viewer-search" size="10" value="" placeholder="Find" style="width:120px;padding:2px"
                                   title="Use &lt;?&gt; as single-letter, and &lt;*&gt; as multi-letter wildcards">
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-clear">Clear</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-search-prev">Previous search result</button>
                        </td>
                        <td style="padding:2px 0 0 4px;line-height:28px">
                            <button id="pdf-viewer-search-next">Next search result</button>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="pdf-viewer-div">
            <div id="navpane" style="display:none">
                <div id="thumbs" style="display:none"><p>Loading previews...</p></div>
                <div id="bookmarks" style="text-align:left;display:none"></div>
                <div id="annotations-left" style="text-align:left;display:none">
                    <input type="text" placeholder="Search notes" id="filter_notes" style="width:148px;margin-left:6px;margin-top:4px">
                </div>
                <div id="search-results" style="text-align:left;display:none">
                    <div style="font-weight:bold;padding:6px 6px 0 6px">Search results:</div>
                </div>
            </div>
            <div id="pdf-viewer-img-div">
                <img src="" id="pdf-viewer-img" alt="">
                <div id="pdf-viewer-loader" class="ui-corner-all" style="display:none">
                    <img src="img/ajaxloader.gif" alt=""> Rendering PDF
                </div>
                <div id="highlight-container"></div>
                <div id="annotation-container" style="display:none;"></div>
                <div id="cursor">
                    <span class="ui-icon"></span>
                </div>
            </div>
        </div>
    </div>
    <div id="copy-image-container" title="Select an area to copy and press the Copy button" style="display:none">
        <img src="" id="image-to-copy" style="box-shadow:0 0 2px #333">
        <form action="viewpdf.php" method="get">
            <input type="hidden" name="cropimage" value="1">
            <input type="hidden" name="image" id="image-src" value="">
            <input type="hidden" id="x" name="x">
            <input type="hidden" id="y" name="y">
            <input type="hidden" id="w" name="width">
            <input type="hidden" id="h" name="height">
        </form>
    </div>
    <div id="save-container" title="Export options" style="display:none">
        <form action="viewpdf.php" method="get">
            <p>&nbsp;Attach to PDF:</p>
            <table>
                <tr>
                    <td class="select_span">
                        <input type="checkbox" name="attachments[]" value="supp" style="display:none">
                        <span class="ui-icon ui-icon-close" style="float:left"></span> &nbsp;supplementary files
                    </td>
                </tr>
                <tr>
                    <td class="select_span">
                        <input type="checkbox" name="attachments[]" value="richnotes" style="display:none">
                        <span class="ui-icon ui-icon-close" style="float:left"></span> &nbsp;rich-text notes
                    </td>
                </tr>
                <tr>
                    <td class="select_span">
                        <input type="checkbox" name="attachments[]" value="notes" style="display:none">
                        <span class="ui-icon ui-icon-close" style="float:left"></span> &nbsp;PDF notes
                    </td>
                </tr>
                <tr>
                    <td class="select_span" style="padding-left:18px">
                        <input type="checkbox" name="attachments[]" value="allusers" style="display:none">
                        <span class="ui-icon ui-icon-close" style="float:left"></span> &nbsp;include notes from all users
                    </td>
                </tr>
            </table>
            <br>
        </form>
    </div>
    <div id="confirm-container" title="Confirm deletion" style="display:none"></div>
    <script type="text/javascript">
        var fileName='<?php print $file ?>',
        totalPages=<?php print $page_number ?>,
        pg=<?php print $page ?>,
        navpanes=false;
        <?php if (isset($_GET['navpanes']) && $_GET['navpanes'] == 1) print 'navpanes=true;'; ?>
    </script>
    <script type="text/javascript" src="js/pdfviewer.js?v=<?php print $version ?>"></script>
</body>
</html>
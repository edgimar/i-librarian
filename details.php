<?php

include_once 'data.php';

if (isset($_SESSION['auth']) && isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

    session_write_close();

    include_once 'functions.php';

    print '<b>&nbsp;Installation Details:</b>';

    print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required software:</td></tr>";

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">PHP version</td>";

    print "<td class=\"details\">>5.2.0 (>5.3.0 recommended)</td><td class=\"details\">" . PHP_VERSION . "</td>";

    print "<td class=\"details\">";

    print version_compare(PHP_VERSION, "5.2.0", "<") ? "<span style=\"color: red; font-weight: bold\">!!!</span>" : "<span style=\"color: green; font-weight: bold\">OK</span>";

    print "</td></tr>";

    database_connect($database_path, 'library');

    $sqlite_version = $dbHandle->query("SELECT sqlite_version()");

    $dbHandle = null;

    $sqlite_version = $sqlite_version->fetchColumn();

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">SQLite database version</td>";

    print "<td class=\"details\">>3.3.0 (>3.7.0 recommended)</td><td class=\"details\">$sqlite_version</td>";

    print "<td class=\"details\" style=\"\">";

    print version_compare($sqlite_version, "3.3.0", "<") ? "<span style=\"color: red; font-weight: bold\">!!!</span>" : "<span style=\"color: green; font-weight: bold\">OK</span>";

    print "</td></tr>";

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required PHP extensions:</td></tr>";

    $extensions = array('pdo' => 'built-in SQLite database',
        'pdo_sqlite' => 'built-in SQLite database',
        'gd' => 'icon views and PDF viewer');

    while (list($extension, $feature) = each($extensions)) {

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">$extension</td><td class=\"details\" style=\"white-space: nowrap\">$feature</td>";

        if (extension_loaded($extension)) {
            print "<td class=\"details\">installed</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
        } else {
            print "<td class=\"details\">not installed</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
        }
    }

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Optional PHP extensions:</td></tr>";

    $extensions = array('zip' => 'export to ZIP');

    while (list($extension, $feature) = each($extensions)) {

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">$extension</td><td class=\"details\" style=\"white-space: nowrap\">$feature</td>";

        if (extension_loaded($extension)) {
            print "<td class=\"details\">installed</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
        } else {
            print "<td class=\"details\">not installed</td><td class=\"details\" style=\"color: orange; font-weight: bold\">!!!</td></tr>";
        }
    }

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>";

    print "Required <a href=\"http://www.php.net/manual/en/ini.php\" target=\"_blank\">" . (version_compare(PHP_VERSION, "5.2.4", ">") ? php_ini_loaded_file() : "php.ini") . "</a> settings:</td></tr>";

    $directives = array('file_uploads' => '1', 'upload_max_filesize' => '200M', 'post_max_size' => '800M', 'max_input_time' => '60');

    while (list($directive, $value) = each($directives)) {

        if (intval(ini_get($directive)) < intval($value)) {

            print "<tr><td class=\"details\" style=\"white-space: nowrap\">$directive</td>";
            print "<td class=\"details\" style=\"\">recommended $value</td>";
            print "<td class=\"details\" style=\"\">" . ini_get($directive) . "</td>";
            print "<td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
        } else {
            print "<tr><td class=\"details\" style=\"white-space: nowrap\">$directive</td>";
            print "<td class=\"details\" style=\"\">recommended $value</td>";
            print "<td class=\"details\" style=\"\">" . ini_get($directive) . "</td>";
            print "<td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
        }
    }

    if (ini_get('open_basedir') != false) {

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">open_basedir</td>";
        print "<td class=\"details\" style=\"\">required disabled</td>";
        print "<td class=\"details\" style=\"\">" . ini_get('open_basedir') . "</td>";
        print "<td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    } else {
        print "<tr><td class=\"details\" style=\"white-space: nowrap\">open_basedir</td>";
        print "<td class=\"details\" style=\"\">required disabled</td>";
        print "<td class=\"details\" style=\"\">disabled</td>";
        print "<td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    }

    if (ini_get('allow_url_fopen') != true) {

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">allow_url_fopen</td>";
        print "<td class=\"details\" style=\"\">required On</td>";
        print "<td class=\"details\" style=\"\">Off</td>";
        print "<td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    } else {
        print "<tr><td class=\"details\" style=\"white-space: nowrap\">allow_url_fopen</td>";
        print "<td class=\"details\" style=\"\">required On</td>";
        print "<td class=\"details\" style=\"\">On</td>";
        print "<td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    }

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required binary executables:</td></tr>";

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdftotext</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">PDF full-text search</td>";

    print '<td class="details" id="details-1" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-2" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdfinfo</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">built-in PDF viewer</td>";

    print '<td class="details" id="details-3" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-4" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdftohtml</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">PDF search in the built-in PDF viewer</td>";

    print '<td class="details" id="details-5" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-6" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Ghostscript</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">icon views, built-in PDF viewer</td>";

    print '<td class="details" id="details-7" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-8" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Bibutils</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Bibtex export/import</td>";

    print '<td class="details" id="details-9" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-10" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdftk</td>";

    print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">PDF bookmarks and watermarks</td>";

    print '<td class="details" id="details-11" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
    print '<td class="details" id="details-12" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

    print '</tr>';

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>I, Librarian " . $version . " is installed in \"" . dirname(__FILE__) . "\":</td></tr>";

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to PDF files:</td><td class=\"details\" style=\"font-size: 11px\">" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "library</td>";

    if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library") && @file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . '.')) {

        print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    } else {

        print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    }

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to supplementary files:</td><td class=\"details\" style=\"font-size: 11px\">" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "supplement</td>";

    if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "supplement") && @file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . '.')) {

        print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    } else {

        print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    }

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to database files:</td><td class=\"details\" style=\"font-size: 11px\">" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "database</td>";

    if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "database") && @file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "database" . DIRECTORY_SEPARATOR . '.')) {

        print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    } else {

        print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    }

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to PNG images:</td><td class=\"details\" style=\"font-size: 11px\">" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "pngs</td>";

    if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "pngs") && @file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "pngs" . DIRECTORY_SEPARATOR . '.')) {

        print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    } else {

        print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    }

    print "<tr><td class=\"details\" style=\"white-space: nowrap\">Temporary directory:</td><td class=\"details\" style=\"font-size: 11px\">" . $temp_dir . "</td>";

    if (is_writable($temp_dir) && @file_exists($temp_dir . DIRECTORY_SEPARATOR . '.')) {

        print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
    } else {

        print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
    }

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>SQLite database files:</td></tr>";

    $database_files = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database');

    while (list($key, $database_file) = each($database_files)) {

        if (substr($database_file, -4) == '.sq3') {
            
            $dbsize = filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $database_file);
            if ($dbsize < 1048576) $dbsize2 = round($dbsize / 1024, 1).' kB';
            if ($dbsize >= 1048576) $dbsize2 = round($dbsize / 1048576, 1).' MB';
            if ($dbsize >= 1073741824) $dbsize2 = round($dbsize / 1073741824, 1).' GB';

            print "<tr><td class=\"details\">$database_file</td>";
            print "<td class=\"details\"><div class=\"file-size\" style=\"width:7em;float:left\">" . $dbsize2. "</div>";
            if ($database_file == 'library.sq3')
                print ' <span class="ui-state-highlight integrity" data-db="library">&nbsp;Check Integrity&nbsp;</span>';
            if ($database_file == 'fulltext.sq3')
                print ' <span class="ui-state-highlight integrity" data-db="fulltext">&nbsp;Check Integrity&nbsp;</span>';
            if ($database_file == 'users.sq3')
                print ' <span class="ui-state-highlight integrity" data-db="users">&nbsp;Check Integrity&nbsp;</span>';
            if ($database_file == 'library.sq3')
                print ' <span class="ui-state-highlight vacuum" data-db="library">&nbsp;Vacuum&nbsp;</span>';
            if ($database_file == 'fulltext.sq3')
                print ' <span class="ui-state-highlight vacuum" data-db="fulltext">&nbsp;Vacuum&nbsp;</span>';
            if ($database_file == 'users.sq3')
                print ' <span class="ui-state-highlight vacuum" data-db="users">&nbsp;Vacuum&nbsp;</span>';
            print '</td>';

            if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "database" . DIRECTORY_SEPARATOR . $database_file)) {

                print "<td class=\"details\" style=\"white-space: nowrap\">writable</td><td class=\"details\" style=\"color: green; font-weight: bold\">OK</td></tr>";
            } else {

                print "<td class=\"details\" style=\"white-space: nowrap\">not writable</td><td class=\"details\" style=\"color: red; font-weight: bold\">!!!</td></tr>";
            }
        }
    }
    
    print '</table><br>';
} else {
    print 'Super User authorization required.';
}
?>
<?php
include_once 'data.php';
include_once 'functions.php';
set_time_limit(0);
session_write_close();

function safe_copy($file, $path_from, $path_to) {

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        $path_from = str_replace("/", "\\", $path_from);
        $path_to = str_replace("/", "\\", $path_to);
    }

    if (is_file($path_from . DIRECTORY_SEPARATOR . $file)) {
        if (!is_file($path_to . DIRECTORY_SEPARATOR . $file) ||
                (is_file($path_to . DIRECTORY_SEPARATOR . $file) &&
                filemtime($path_from . DIRECTORY_SEPARATOR . $file) > filemtime($path_to . DIRECTORY_SEPARATOR . $file))) {

            $fp = fopen($path_from . DIRECTORY_SEPARATOR . $file, "r");

            if (flock($fp, LOCK_SH)) {

                if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                    exec("copy \"" . $path_from . DIRECTORY_SEPARATOR . $file . "\" \"" . $path_to . DIRECTORY_SEPARATOR . $file . "\" /Y");
                } else {
                    exec(escapeshellcmd("cp -p \"" . $path_from . DIRECTORY_SEPARATOR . $file . "\" \"" . $path_to . DIRECTORY_SEPARATOR . $file . "\""));
                }

                flock($fp, LOCK_UN);
                fclose($fp);
            } else {
                fclose($fp);
            }
        }
    }
}

if (isset($_SESSION['auth']) && $_SESSION['permissions'] == 'A') {

    if (!empty($_GET['backup'])) {

        $directory = '';
        if (isset($_GET['directory']))
            $directory = $_GET['directory'];
        if (substr($directory, -1) == DIRECTORY_SEPARATOR)
            $directory = substr($directory, 0, -1);

        $library = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library');
        $database = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database');
        $supplement = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement');
        $pngs = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs');

        if (empty($directory)) {

            $required_space = null;
            $f_number = count($library) + count($database) + count($supplement) + count($pngs) - 11;

            foreach ($library as $file) {
                $required_space = $required_space + filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $file);
            }

            foreach ($database as $file) {
                $required_space = $required_space + filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . $file);
            }

            foreach ($supplement as $file) {
                $required_space = $required_space + filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement' . DIRECTORY_SEPARATOR . $file);
            }

            foreach ($pngs as $file) {
                $required_space = $required_space + filesize(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs' . DIRECTORY_SEPARATOR . $file);
            }
        } else {

            if (!is_dir($directory)) {
                $is_dir = @mkdir($directory);
            } else {
                $is_dir = true;
            }

            if (isset($is_dir) && $is_dir && is_writable($directory)) {

                database_connect($usersdatabase_path, 'users');
                save_setting($dbHandle, 'backup_dir', $directory);
                $dbHandle = null;

                @mkdir($directory . DIRECTORY_SEPARATOR . 'library');
                @mkdir($directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database');
                @mkdir($directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement');
                @mkdir($directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs');
                foreach ($library as $file) {
                    $path_from = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
                    $path_to = $directory . DIRECTORY_SEPARATOR . 'library';
                    safe_copy($file, $path_from, $path_to);
                }
                foreach ($database as $file) {
                    $path_from = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database';
                    $path_to = $directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database';
                    safe_copy($file, $path_from, $path_to);
                }
                foreach ($supplement as $file) {
                    $path_from = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement';
                    $path_to = $directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement';
                    safe_copy($file, $path_from, $path_to);
                }
                foreach ($pngs as $file) {
                    $path_from = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';
                    $path_to = $directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';
                    safe_copy($file, $path_from, $path_to);
                }
                die('Done');
            } else {
                die('Error! Access denied or directory cannot be created.');
            }
        }

        database_connect($usersdatabase_path, 'users');
        $backup_dir = get_setting($dbHandle, 'backup_dir');
        $dbHandle = null;
        ?>

        <table style="width: 100%"><tr><td class="details alternating_row"><b>Backup</b></td></tr></table>
        <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:500px">
            <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">
                Enter the directory path, where the backup copy should be created:
            </div>
            <div class="separator" style="margin:0"></div>
            <div class="alternating_row items ui-corner-bottom">
                <button class="open-dirs-button">Browse directories</button>
                <form action="backup.php" method="GET">
                    <input type="hidden" name="backup" value="1">
                    <input type="text" size="50" style="width:435px" name="directory" value="<?php if (!empty($backup_dir)) print $backup_dir; ?>"><br>
                    Total library size: <?php print round($required_space / (1024 * 1024 * 1024), 3); ?> GB (<?php print number_format($f_number, '0', '.', ','); ?> files)<br>
                    Make sure that the destination drive has sufficient free space.<br>
                    <input type="submit" value="Proceed">
                </form>
            </div>
        </div>

        <?php
    } elseif (!empty($_GET['restore'])) {

        $directory = '';
        if (isset($_GET['directory']))
            $directory = $_GET['directory'];
        if (substr($directory, -1) == DIRECTORY_SEPARATOR)
            $directory = substr($directory, 0, -1);

        if (!empty($directory)) {

            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') $directory = str_replace("/", "\\", $directory);

            if (!is_dir($directory)) {
                $is_dir = false;
            } else {
                $is_dir = true;
            }

            if ($is_dir && is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library')) {

                if (!is_readable($directory . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'library.sq3'))
                    die('Error! Access denied or directory does not exist.');

                database_connect($usersdatabase_path, 'users');
                save_setting($dbHandle, 'backup_dir', $directory);
                $dbHandle = null;

                if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                    exec("del /q \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . "\"");
                    exec("rmdir \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . "\" /s/q");
                    exec("rmdir \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement' . "\" /s/q");
                    exec("rmdir \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs' . "\" /s/q");
                    exec("xcopy \"" . $directory . DIRECTORY_SEPARATOR . 'library' . "\" \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . "\" /c /v /q /s /e /h /y");
                } else {
                    exec("rm -f \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . "*.*\"");
                    exec("rm -rf \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'database' . "\"");
                    exec("rm -rf \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'supplement' . "\"");
                    exec("rm -rf \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs' . "\"");
                    exec(escapeshellcmd("cp -r \"" . $directory . DIRECTORY_SEPARATOR . 'library' . "\" \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . "\""));
                }
                die('Done');
            } else {
                die('Error! Access denied or directory does not exist.');
            }
        }

        database_connect($usersdatabase_path, 'users');
        $backup_dir = get_setting($dbHandle, 'backup_dir');
        $dbHandle = null;
        ?>

        <table style="width: 100%"><tr><td class="details alternating_row"><b>Restore</b></td></tr></table>
        <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:500px">
            <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">
                Enter the directory path, where the backup copy is stored:<br>
            </div>
            <div class="separator" style="margin:0"></div>
            <div class="alternating_row items ui-corner-bottom">
                <button class="open-dirs-button">Browse directories</button>
                <form action="backup.php" method="GET">
                    <input type="hidden" name="restore" value="1">
                    <input type="text" size="50" style="width:435px" name="directory" value="<?php if (!empty($backup_dir)) print $backup_dir; ?>"><br>
                    <span class="ui-state-error-text">This action will permanently delete your current library!</span><br>
                    <input type="submit" value="Proceed">
                </form>
            </div>
        </div>

        <?php
    } else {
        ?>
        <table style="width: 100%"><tr><td class="details alternating_row"><b>Backup Assistant</b></td></tr></table>
        <div style="text-align:center">
            <div style="width:250px;margin:auto;margin-top:100px">
                <div style="width:250px">
                    <span id="unlock-restore" class="ui-icon ui-icon-locked" style="float:right;cursor:pointer" title="Unlock restore"></span>
                </div>
                <div style="clear:both"></div>
                <div id="select-backup" class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:100px;float:left;text-align:left;cursor:pointer">
                    <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="text-align:center;font-size:13px;border:0">Backup</div>
                    <div class="separator" style="margin:0"></div>
                    <div class="alternating_row ui-corner-bottom" style="padding:8px 7px;overflow:auto;height:20px">
                        <span class="ui-icon ui-icon-document" style="float:left;margin-left:19px"></span>
                        <span class="ui-icon ui-icon-arrowthick-1-e" style="float:left"></span>
                        <span class="ui-icon ui-icon-disk" style="float:left"></span>
                    </div>
                </div>
                <div id="select-restore" class="item-sticker ui-widget-content ui-corner-all ui-state-disabled" style="margin-left:4px;margin-top:4px;width:100px;float:right;text-align:left;cursor:pointer">
                    <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="text-align:center;font-size:13px;border:0">Restore</div>
                    <div class="separator" style="margin:0"></div>
                    <div class="alternating_row ui-corner-bottom" style="padding:8px 7px;overflow:auto;height:20px">
                        <span class="ui-icon ui-icon-disk" style="float:left;margin-left:19px"></span>
                        <span class="ui-icon ui-icon-arrowthick-1-e" style="float:left"></span>
                        <span class="ui-icon ui-icon-document" style="float:left"></span>
                    </div>
                </div>
            </div>
            <div style="clear:both"></div>
            <div><br><br>Make sure that nobody is using the library.</div>
        </div>

        <?php
    }
    ?>
    <?php
} else {
    print "<p>Super User permissions required.</p>";
}
?>

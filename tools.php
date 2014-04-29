<?php
include_once 'data.php';
?>
<div class="leftindex" style="float:left;width:240px;height:100%;overflow:scroll;margin:0px;padding:0px;border:0px" id="tools-left">
    <table cellspacing=0 style="margin:8px 0 6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="settingslink">
                Settings
            </td>
        </tr>
    </table>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="detailslink">
                Installation Details
            </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="fontslink">
                Fonts & Colors
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="userslink">
                User Management
            </td>
        </tr>
    </table>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="backuplink">
                Backup / Restore
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="synclink">
                Synchronize
            </td>
        </tr>
    </table>
    <?php
    }
    if ($_SESSION['auth'] && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="duplicateslink">
                Find Duplicates
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="renamejournallink">
                Rename Journal
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="renamecategorylink">
                Edit Categories
            </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton" id="aboutlink">
                About I, Librarian
            </td>
        </tr>
    </table>
</div>
<div style="height:100%;overflow:auto" id="right-panel"></div>
<?php
include_once 'data.php';
include_once 'functions.php';
?>
<table cellspacing="0" class="noprint" style="width:99%;margin-left:4px">
    <tr>
        <td class="topindex" id="bottomrow" style="padding-top:3px;line-height:22px;height:22px">
            <a href="leftindex.php?select=library" title="All Items" class="topindex topindex_clicked ui-corner-all" id="link-library">Library</a>
            <?php
            if (isset($_SESSION['auth'])) {
            ?>
            <a href="leftindex.php?select=shelf" title="Personal Shelf" class="topindex ui-corner-all" id="link-shelf">Shelf</a>
            <a href="leftindex.php?select=desktop" title="Create/Open Projects" class="topindex ui-corner-all" id="link-desk">Desk</a>
            <a href="leftindex.php?select=clipboard" title="Temporary List" class="topindex ui-corner-all" id="link-clipboard">Clipboard</a>
            <?php
            if (isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {
            ?>
            <a href="addarticle.php" class="topindex ui-corner-all" id="link-record">Add Record</a>
            <?php
            }
            ?>
            <a href="tools.php" class="topindex ui-corner-all" id="link-tools">Tools</a>
            <?php
            }
            ?>
        </td>
        <td class="topindex" style="padding-top:4px">
            <table style="float:right">
                <tr>
                    <td style="line-height:20px;height:20px">
                        <span id="link-signout" style="cursor: pointer">Sign Out</span>
                    </td>
                    <td  style="padding:0 6px">
                        <button id="signedin" style="height:20px;width:20px">Sign Out</button>
                    </td>
                    <td style="line-height:20px;height:20px">
                        <span id="username-span"><?php print $_SESSION['user'] ?></span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
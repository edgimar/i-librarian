<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect($database_path, 'library');

$id_query = $dbHandle->quote($_SESSION['user_id']);

$result = $dbHandle->query("SELECT DISTINCT projects.projectID as projectID,project,projects.userID AS creator
        FROM projects LEFT JOIN projectsusers ON projects.projectID=projectsusers.projectID
        WHERE projects.userID=$id_query OR projectsusers.userID=$id_query ORDER BY project COLLATE NOCASE ASC");
$projects = $result->fetchAll(PDO::FETCH_ASSOC);
$firstproject = '';
if (!empty($projects))
    $firstproject = $projects[0]['projectID'];

$dbHandle->exec("ATTACH DATABASE '" . $database_path . "users.sq3' AS usersdatabase");
$result2 = $dbHandle->query("SELECT userID,username FROM users ORDER BY username COLLATE NOCASE ASC");
$users = $result2->fetchAll(PDO::FETCH_ASSOC);
$dbHandle->exec("DETACH DATABASE '" . $database_path . "users.sq3'");
$number_of_users = count($users);
?>
<div class="leftindex" id="leftindex-left" style="float:left;width:233px;height:100%;overflow:scroll">
    <form id="quicksearch" action="search.php" method="GET" target="rightpanel">
        <table cellspacing="0" style="width:100%;border-bottom:1px solid #b5b6b8">
            <tr>
                <td class="quicksearch ui-state-highlight">
                    <input type="text" size="28" name="anywhere" style="width:99%" value="<?php print isset($_SESSION['session_anywhere']) ? htmlspecialchars($_SESSION['session_anywhere']) : 'Quick Search'; ?>">
                </td>
            </tr>
            <tr>
                <td class="quicksearch ui-state-highlight" style="line-height:16px">
                    <table border=0 cellspacing=0 cellpadding=0 style="float:left">
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="anywhere_separator" value="AND" style="display:none" checked>
                                <span class="ui-icon ui-icon-radio-on" style="float:left"></span>AND
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="anywhere_separator" value="OR" style="display:none">
                                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>OR
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="anywhere_separator" value="PHRASE" style="display:none">
                                <span class="ui-icon ui-icon-radio-off" style="float:left"></span>phrase
                            </td>
                        </tr>
                    </table>
                    <button id="search" style="width:36px;height:20px">Search</button><button id="clear" style="width:20px;height:20px">Clear</button>
                    <input type="hidden" name="select" value="desk">
                    <input type="hidden" name="project" value="<?php print $firstproject; ?>">
                    <input type="hidden" name="searchtype" value="metadata">
                    <input type="hidden" name="searchmode" value="quick">
                    <input type="hidden" name="rating[]" value="1">
                    <input type="hidden" name="rating[]" value="2">
                    <input type="hidden" name="rating[]" value="3">
                </td>
            </tr>
        </table>
    </form>
    <div id="advancedsearchbutton" class="ui-corner-bl leftleftbutton" style="width:6.2em;float:left;margin-left:8px;text-align:center;height:auto;cursor:pointer">
        Advanced
    </div>
    <div id="expertsearchbutton" class="ui-corner-br leftleftbutton" style="width:5.2em;float:left;margin-left:1px;text-align:center;height:auto;cursor:pointer">
        Expert
    </div>
    <div style="clear:both"></div>
    <br>
    <form action="ajaxdesk.php" method="GET">
        <input type="hidden" name="create" value="create">
        <input type="text" size="10" name="project" value="" style="width:125px;margin-left:3px" placeholder="Create project">
        <button id="createproject">Create</button>
    </form>
    <br><br>
    <?php
    foreach ($projects as $project) {
        ?>
        <table cellspacing=0 width="210px" style="margin:6px 0px" class="projectheader">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton">
                    <div style="width:200px;white-space:nowrap;overflow:hidden"><?php print htmlspecialchars($project['project']) ?></div>
                </td>
            </tr>
        </table>
        <div class="projectcontainer" id="project-<?php print intval($project['projectID']); ?>" style="display: none;width:200px;margin-left: 10px">
            <a href="discussion.php?project=<?php print htmlspecialchars(urlencode($project['projectID'])) ?>" target="_blank" style="display:block;width:8em">
                <span class="ui-icon ui-icon-comment" style="float:left;position:relative;top:-2px"></span>Discussion</a>
            <b>Creator</b> &bull; <?php print get_username($dbHandle, $database_path, $project['creator']) ?>
            <br>
            <?php
            if ($number_of_users > 1) {

                $collaborators = $dbHandle->query("SELECT userID FROM projectsusers WHERE projectID=" . intval($project['projectID']));
                $collaborators = $collaborators->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <table cellspacing=0>
                    <tr>
                        <td>Users:</td>
                        <td>Collaborators:</td>
                    </tr>
                    <tr>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="adduser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (!in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . $user['username'] . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                        <td>
                            <select size="<?php print min($number_of_users - 1, 8) ?>" style="width:96px" name="removeuser">
                                <?php
                                while (list($key, $user) = each($users)) {
                                    if (in_array($user['userID'], $collaborators) && $user['userID'] != $project['creator'])
                                        print '<option value="' . $user['userID'] . '">' . $user['username'] . '</option>' . PHP_EOL;
                                }
                                reset($users);
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table cellspacing=0 class="ui-state-highlight ui-corner-bottom adduser" style="margin-left:20px">
                                <tr>
                                    <td><span style="float:left">&nbsp;Add</span><span class="ui-icon ui-icon-arrowthick-1-e" style="float:right"></span></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table cellspacing=0 class="ui-state-highlight ui-corner-bottom removeuser" style="margin-left:10px">
                                <tr>
                                    <td><span class="ui-icon ui-icon-arrowthick-1-w" style="float:left"></span>Remove&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <br>
                <?php
            }
            ?>
            <form action="ajaxdesk.php" method="GET">
                <input type="hidden" name="rename" value="Rename">
                <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                <input type="text" size="10" name="project" value="<?php print htmlspecialchars($project['project']) ?>" style="width:115px">
                <button class="renamebutton">Rename</button><br>
            </form>
            <?php
            if ($_SESSION['user_id'] == $project['creator']) {
                ?>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="delete" value="">
                    <button class="deletebutton" style="margin:2px 0px">Delete</button>
                </form>
                <form action="ajaxdesk.php" method="GET">
                    <input type="hidden" name="id" value="<?php print htmlspecialchars($project['projectID']) ?>">
                    <input type="hidden" name="empty" value="">
                    <button class="emptybutton" style="margin:2px 0px">Empty</button>
                </form>
                <br>
                <?php
            }
            ?>
        </div>
        <?php
    } //while
    $dbHandle = null;
    ?>
</div>
<div class="alternating_row middle-panel"
     style="float:left;width:6px;height:100%;overflow:hidden;border-right:1px solid #b5b6b8;cursor:pointer">
    <span class="ui-icon ui-icon-triangle-1-w" style="position:relative;left:-5px;top:46%"></span>
</div>
<div style="width:100%;height:100%;overflow:auto" id="right-panel"></div>
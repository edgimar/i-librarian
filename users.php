<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    if (!empty($_GET['change_password']) && !empty($_GET['old_password']) && !empty($_GET['new_password1']) && !empty($_GET['new_password2'])
        && $_GET['new_password1'] == $_GET['new_password2']) {

        database_connect($usersdatabase_path, 'users');
        $new_password_query = $dbHandle->quote(md5($_GET['new_password1']));
        $old_password_query = $dbHandle->quote(md5($_GET['old_password']));
        $user_query = $dbHandle->quote($_SESSION['user_id']);
        $password_changed = $dbHandle->exec("UPDATE users SET password=$new_password_query WHERE userID=$user_query AND password=$old_password_query");
        $dbHandle = null;
    }

    if (!empty($_GET['delete']) && !empty($_GET['id'])) {

        database_connect($database_path, 'library');
        $dbHandle->exec("ATTACH DATABASE '".$database_path."users.sq3' AS userdatabase");

        $id_query = $dbHandle->quote($_GET['id']);

        $result = $dbHandle->query("SELECT projectID FROM projects WHERE userID=$id_query");

        while ($project = $result->fetch(PDO::FETCH_ASSOC)) {
            if (is_writable($database_path.'project'.$project['projectID'].'.sq3')) unlink ($database_path.'project'.$project['projectID'].'.sq3');
        }

        $result = null;

        $dbHandle->beginTransaction();
        
        $result = $dbHandle->query("SELECT MIN(userID) FROM userdatabase.users");
        $minID = $result->fetchColumn();
        $result = null;

        $dbHandle->exec("DELETE FROM userdatabase.users WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM userdatabase.settings WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM projectsusers WHERE projectID IN (SELECT projectID FROM projects WHERE userID=$id_query)");
        $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID IN (SELECT projectID FROM projects WHERE userID=$id_query)");
        $dbHandle->exec("DELETE FROM projects WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM projectsusers WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM notes WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM searches WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM shelves WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM yellowmarkers WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM annotations WHERE userID=$id_query");
        $dbHandle->exec("UPDATE library SET added_by=$minID WHERE added_by=$id_query");
        $dbHandle->exec("UPDATE library SET modified_by=$minID WHERE modified_by=$id_query");

        $dbHandle->commit();
        $dbHandle->exec("DETTACH DATABASE '".$database_path."users.sq3'");
        $dbHandle = null;

    }

    if (!empty($_GET['create_user']) && !empty($_GET['username']) && !empty($_GET['password']) && !empty($_GET['permissions'])) {

        database_connect($usersdatabase_path, 'users');
        $username_query = $dbHandle->quote($_GET['username']);
        $password_query = $dbHandle->quote(md5($_GET['password']));
        $permissions_query = $dbHandle->quote($_GET['permissions']);
        $dbHandle->exec("INSERT INTO users (username,password,permissions) VALUES($username_query,$password_query,$permissions_query)");
        $dbHandle = null;
    }

    if (!empty($_GET['change_permissions']) && !empty($_GET['id'])) {

        if ($_GET['new_permissions'] == 'A') {
            $new_permissions = 'A';
        } elseif ($_GET['new_permissions'] == 'U') {
            $new_permissions = 'U';
        } elseif ($_GET['new_permissions'] == 'G') {
            $new_permissions = 'G';
        }

        database_connect($usersdatabase_path, 'users');
        $permissions_query = $dbHandle->quote($new_permissions);
        $id_query = $dbHandle->quote($_GET['id']);
        $dbHandle->exec("UPDATE users SET permissions=$permissions_query WHERE userID=$id_query");
        $dbHandle = null;
    }

    if (!empty($_GET['rename']) && !empty($_GET['id']) && !empty($_GET['username'])) {

        database_connect($usersdatabase_path, 'users');
        $username_query = $dbHandle->quote($_GET['username']);
        $id_query = $dbHandle->quote($_GET['id']);
        $dbHandle->exec("UPDATE users SET username=$username_query WHERE userID=$id_query");
        $dbHandle = null;
    }

    if (!empty($_GET['force_password']) && !empty($_GET['id']) && !empty($_GET['new_password'])) {

        database_connect($usersdatabase_path, 'users');
        $password_query = $dbHandle->quote(md5($_GET['new_password']));
        $id_query = $dbHandle->quote($_GET['id']);
        $dbHandle->exec("UPDATE users SET password=$password_query WHERE userID=$id_query");
        $dbHandle = null;
    }

    print '<form action="users.php" method="GET">';

    print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

    print "<tr><td class=\"details alternating_row\"><b>Change password for user $_SESSION[user]</b></td></tr>";

    print "<tr><td class=\"details\">";

    print "Old Password:<input type=\"password\" size=\"10\" name=\"old_password\">
    New Password:<input type=\"password\" size=\"10\" name=\"new_password1\">
    Re-type New Password:<input type=\"password\" size=\"10\" name=\"new_password2\"><br>";

    print "</td></tr>";

    print "<tr><td class=\"details\">";

    if (isset($password_changed) && $password_changed == 1) print 'Password Changed<br>';

    print "<input type=\"submit\" name=\"change_password\" value=\"Change\">";

    print "</td></tr></table>";

    print '</form><br>';

    if ($_SESSION['permissions'] == 'A') {

        $number1 = rand  (2, 9);
        $number2 = rand  (2, 9);
        $upper1 = rand  (65, 90);
        if ($upper1 == '79') $upper1 = '80';
        $upper2 = rand  (65, 90);
        if ($upper2 == '79') $upper2 = '80';
        $lower1 = rand  (97, 122);
        if ($lower1 == '108') $lower1 = '109';
        $lower2 = rand  (97, 122);
        if ($lower2 == '108') $lower2 = '109';
        $random_password = chr($upper1).chr($lower1).$number1.$number2.chr($lower2).chr($upper2);

        print '<form action="users.php" method="GET" id="users-create">';

        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

        print "<tr><td class=\"details alternating_row\"><b>Create new user</b></td></tr>";

        print "<tr><td class=\"details\">";

        print "Username:<input type=\"text\" size=\"10\" name=\"username\">
        Password:<input type=\"text\" size=\"6\" name=\"password\" value=\"$random_password\">
        Permissions:<input type=\"radio\" name=\"permissions\" value=\"A\">Super User
        <input type=\"radio\" name=\"permissions\" value=\"U\" checked>User
        <input type=\"radio\" name=\"permissions\" value=\"G\">Guest<br>";

        print "</td></tr>";

        print "<tr><td class=\"details\">";

        print "<input type=\"submit\" name=\"create_user\" value=\"Create\">";

        print "</td></tr></table>";

        print '</form>';

        database_connect($usersdatabase_path, 'users');

        $users = $dbHandle->query("SELECT userID,username,permissions FROM users");

        $dbHandle = null;

        print '<br>Be careful. Some of these changes cannot be undone&#172;';

        print '<br><table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

        print "<tr><td class=\"details alternating_row\" ><b>User administration:</b></td></tr>";

        print '</table>';

        while ($username = $users->fetch(PDO::FETCH_ASSOC)) {

            $number1 = rand  (2, 9);
            $number2 = rand  (2, 9);
            $upper1 = rand  (65, 90);
            if ($upper1 == '79') $upper1 = '80';
            $upper2 = rand  (65, 90);
            if ($upper2 == '79') $upper2 = '80';
            $lower1 = rand  (97, 122);
            if ($lower1 == '108') $lower1 = '109';
            $lower2 = rand  (97, 122);
            if ($lower2 == '108') $lower2 = '109';
            $random_password = chr($upper1).chr($lower1).$number1.$number2.chr($lower2).chr($upper2);

            if ($username['permissions'] == 'A') {
                    $user_string = '<input type="radio" name="new_permissions" value="A" checked>Super User<input type="radio" name="new_permissions" value="U">User<input type="radio" name="new_permissions" value="G">Guest';
            } elseif ($username['permissions'] == 'U') {
                    $user_string = '<input type="radio" name="new_permissions" value="A">Super User<input type="radio" name="new_permissions" value="U" checked>User<input type="radio" name="new_permissions" value="G">Guest';
            } elseif ($username['permissions'] == 'G') {
                    $user_string = '<input type="radio" name="new_permissions" value="A">Super User<input type="radio" name="new_permissions" value="U">User<input type="radio" name="new_permissions" value="G" checked>Guest';
            }
    ?>
            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%"><tr>
            <td class="details" style="white-space: nowrap">
            <form action="users.php" method="GET" id="users-delete">
            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
            <input type="hidden" name="delete" value="1">
            <input type="submit" value="Delete" class="deletebutton" <?php if ($username['userID'] == 1) print 'disabled'; ?>> ID <?php print $username['userID'] ?>
            </form>
            </td>
            <td class="details" style="white-space: nowrap">
            <form action="users.php" method="GET" id="users-perm">
            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
            <input type="submit" name="change_permissions" value="Change" <?php if ($username['userID'] == 1) print 'disabled'; ?>> <?php print $user_string ?>
            </form>
            </td>
            <td class="details">
            <form action="users.php" method="GET" id="users-rename">
            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
            <input type="submit" name="rename" value="Rename"><input type="text" size="10" name="username" value="<?php print $username['username'] ?>">
            </form>
            </td>
            <td class="details">
            <form action="users.php" method="GET" id="users-force">
            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
            <input type="submit" name="force_password" value="Force Password"><input type="text" size="6" name="new_password" value="<?php print $random_password ?>">
            </form>
            </td>
            </tr></table>
<?php
        }
    }
?>
    <div id="delete-confirm" title="Delete User?"></div>
<?php
}
?>
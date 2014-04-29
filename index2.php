<?php
include_once 'data.php';
include_once 'functions.php';

//UPGRADING DATABASE
if (is_file($database_path . 'library.sq3')) {
    $isupgraded = false;
    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='categories'");
    $newtable = $result->fetchColumn();
    $result = null;
    $result = $dbHandle->query("PRAGMA main.table_info(library)");
    while ($libtable = $result->fetch(PDO::FETCH_NAMED)) {
        if ($libtable['name'] == 'bibtex') {
            $isupgraded = true;
            break;
        }
    }
    $result = null;
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='library_log'");
    $logtable = $result->fetchColumn();
    $result = null;
    $dbHandle = null;
    //UPGRADE 2.0 to 2.1
    if ($newtable == 0)
        include_once 'migrate.php';
    //UPGRADE 2.1 to 2.5
    if (!$isupgraded)
        include_once 'migrate2.php';
    //UPGRADE 2.7 to 2.8
    if ($logtable == 0)
        include_once 'migrate3.php';
}

$ini_array = parse_ini_file("ilibrarian.ini");

/////////////// LDAP SETTINGS //////////////////////////////
$ldap_active = $ini_array['ldap_active'];
$ldap_version = $ini_array['ldap_version'];
$ldap_server = $ini_array['ldap_server'];
$ldap_port = $ini_array['ldap_port'];
$ldap_basedn = $ini_array['ldap_basedn'];
$ldap_rdn = $ini_array['ldap_rdn'];
$ldap_cn = $ini_array['ldap_cn'];
$ldap_admin_cn = $ini_array['ldap_admin_cn'];
$ldap_filter = $ini_array['ldap_filter'];
if (!extension_loaded('ldap'))
    $ldap_active = false;
/////////////// END LDAP SETTINGS //////////////////////////////

///////////////start sign out//////////////////////////////

if (isset($_GET['action']) && $_GET['action'] == 'signout') {
    // DELETE USER'S FILE CACHE
    $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id(). DIRECTORY_SEPARATOR .'*', GLOB_NOSORT);
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file))
            @unlink($clean_file);
    }
    $_SESSION = array();
    session_destroy();
    die('OK');
}
///////////////end sign out////////////////////////////////

///////////////start register new user////////////////////
if (!$ldap_active && isset($_POST['form']) && $_POST['form'] == 'signup' && !empty($_POST['user']) && !empty($_POST['pass']) && !empty($_POST['pass2'])) {

    if ($_POST['pass'] == $_POST['pass2']) {

        database_connect($database_path, 'library');

        $quoted_path = $dbHandle->quote($usersdatabase_path . 'users.sq3');

        $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

        $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

        $result = $dbHandle->query("SELECT count(*) FROM userdatabase.users");
        $users = $result->fetchColumn();
        $result = null;

        $result = $dbHandle->query("SELECT setting_value FROM userdatabase.settings WHERE setting_name='settings_global_default_permissions' LIMIT 1");
        $default_permissions = $result->fetchColumn();
        $result = null;

        if ($users == 0) {
            $permissions = 'A';
        } else {
            !empty($default_permissions) ? $permissions = $default_permissions : $permissions = 'U';
        }

        $rows = 0;

        $quoted_user = $dbHandle->quote($_POST['user']);

        if ($users > 0) {
            $count = $dbHandle->query("SELECT count(*) FROM userdatabase.users WHERE username=$quoted_user LIMIT 1");
            $rows = $count->fetchColumn();
            $count = null;
        }

        if ($rows == 0) {

            $dbHandle->exec("INSERT INTO userdatabase.users (username,password,permissions) VALUES ($quoted_user,'" . md5($_POST['pass']) . "','$permissions')");

            $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM userdatabase.users");
            $id = $last_id->fetchColumn();
            $last_id = null;

            $dbHandle->exec("INSERT INTO projects (userID,project) VALUES ($id,$quoted_user || '''s project')");

            $_SESSION['user_id'] = $id;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['password'] = md5($_POST['pass']);
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        } else {
            $dbHandle->exec("ROLLBACK");
            die('Username already exists.');
        }

        $dbHandle->exec("COMMIT TRANSACTION");

        if (isset($_SESSION['auth'])) {

            $connection = '';
            $proxy_setting = array();

            $proxy = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE setting_name LIKE 'settings_global_%'");

            $proxy_settings = $proxy->fetchAll(PDO::FETCH_ASSOC);

            while (list($key, $proxy_setting) = each($proxy_settings)) {
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'proxy') {
                    $connection = 'proxy';
                    break;
                }
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'autodetect') {
                    $_SESSION['connection'] = "autodetect";
                    break;
                }
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'url') {
                    $_SESSION['connection'] = "url";
                }
                if ($proxy_setting['setting_name'] == 'settings_global_wpad_url') {
                    $_SESSION['wpad_url'] = $proxy_setting['setting_value'];
                }
            }

            if ($connection == "proxy") {
                $proxy_setting = array();
                reset($proxy_settings);
                while (list($key, $proxy_setting) = each($proxy_settings)) {
                    $setting_name = substr($proxy_setting['setting_name'], 16);
                    $_SESSION[$setting_name] = $proxy_setting['setting_value'];
                }
            }

            ####### create directory for caching ########

            @mkdir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id());
        }

        $dbHandle->exec("DETACH DATABASE $quoted_path");
        $dbHandle = null;
    } else {

        die('Password typo.');
    }

    die('OK');
}

///////////////end register////////////////////////////////

///////////////auto sign in start////////////////////////////////

if (!isset($_POST['form']) && !isset($_SESSION['auth']) && $ini_array['autosign'] == 1) {

    database_connect($usersdatabase_path, 'users');
    $quoted_user = $dbHandle->quote($ini_array['username']);
    $autosign_query = $dbHandle->query("SELECT password FROM users WHERE username=$quoted_user");
    if ($autosign_query) $autosign = true;

    if ($autosign) {
        $autosign_user = $autosign_query->fetch(PDO::FETCH_ASSOC);
        $_POST['form'] = 'signin';
        $_POST['user'] = $ini_array['username'];
        $_POST['pass'] = $autosign_user['password'];
        $_POST['keepsigned'] = 1;
    }
    $autosign_query = null;
    $dbHandle = null;
}

///////////////auto sign in end////////////////////////////////

///////////////start authentication////////////////////////
if (isset($_POST['form']) && $_POST['form'] == 'signin' && !empty($_POST['user']) && !empty($_POST['pass']) && !isset($_SESSION['auth'])) {

    $username = $_POST['user'];
    $password = $_POST['pass'];

    database_connect($usersdatabase_path, 'users');

    $username_quoted = $dbHandle->quote($username);

    /* IS THE USER AN LDAP USER? */
    if ($ldap_active) {

        if (!$ldap_connect = ldap_connect($ldap_server, $ldap_port))
            die("Could not connect to LDAP server");

        if (!ldap_set_option($ldap_connect, LDAP_OPT_PROTOCOL_VERSION, $ldap_version))
            die("Failed to set version to protocol $ldap_version");

        $ldap_dn = $ldap_cn . $username . ',' . $ldap_rdn['users'] . ',' . $ldap_basedn;

        /* AUTHENTICATE */
        if ($ldap_bind = @ldap_bind($ldap_connect, $ldap_dn, $password)) {

            /* AUTHORIZE ADMIN */
            $ldap_dn = $ldap_admin_cn . ',' . $ldap_rdn['groups'] . ',' . $ldap_basedn;
            $ldap_sr = @ldap_read($ldap_connect, $ldap_dn, '(' . $ldap_filter . $username . ')', array('memberUid'));
            $ldap_info_group = @ldap_get_entries($ldap_connect, $ldap_sr);

            $permissions = 'U';
            if ($ldap_info_group['count'] > 0)
                $permissions = 'A';

            $dbHandle->beginTransaction();

            $count = $dbHandle->query("SELECT count(*) FROM users WHERE username=$username_quoted LIMIT 1");
            $rows = $count->fetchColumn();
            $count = null;

            if ($rows == 0) {
                
                $dbHandle->exec("INSERT INTO users (username,password,permissions) VALUES ($username_quoted,'','$permissions')");

                $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM users");
                $id = $last_id->fetchColumn();
                $last_id = null;

                $dbHandle->exec("INSERT INTO projects (userID,project) VALUES ($id,$username_quoted || '''s project')");
            }
            
            $result = $dbHandle->query("SELECT userID FROM users WHERE username=$username_quoted LIMIT 1");
            $id = $result->fetchColumn();
            $result = null;

            $dbHandle->commit();
      
            $_SESSION['user_id'] = $id;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['password'] = '';
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        }
    } else {

        /* IF LDAP NOT ENABLED, CHECK THE LOCAL DB */
        if (isset($autosign) && $autosign) {
            $result = $dbHandle->query("SELECT userID,permissions FROM users WHERE username=$username_quoted LIMIT 1");
        } else {
            $result = $dbHandle->query("SELECT userID,permissions FROM users WHERE username=$username_quoted AND password='" . md5($_POST["pass"]) . "' LIMIT 1");
        }
        $user = $result->fetch(PDO::FETCH_ASSOC);
        $result = null;

        if (!empty($user['userID'])) {
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['permissions'] = $user['permissions'];
            $_SESSION['auth'] = true;
            $_SESSION['watermarks'] = '';
            if (isset($autosign) && $autosign) {
                $_SESSION['password'] = $_POST['pass'];
            } else {
                $_SESSION['password'] = md5($_POST['pass']);
            }
        }
    }

    /* OK, THIS IS A REGISTERED USER. DO THE PROXY SETTINGS AND CREATE A TEMP DIR */
    if (isset($_SESSION['auth'])) {

        if (isset($_POST['keepsigned']) && $_POST['keepsigned'] == 1) {
            $keepsigned = 1;
            save_setting($dbHandle, 'keepsigned', '1');
            setcookie(session_name(), session_id(), time() + 604800);
        } else {
            save_setting($dbHandle, 'keepsigned', '');
            setcookie(session_name(), session_id(), 0);
        }

        $connection = '';
        $proxy_setting = array();

        $proxy = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE setting_name LIKE 'settings_global_%'");

        $proxy_settings = $proxy->fetchAll(PDO::FETCH_ASSOC);

        while (list($key, $proxy_setting) = each($proxy_settings)) {
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'proxy') {
                $connection = 'proxy';
            }
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'autodetect') {
                $_SESSION['connection'] = "autodetect";
            }
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'url') {
                $_SESSION['connection'] = "url";
            }
            if ($proxy_setting['setting_name'] == 'settings_global_wpad_url') {
                $_SESSION['wpad_url'] = $proxy_setting['setting_value'];
            }
            if ($proxy_setting['setting_name'] == 'settings_global_watermarks') {
                $_SESSION['watermarks'] = $proxy_setting['setting_value'];
            }
        }

        if ($connection == "proxy") {
            $proxy_setting = array();
            reset($proxy_settings);
            while (list($key, $proxy_setting) = each($proxy_settings)) {
                $setting_name = substr($proxy_setting['setting_name'], 16);
                $_SESSION[$setting_name] = $proxy_setting['setting_value'];
            }
        }

        $result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=" . intval($_SESSION['user_id']));

        while ($custom_settings = $result->fetch(PDO::FETCH_ASSOC)) {

            $_SESSION[substr($custom_settings['setting_name'], 9)] = $custom_settings['setting_value'];
        }

        ####### create directory for caching ########

        @mkdir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id());
        
    } else {
        die('Bad username or password.');
    }

    $dbHandle = null;

    if (!isset($autosign) || $autosign != 1) die('OK');
}
///////////////end authentication/////////////////////////
?>
<!DOCTYPE html>
<html style="width:100%;height:100%">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>I, Librarian <?php print $version ?></title>
        <link type="text/css" href="css/fonts.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/custom-theme/jquery-ui-custom.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/player-controls.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/tipsy.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/jquery.jgrowl.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/jqueryFileTree.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/static.css?v=<?php print $version ?>" rel="stylesheet">
        <style type="text/css">
<?php include_once 'style.php'; ?>
        </style>
        <script type="text/javascript" src="js/jquery.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.form.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/tiny_mce/tiny_mce.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery-ui-custom.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.tipsy.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.jgrowl.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jplayer/jquery.jplayer.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jqueryFileTree.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.hotkeys.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/plupload/plupload.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/javascript.js?v=<?php print $version ?>"></script>
    </head>
    <body style="margin:0;border:0;padding:0;width:100%;height:100%;overflow:hidden">
<?php
if (isset($_SESSION['auth'])) {
    ?>
            <div style="display:none;height:100%;overflow:hidden" id="items-container"></div>
            <div style="height:29px;width:100%" id="top-panel"></div>
            <div style="height:100%;overflow:hidden" id="bottom-panel"></div>
            <div style="height:100%;overflow:hidden;display:none" id="addrecord-panel"></div>
            <div id="dialog-confirm" style="display:none"></div>
            <div id="advancedsearch" style="display:none"></div>
            <div id="expertsearch" style="display:none"></div>
            <div id="exportdialog" style="display:none"></div>
            <div id="omnitooldiv" style="display:none"></div>
            <div id="delete-file" title="Delete Record?" style="display:none"></div>
            <div id="dialog-error" style="display:none"></div>
            <div id="open-dirs"></div>
    <?php
} else {
    ?>
            <div id="signin-background" style="height:100%;overflow:hidden">
                <div class="item-sticker ui-widget-content ui-corner-all" style="position:absolute;top:0;left:0;width:300px" id="signin-container">
                    <div class="topindex" id="top-panel-form" style="padding:7px 10px;font-size:15px;font-weight:bold;text-shadow: 1px 1px 1px #333">I, Librarian <?php print $version ?></div>
                    <div class="separator" style="margin:0"></div>
                    <div class="alternating_row ui-corner-bottom" style="padding:4px 6px;overflow:auto;height:140px">
                        <form action="index2.php" method="POST" id="signinform">
                            <input type="hidden" name="form" value="signin">
                            <table style="width:100%">
                                <tr>
                                    <td style="padding:6px;width:90px">
                                        <?php print ($ldap_active) ? 'LDAP ' : '' ?>User:
                                    </td>
                                    <td style="padding:6px">
    <?php
    $signin_mode = '';
    $disallow_signup = '';
    database_connect($usersdatabase_path, 'users');
    $all_users = $dbHandle->query("SELECT username FROM users ORDER BY username COLLATE NOCASE");
    $all_users_count = $dbHandle->query("SELECT count(*) FROM users");
    $setting1 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='settings_global_signin_mode' LIMIT 1");
    $setting2 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='settings_global_disallow_signup' LIMIT 1");
    $rows = $all_users_count->fetchColumn();
    $signin_mode = $setting1->fetchColumn();
    $disallow_signup = $setting2->fetchColumn();
    $dbHandle = null;
    if ($signin_mode == 'textinput' || $ldap_active) {
        print '<input type="text" name="user" size="10" value="" style="width:90%">';
    } else {
        print '<select name="user" style="width:90%"><option></option>';
        while ($user = $all_users->fetch(PDO::FETCH_ASSOC)) {
            print '<option';
            if ($rows == 1)
                print ' selected';
            print ' value="' . $user['username'] . '">' . $user['username'] . '</option>';
        }
        print '</select>';
    }
    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        Password:
                                    </td>
                                    <td style="padding:6px">
                                        <input type="password" name="pass" size="10" value="" style="width:90%">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        <button id="signinbutton">Sign In</button>
                                    </td>
                                    <td style="padding:6px;vertical-align:middle">
    <?php
    if (!$ldap_active && $disallow_signup != '1')
        print '<span style="cursor:pointer" id="register">Create Account</span>';
    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px" colspan=2>
                                        <div id="sign-options">
                                            <span class="ui-icon ui-icon-triangle-1-s" style="float:right"></span>
                                            <span class="ui-icon ui-icon-gear" style="float:right"></span>
                                        </div>
                                        <div style="clear:both"></div>
                                        <div id="sign-options-list" class="ui-corner-all lib-shadow-bottom" style="display:none;position:fixed;background-color:rgba(255,255,255,0.7);border:1px solid #afa9a8;padding:10px">
                                            <table cellspacing=0>
                                                <tr>
                                                    <td class="select_span" style="line-height:16px">
                                                        <input type="checkbox" name="keepsigned" value="1" style="display:none" checked>
                                                        <span class="ui-icon ui-icon-check" style="float:left">
                                                        </span>Keep me signed in&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                            <br>
                                            <span style="margin-left:16px;cursor: pointer" id="openresetpassword">Forgotten password</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        <form action="index2.php" method="POST" id="signupform" style="display: none">
                            <input type="hidden" name="form" value="signup">
                            <table style="width:100%">
                                <tr>
                                    <td style="padding:6px;width:90px">
                                        User:
                                    </td>
                                    <td style="padding:6px">
                                        <input type="text" name="user" size="10" value="" style="width:90%">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        Password:
                                    </td>
                                    <td style="padding:6px">
                                        <input type="password" name="pass" size="10" value="" style="width:90%">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        Retype:
                                    </td>
                                    <td style="padding:6px">
                                        <input type="password" name="pass2" size="10" value="" style="width:90%">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        <button id="signupbutton">Sign Up</button>
                                    </td>
                                    <td style="padding:6px;vertical-align:middle">
                                        <span style="cursor:pointer" id="login">Sign In</span>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        <div id="resetpassword-container" style="display:none"></div>
                        <div id="errors" style="display:none" title="Error!"></div>
                    </div>
                </div>
                <div id="credits" style="position: absolute;right:10px;bottom:10px;cursor:pointer">
                    I, Librarian <?php print $version ?> &copy; 2013 Martin Kucej &middot; GPLv3
                </div>
                <div style="position: absolute;left:10px;bottom:10px;cursor:pointer">
                    <a href="m/index.html" target="_blank">I, Librarian Mobile</a>
                </div>
            </div>
            <script type="text/javascript">index2.init()</script>
    <?php
}
?>
    </body>
</html>
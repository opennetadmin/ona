<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */
//
//
//
//
//
//  MP: this login is not currently used.  login is directly on html_desktop
//  This could later serve as a way to auth side apps/modules.  will probably
//  need some rewrites to do that though
//
//

//
// Save a redirect url..
if (!isset($_SESSION['redirect']))
    $_SESSION['redirect'] = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "{$baseURL}/"; 


// See if they're logging in right now
// MP: dont think this is getting used.. take it out after testing
if (isset($_REQUEST['logon'])) {

    // FIXME: Hard coded for now
    $_SESSION['ona']['auth']['user']['username'] = "guest";
    $_SESSION['ona']['auth']['user']['level'] = "0";

    // Log insecure logins
    if ($self['secure'] != 1)
        printmsg("NOTICE => Insecure (http) login requested!", 0);
    
/*    
    // Get the user record (usernames are case-insensitive .. all lower-case)
    $_REQUEST['username'] = strtolower($_REQUEST['username']);
    list($status, $rows, $user) = db_get_record($mysql, 'users', array('client_id' => $client['id'], 'username' => $_REQUEST['username'], 'active' => 1));
    if ($status != 0 or $rows != 1) {
        printmsg("NOTICE => Login failure! Invalid username: {$_REQUEST['username']} Client url: {$client_url}", 0);
        $_SESSION['login_failure'] = 'Invalid username';
        header("Location: {$https}/");
        exit();
    }
    
    // Get the MD5 of $passwd
    $passwd = md5($_REQUEST['password']);
    
    // Compare the given password and the db password
    if ($passwd != $user['passwd']) {
        printmsg("NOTICE => Login failure!  Password mismatch.  Client url: {$client_url}", 0);
        $_SESSION['login_failure'] = 'Incorrect password';
        header("Location: {$https}/");
        exit();
    }
    
    // Otherwise auth succeeded, proceed.
    
    // Make sure their ACL is saved in $_SESSION for quick reference
    save_users_acl_in_session();
    

    
*/
    
    // Log that the user logged in
    printmsg("INFO => Standalone login: Successful login as " . $_REQUEST['username'], 0);
    
    
    // Redirect them to the admin section of the site (javascript redirect doesn't bring up the IE security warning)
    // If they're an admin, redirect them to the https side - they might edit credit card info!
    // $redirect = auth('admin') ? $https : $http;
    $redirect = "{$http}{$baseURL}/";
    echo <<<EOL
    <html><body>
    <script type="text/javascript"><!--
        window.location = "{$redirect}";
    --></script>
    </body></html>
EOL;

}




// Otherwise display a login dialog box
else {

// Include xajax stuff (ajax calls will never make it past this line)
require_once($conf['inc_xajax_stuff']);

$message = '';
if (!empty($_SESSION['login_failure'])) $message = $_SESSION['login_failure'];
unset($_SESSION['login_failure']);

print <<<EOL

<html>
<head>
    <title>{$conf['title']} Standalone Login</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.inc.php">
    <link rel="shortcut icon" type="image/ico" href="{$images}/favicon.ico">
    <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
    {$conf['html_headers']}
</head>
<body>
<center>
    <h1 style="color: #5262F2;">OpenNetAdmin Login</h1>
    <div>
        <form id="standalone_loginform_form" onSubmit="return(false);">
            <input id="onapassword" type="hidden" name="onapassword">
            <input id="standalone" type="hidden" name="standalone" value="standalone">
            <table cellspacing="2" border="0" cellpadding="0">
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/user.png" alt="Username" title="Username" align="left" border="0" style="display: inline;">
                        <input
                            id="onausername"
                            name="onausername"
                            class="edit"
                            style="width: 100px; font-size: 8pt;" type="text" maxlength="64" tabindex="1" accesskey="u"
                            onkeypress="if (event.keyCode == 13) { el('getpass').focus(); }"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/key.png" alt="Password" title="Password" align="left" border="0" style="display: inline;">
                        <input
                            id="getpass"
                            name="getpass"
                            class="edit"
                            style="width: 100px; font-size: 8pt;" type="password" maxlength="64" tabindex="2" accesskey="p"
                            onkeypress="if (event.keyCode == 13) { el('loginbutton').click(); }"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <input id="loginbutton" class="button" style="font-size: smaller;" type="button" name="logon" value="Login"
                                onClick="el('onapassword').value = make_md5(el('getpass').value);
                                         xajax_window_submit('tooltips', xajax.getFormValues('standalone_loginform_form'), 'logingo');"
                        >
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <br>
    <span style="color: red; font-weight: bold;" id="loginmsg">{$message}</span>

    <script type="text/javascript"><!--
        /* Focus the username field */
        el('onausername').focus();
    //--></script>

    <br>

</center>
</body>
</html>

EOL;



}

?>
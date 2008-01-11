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
//
//
//
//
//
//
//
//
//
// Save a redirect url..
if (!isset($_SESSION['redirect']))
    $_SESSION['redirect'] = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "{$baseURL}/"; 


// See if they're logging in right now
if (isset($_REQUEST['logon'])) {
    
    // FIXME: Hard coded for now
    $_SESSION['ona']['auth']['user']['username'] = "guest";
    $_SESSION['ona']['auth']['user']['level'] = "0";
    
    // Save the timezone they say they have in the session
    $_SESSION['tz'] = $_REQUEST['timezone'];
    if (!is_numeric($_SESSION['tz'])) { $_SESSION['tz'] = 0; }
    
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
    
    // Update their record's atime
    list($status, $rows) = db_update_record($mysql, 'users', array('id' => $user['id']), array('atime' => date_mangle(gmtime())));
    
    // Save their user record in the session so we know they're logged in
    $_SESSION['auth']['user'] = $user;
    $_SESSION['auth']['client'] = $client;
    
    // Make sure their ACL is saved in $_SESSION for quick reference
    save_users_acl_in_session();
    
    // Make sure that either the user is an admin, or they are connecting from an allowed IP address
    if (!$_SESSION['auth']['acl']['admin']) {
        list($status, $rows, $ipacl) = db_get_record($mysql, 'ipacl', array('client_id' => $_SESSION['auth']['client']['id'], 'ip' => ip_mangle($_SERVER['REMOTE_ADDR'], 'numeric')));
        if (!($status == 0 and $rows == 1 and $ipacl['id'])) {
            // Log that the user logged in
            printmsg("NOTICE => Login: user/passwd was correct, but remote IP is not in ACL: " . $_SERVER['REMOTE_ADDR'], 0);
            // Redirect them to the admin section of the site
            $_SESSION['auth'] = array();
            $_SESSION['login_failure'] = 'Unauthorized location';
            header("Location: {$https}/");
            exit();
        }
    }
    
*/
    
    // Log that the user logged in
    printmsg("INFO => Successful login as " . $_REQUEST['username'], 0);
    
    
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

$message = '';
if (!empty($_SESSION['login_failure'])) $message = $_SESSION['login_failure'];
unset($_SESSION['login_failure']);

print <<<EOL

<html>
<body>
<center>
    <h1 style="color: #5262F2;">Admin Login</h1>
    <br>
    
    <span style="color: red; font-weight: bold;">{$message}</span>
    <div>
        <form action="{$baseURL}/login.php" method="POST" enctype="multipart/form-data" id="login">
            <table cellspacing="2" border="0" cellpadding="0">
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/user.png" alt="Username" title="Username" align="left" border="0" style="display: inline;">
                        <input 
                            id="username" 
                            name="username" 
                            class="edit" 
                            style="width: 100px; font-size: 8pt;" type="text" maxlength="64" tabindex="1" accesskey="u"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/key.png" alt="Password" title="Password" align="left" border="0" style="display: inline;">
                        <input 
                            id="password" 
                            name="password" 
                            class="edit" 
                            style="width: 100px; font-size: 8pt;" type="password" maxlength="64" tabindex="2" accesskey="p"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <input class="button" style="font-size: smaller;" type="submit" name="logon" value="Login">
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <script type="text/javascript"><!--
        /* Focus the username field */
        document.forms.login.username.focus();
    //--></script>
    
    <br>

</center>
</body>
</html>

EOL;



}

?>
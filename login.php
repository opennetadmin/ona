<?php

/* -------------------- COMMON HEADER ---------------------- */

// Find the include directory
$base = dirname(__FILE__);
while ($base and (!is_dir($base . '/include')) ) {
    $base = preg_replace('+/[^/]*$+', '', $base);
}   $include = $base . '/include';
if (!is_dir($include)) {
    print "ERROR => Couldn't find include folder!\n"; exit;
}

// Read information from config file
require_once($base . '/config/config.inc.php');
require_once($conf['inc_functions']);

/* --------------------------------------------------------- */

// See if they're logging in right now
if (isset($_REQUEST['logon'])) {

    // Save the timezone they say they have in the session
    $_SESSION['tz'] = $_REQUEST['timezone'];
    if (!is_numeric($_SESSION['tz'])) { $_SESSION['tz'] = 0; }

    // Log insecure logins
    if ($self['secure'] != 1) {
        printmsg("NOTICE => Insecure (http) login requested!", 0);
    }

    // First use the SERVER_NAME to determine what client we're authenticating against
    $client_url = preg_replace('/\.tsheets.com.*$/', '', $_SERVER['HTTP_HOST']);

    // Get the client record
    list($status, $rows, $client) = db_get_record($mysql, 'clients', array('url' => strtolower($client_url), 'active' => 1));
    if ($status != 0 or $rows != 1) {
        printmsg("NOTICE => Login failure! Invalid client URL: {$client_url}", 0);
        $_SESSION['login_failure'] = 'Invalid URL';
        header("Location: {$https}/");
        exit();
    }

    // Get the user record (usernames are case-insensitive)
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

    // Log that the user logged in
    printmsg("INFO => Successful login as " . $_REQUEST['username'] . " Client url: {$client_url}", 0);

}

// Redirect them to the admin section of the site (javascript redirect doesn't bring up the IE security warning)
// If they're an admin, redirect them to the https side - they might edit credit card info!
$redirect = auth('admin') ? $https : $http;
echo <<<EOL
<html><body>
<script type="text/javascript"><!--
    window.location = "{$redirect}/";
--></script>
</body></html>
EOL;

?>
<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */

// These store the output to be displayed
$status = 1;
$output = "ERROR => No module specified!\n";

// FIXME: Add IP Auth in Later --- or just use htaccess method
// Disconnect the user if their IP address isn't in our allowed list
// $remote_ip = ip_mangle($_SERVER['REMOTE_ADDR'], 'numeric');
// if (!in_array($remote_ip, $ips)) { print "1\r\nPermission denied!\n"; exit; }

printmsg("DEBUG => DCM_USER: {$_SERVER['PHP_AUTH_USER']}", 4);

// FIXME: for now this hard codes the user.. this needs to pay attention to the user passed in and the password!
// will this ever even happen?  it would come up as "unknown user" anyway
if ($_SERVER['PHP_AUTH_USER'] == '') {
    $_SESSION['ona']['auth']['user']['username']='dcm.pl';
    get_perms('dcm.pl');
}
else {
    $_SESSION['ona']['auth']['user']['username']=$_SERVER['PHP_AUTH_USER'];
    $status = get_perms($_SERVER['PHP_AUTH_USER']);
    if ($status == 1) {
        printmsg("ERROR => DCM: Unknown user {$_SERVER['PHP_AUTH_USER']}", 4);
        print "Unknown or unauthorized user: {$_SERVER['PHP_AUTH_USER']}\nSee -l and -p options within dcm.pl.\n";
        // clear the session
        // FIXME: should I do a sess_destroy or sess_close instead?  to clear crap from the DB
        unset($_SESSION['ona']['auth']);
        exit;
    }
}


// Display the current debug level if it's above 1
printmsg("DEBUG => debug level: {$conf['debug']}", 1);


/* ----------- RUN A MODULE IF NEEDED ------------ */
if (isset($_REQUEST['module'])) {
    // Run the module
    list($status, $output) = run_module($_REQUEST['module'], $_REQUEST['options']);
}


// Send the module status code and output to dcm.pl
print $status . "\r\n";
print $output;

// clear the session
// FIXME: should I do a sess_destroy or sess_close instead?  to clear crap from the DB
unset($_SESSION['ona']['auth']);

?>
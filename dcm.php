<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
require_once($conf['inc_functions']);
/* --------------------------------------------------------- */

// These store the output to be displayed
$status = 1;
$output = "ERROR => No module specified!\n";

// FIXME: Add IP Auth in Later
// Disconnect the user if their IP address isn't in our allowed list
// $remote_ip = ip_mangle($_SERVER['REMOTE_ADDR'], 'numeric');
// if (!in_array($remote_ip, $ips)) { print "1\r\nPermission denied!\n"; exit; }

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

?>
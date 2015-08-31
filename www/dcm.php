<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */

// MP: Since we know ONA will generate a ton of notice level errors, lets turn them off here
// I dont believe this will be impactful to anyone. keep an eye out for it however.
error_reporting (E_ALL ^ E_NOTICE);

// These store the output to be displayed
$status = 1;
$output = "ERROR => No module specified!\n";
$type='DCM';


// FIXME: Add IP Auth in Later --- or just use htaccess method
// Disconnect the user if their IP address isn't in our allowed list
// $remote_ip = ip_mangle($_SERVER['REMOTE_ADDR'], 'numeric');
// if (!in_array($remote_ip, $ips)) { print "1\r\nPermission denied!\n"; exit; }

printmsg("DEBUG => DCM_USER: {$_SERVER['PHP_AUTH_USER']}", 4);

// If no user name is passed in then use dcm.pl as the login name
// be careful as this currently does not require a password.
// FIXME: this needs to go away as it is a backdoor.  allow it to be configurable at least?
// Start out the session as a guest with level 0 access.  This is for view only mode.
// You can enable or disable this by setting the "disable_guest" sysconfig option
if ($_SERVER['PHP_AUTH_USER'] == '' and !$conf['disable_guest']) {
    $_SESSION['ona']['auth']['user']['username']='dcm.pl';
    // create new local authentication class directly
    $auth = load_auth_class('local');
    get_perms('dcm.pl');
    printmsg("INFO => [{$type}] {$_SESSION['ona']['auth']['user']['username']} has logged in",3);
}
else {
    // Set the cli user as the login user
    $DCMUSER=$_SESSION['ona']['auth']['user']['username']=$_SERVER['PHP_AUTH_USER'];

    printmsg("INFO => [{$type}] Attempting login as " . $DCMUSER, 4);

    list($status, $js) = get_authentication($DCMUSER,$_SERVER['PHP_AUTH_PW']);

    $errmsg = substr($js,27);

    if ($status==0) {
        $PERMSTAT = get_perms($DCMUSER);
        printmsg("INFO => [{$type}] {$_SESSION['ona']['auth']['user']['username']} has logged in",3);
    } else {
        printmsg("ERROR => DCM: Unknown user {$DCMUSER}", 4);
        print "ERROR => [{$DCMUSER}]: {$errmsg}\nSee -l and -p options within dcm.pl.\n";
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

// process various types of output formats
if (strstr($_REQUEST['options'], "format=json")) {
  output_formatter('json','json_encode');
} elseif (strstr($_REQUEST['options'], "format=yaml")) {
  output_formatter('yaml','yaml_emit');
} else {
  // Assume default text format
  // Send the module status code and output to dcm.pl
  print $status . "\r\n";
  print $output;
}



// clear the session
// FIXME: should I do a sess_destroy or sess_close instead?  to clear crap from the DB
unset($_SESSION['ona']['auth']);


// Given a format and format function, execute that output conversion on the $output array
function output_formatter($format='',$function='') {
  global $status, $output;
  // Check that the function exists
  if (!function_exists($function)) {
      print "998\r\n";
      print "ERROR => Format output type missing required php function: ${function}";
  } else {
    // Output needs to be an array
    if (is_array($output))  {
      $output['module_exit_status'] = $status;
      eval('print '.$function.'($output);');
    } else {
      $out['module_exit_status'] = $status;
      $out['module_exit_message'] = $output;
      eval('print '.$function.'($out);');
    }
  }
}


?>

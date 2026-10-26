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

// Log the user out and redirect them to the login page:

// Print a logout message
if(isset($_SESSION['ona']['auth']['user']['username'])){
        printmsg("INFO => [Desktop] {$_SESSION['ona']['auth']['user']['username']} has logged out",0);
}

// Unset session info relating to their account
if(isset($_SESSION['ona']['auth'])) {
  $_SESSION = array();
}

session_destroy();

// Print javascript to redirect them to https so they can login again
header("Location: index.php");
exit();

?>

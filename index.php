<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
require_once($conf['inc_functions']);
/* --------------------------------------------------------- */

// // Redirect them to HTTPS if they're not already logged in
// if (!loggedIn()) {
//     echo <<<EOL
// <html><body>
// Redirecting you to: <a href="{$https}{$baseURL}/login.php">{$https}{$baseURL}/login.php</a>
// <script type="text/javascript"><!--
//     setTimeout("window.location = \"{$https}{$baseURL}/login.php\";", 1000);
// --></script>
// </body></html>
// EOL;
//     exit;
// }

// Start out the session as a guest with level 0 access.  This is for view only mode.
$_SESSION['ona']['auth']['user']['username'] = "guest";
// FIXME: MP it may be best to not set level and allow the admin to define what the initial "guest" access will be via the auth system.
$_SESSION['ona']['auth']['user']['level'] = "0";

// Include xajax stuff (ajax calls will never make it past this line)
require_once($conf['inc_xajax_stuff']);

// Set the title of this web page here:
$conf['title'] .= "0wn Your Network";

// Include "Desktop" Framework
require_once($conf['html_desktop']);

?>
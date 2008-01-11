<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */

// Log the user out and redirect them to the login page:

// Unset session info relating to their account
$_SESSION['ona']['auth'] = array();

// Print javascript to redirect them to https so they can login again
echo <<<EOL
<html><body>
<script type="text/javascript"><!--
    window.location = "{$https}{$baseURL}/";
--></script>
</body></html>
EOL;

?>
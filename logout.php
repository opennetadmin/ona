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

// Log the user out and redirect them to the login page:

// Unset session info relating to their account
$_SESSION['auth'] = array();

// Print javascript to redirect them to https so they can login again
echo <<<EOL
<html><body>
<script type="text/javascript"><!--
    window.location = "{$https}/";
--></script>
</body></html>
EOL;

?>
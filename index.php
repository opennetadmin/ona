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

// Redirect them to HTTPS if they're not already logged in
/*
if (!$self['secure'] and !loggedIn()) {
    echo <<<EOL
<html><body>
<script type="text/javascript"><!--
    window.location = "{$https}/";
--></script>
</body></html>
EOL;
}
*/


// Set the title of this web page here:
$conf['title'] .= "Home";

// Include xajax stuff
require_once($conf['inc_xajax_stuff']);

// Include HTML Header
require_once($conf['html_header']);

echo "&nbsp;";

require_once($conf['html_footer']);
?>

<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
require_once($conf['inc_functions']);
/* --------------------------------------------------------- */

// Redirect them to HTTPS if they're not already logged in
if (!loggedIn()) {
    echo <<<EOL
<html><body>
Redirecting you to: <a href="{$https}{$baseURL}/login.php">{$https}{$baseURL}/login.php</a>
<script type="text/javascript"><!--
    setTimeout("window.location = \"{$https}{$baseURL}/login.php\";", 1000);
--></script>
</body></html>
EOL;
    exit;
}

// Set the title of this web page here:
$conf['title'] .= "Home";

// Include xajax stuff
require_once($conf['inc_xajax_stuff']);

// Include HTML Header
require_once($conf['html_header']);

echo "&nbsp;";

require_once($conf['html_footer']);
?>
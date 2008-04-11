<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
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

// This code is to provide a proper file attachment download for configuration archives

// If they have a session go for it
if ($_SESSION['ona']['auth']['user']['username']) {
  if (auth('host_config_admin',$debug_val)) {
    if ($_REQUEST['config_id'] and $_REQUEST['download']) {
        // Generate a SQL query to get the config to display

        list($status, $rows, $config) = ona_get_config_record(array('id' => $_REQUEST['config_id']));
        if (!$config['id']) {
            print "<html><body>Configuration record doesn't exist!</body></html>";
        }

        list($status, $rows, $host) = ona_get_host_record(array('id' => $config['host_id']));

        // Print the config file and exit
        $size = strlen($config['config_body']);
        header("Content-Disposition: attachment; filename=\"{$host['fqdn']}-{$config['config_type_name']}.txt\"; size=\"{$size}\"");
        print $config['config_body'];
        exit;
    }
  } else {
    print "<html><body>ERROR: You are unauthorized for this page!</body></html>";
  }
} else {
  print "<html><body>ERROR: You are unauthorized for this page!</body></html>";
}


?>
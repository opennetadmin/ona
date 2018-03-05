<?php

///////////////////////   WARNING   /////////////////////////////
//           This is the site configuration file.              //
//                                                             //
//      It is not intended that this file be edited.  Any      //
//      user configurations should be in the local config or   //
//      in the database table sys_config                       //
//                                                             //
/////////////////////////////////////////////////////////////////

// Used in PHP for include files and such
// Prefix.. each .php file should have already set $base and $include
// if it is written correctly.  We assume that is the case.
$base;
$include;

$onabase = dirname($base);


//$baseURL = preg_replace('+' . dirname($_SERVER['DOCUMENT_ROOT']) . '+', '', $base);
//$baseURL = preg_replace('+/$+', '', $baseURL);

// Used in URL links
$baseURL=dirname($_SERVER['SCRIPT_NAME']); $baseURL = rtrim($baseURL, '/');
$images = "{$baseURL}/images";

// help URL location
$_ENV['help_url'] = "http://opennetadmin.com/docs/";


// Get any query info
parse_str($_SERVER['QUERY_STRING']);



// Many of these settings serve as defaults.  They can be overridden by the settings in
// the table "sys_config"
$conf = array (
    /* General Setup */
    // Database Context
    // For possible values see the $ona_contexts() array  in the database_settings.inc.php file
    "default_context"        => 'DEFAULT',

    /* Used in header.php */
    "title"                  => 'OpenNetAdmin :: ',
    "meta_description"       => '',
    "meta_keywords"          => '',
    "html_headers"           => '',

    /* Include Files: HTML */
    "html_style_sheet"       => "$include/html_style_sheet.inc.php",
    "html_desktop"           => "$include/html_desktop.inc.php",
    "loading_icon"           => "<br><center><img src=\"{$images}/loading.gif\"></center><br>",

    /* Include Files: Functions */
    "inc_functions"          => "$include/functions_general.inc.php",
    "inc_functions_gui"      => "$include/functions_gui.inc.php",
    "inc_functions_db"       => "$include/functions_db.inc.php",
    "inc_functions_auth"     => "$include/functions_auth.inc.php",
    "inc_db_sessions"        => "$include/adodb_sessions.inc.php",
    "inc_adodb"              => "$include/adodb/adodb.inc.php",
    "inc_adodb_xml"          => "$include/adodb/adodb-xmlschema03.inc.php",
    "inc_xajax_stuff"        => "$include/xajax_setup.inc.php",
    "inc_diff"               => "$include/DifferenceEngine.php",

    /* Settings for dcm.pl */
    "dcm_module_dir"         => "$base/modules",
    "plugin_dir"             => "$base/local/plugins",

    /* Defaults for some user definable options normally in sys_config table */
    "debug"                  => "2",
    "syslog"                 => "0",
    "stdout"                 => "0",
    "log_to_db"              => "0",
    "logfile"                => "/var/log/ona.log",

    /* The output charset to be used in htmlentities() and htmlspecialchars() filtering */
    "charset"                => "utf8",
    "php_charset"            => "UTF-8",

    // enable the setting of the database character set using the "set name 'charset'" SQL command
    // This should work for mysql and postgres but may not work for Oracle.
    // it will be set to the value in 'charset' above.
    "set_db_charset"         => TRUE,
);


// Read in the version file to our conf variable
// It must have a v<majornum>.<minornum>, no number padding, to match the check version code.
if (file_exists($base.'/../VERSION')) { $conf['version'] = trim(file_get_contents($base.'/../VERSION')); }

// The $self array is used to store globally available temporary data.
// Think of it as a cache or an easy way to pass data around ;)
// I've tried to define the entries that are commonly used:
$self = array (
    // Error messages will often get stored in here
    "error"                  => "",

    // All sorts of things get cached in here to speed things up
    "cache"                  => array(),

    // Get's automatically set to 1 if we're using HTTPS/SSL
    "secure"                 => 0,
);
// If the server port is 443 then this is a secure page
// This is basically used to put a padlock icon on secure pages.
if ($_SERVER['SERVER_PORT'] == 443) { $self['secure'] = 1; }




///////////////////////////////////////////////////////////////////////////////
//                            STYLE SHEET STUFF                              //
///////////////////////////////////////////////////////////////////////////////


// Colors
$color['bg']                   = '#FFFFFF';
$color['content_bg']           = '#FFFFFF';
$color['bar_bg']               = '#D3DBFF';
$color['border']               = '#555555'; //#1A1A1A
$color['form_bg']              = '#FFEFB6';

$color['font_default']         = '#000000';
$color['font_title']           = '#4E4E4E';
$color['font_subtitle']        = '#5A5A5A';
$color['font_error']           = '#E35D5D';

$color['link']                 = '#6B7DD1';
$color['vlink']                = '#6B7DD1';
$color['alink']                = '#6B7DD1';
$color['link_nav']             = '#0048FF';  // was '#7E8CD7';
$color['link_act']             = '#FF8000';  // was '#EB8F1F';
$color['link_domain']          = 'green';    // was '#5BA65B';

$color['button_normal']        = '#FFFFFF';
$color['button_hover']         = '#E0E0E0';

// Define some colors for the subnet map:
$color['bgcolor_map_host']     = '#BFD2FF';
$color['bgcolor_map_subnet']   = '#CCBFFF';
$color['bgcolor_map_selected'] = '#FBFFB6';
$color['bgcolor_map_empty']    = '#FFFFFF';

// Much of this configuration is required here since
// a lot of it's used in xajax calls before a web page is created.
$color['menu_bar_bg']          = '#F3F1FF';
$color['menu_header_bg']       = '#FFFFFF';
$color['menu_item_bg']         = '#F3F1FF';
$color['menu_header_text']     = '#436976';
$color['menu_item_text']       = '#436976';
$color['menu_item_selected_bg']= '#B1C6E3';
$color['menu_header_bg']       = '#B1C6E3';


// Style variables (used in PHP in various places)
$style['font-family'] = "Arial, Sans-Serif";
$style['borderT'] = "border-top: 1px solid {$color['border']};";
$style['borderB'] = "border-bottom: 1px solid {$color['border']};";
$style['borderL'] = "border-left: 1px solid {$color['border']};";
$style['borderR'] = "border-right: 1px solid {$color['border']};";

// Include the localized configuration settings
// MP: this may not be needed now that "user" configs are in the database
@include("{$base}/local/config/config.inc.php");

// Include the basic system functions
// any $conf settings used in this "require" should not be user adjusted in the sys_config table
require_once($conf['inc_functions']);

// Include the basic database functions
require_once($conf['inc_functions_db']);

// Include the localized Database settings
$dbconffile = "{$base}/local/config/database_settings.inc.php";
if (file_exists($dbconffile)) {
    if (substr(exec("php -l $dbconffile"), 0, 28) == "No syntax errors detected in") {
        @include($dbconffile);
    } else {
        echo "Syntax error in your DB config file: {$dbconffile}<br>Please check that it contains a valid PHP formatted array, or check that you have the php cli tools installed.<br>You can perform this check maually using the command 'php -l {$dbconffile}'.";
        exit;
    }
} else {
    require_once($base.'/../install/install.php');
    exit;
}

// Check to see if the run_install file exists.
// If it does, run the install process.
if (file_exists($base.'/local/config/run_install') or @$runinstaller or @$install_submit == 'Y') {
    // Process the install script
    require_once($base.'/../install/install.php');
    exit;
}

// Set multibyte encoding to UTF-8
if (@function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
} else {
    printmsg("INFO => Missing 'mb_internal_encoding' function. Please install PHP 'mbstring' functions for proper UTF-8 encoding.", 0);
}

// If we dont have a ona_context set in the cookie, lets set a cookie with the default context
if (!isset($_COOKIE['ona_context_name'])) { $_COOKIE['ona_context_name'] = $conf['default_context']; setcookie("ona_context_name", $conf['default_context']); }

// (Re)Connect to the DB now.
global $onadb;
$onadb = db_pconnect('', $_COOKIE['ona_context_name']);

// Load the actual user config from the database table sys_config
// These will override any of the defaults set above
list($status, $rows, $records) = db_get_records($onadb, 'sys_config', 'name like "%"', 'name');
foreach ($records as $record) {
    printmsg("INFO => Loaded config item from database: {$record['name']}=''{$record['value']}''",5);
    $conf[$record['name']] = $record['value'];
}

// Include functions that replace the default session handler with one that uses MySQL as a backend
require_once($conf['inc_db_sessions']);

// Include the GUI functions
require_once($conf['inc_functions_gui']);

// Include the AUTH functions
require_once($conf['inc_functions_auth']);

// Start the session handler (this calls a function defined in functions_general)
startSession();

// Include internationalization code (should be top of any .php file with strings to be translated).
include $base . '/config/internationalization.inc.php';

// Set session inactivity threshold
ini_set("session.gc_maxlifetime", $conf['cookie_life']);

// if search_results_per_page is in the session, set the $conf variable to it.  this fixes the /rows command
if (isset($_SESSION['search_results_per_page'])) $conf['search_results_per_page'] = $_SESSION['search_results_per_page'];

// Set up our page to https if requested for our URL links
if (@($conf['force_https'] == 1) or ($_SERVER['SERVER_PORT'] == 443)) {
    $https  = "https://{$_SERVER['SERVER_NAME']}";
}
else {
    if ($_SERVER['SERVER_PORT'] != 80) {
      $https  = "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
    } else {
      $https  = "http://{$_SERVER['SERVER_NAME']}";
    }
}

// DON'T put whitespace at the beginning or end of included files!!!
?>

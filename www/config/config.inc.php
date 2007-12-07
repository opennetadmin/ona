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

// Used in URL links
$baseURL=dirname($_SERVER['SCRIPT_NAME']); $baseURL = rtrim($baseURL, '/');
$images = "{$baseURL}/images";

// help URL location
$_ENV['help_url'] = "http://www.opennetadmin.com/docs/";

// Many of these settings serve as defaults.  They can be overridden by the settings in
// the table "sys_config"
$conf = array (
    /* General Setup */
    // It must have a v<majornum>.<minornum>, no number padding to match the check version code.
    "version"                => "v1.0",

    /* Logging - Used by the printmsg() function */
    /////////////// This stuff is replicated in the sys_config table //////////////////////////////
    "debug"                  => 0,
    "stdout"                 => 0, // Print logs to the generated web page, not a good idea!
    "db"                     => 1, // Log to a sql log, highly recommended
    "logfile"                => "/var/log/ona.log",
    "syslog"                 => 0, // It only syslogs if debug is 0.
    /* Other Random Things */
    "money_format"           => '%01.2f',
    "date_format"            => 'M jS, g:ia',
    "search_results_per_page"=> 10,
    "suggest_max_results"    => 10,
    /* Session Settings */
    "cookie_life"            => (60*60*24*2), // 2 days, in seconds
    ///////// End sys_config replicaiton stuff ///////////////////

    // Database Context
    // For possible values see the $db_context() array and description below
    "mysql_context"          => 'default',

    /* Used in header.php */
    "title"                  => 'Open Net Admin :: ',
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
    "inc_xajax_stuff"        => "$include/xajax_setup.inc.php",
    "inc_diff"               => "$include/DifferenceEngine.php",

    /* Settings for dcm.pl */
    "dcm_module_dir"         => "$base/modules",
);



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


$conf['dns']['admin_email']     = 'hostmaster'; // per RFC 2412, defaults to hostmaster within the domain origin
$conf['dns']['primary_master']  = '';           // This should be the fqdn of your default primary master server
$conf['dns']['default_ttl']     = '86400';      // this is the value of $TTL for the zone, used as the default value
$conf['dns']['refresh']         = '86400';
$conf['dns']['retry']           = '3600';
$conf['dns']['expiry']          = '3600';
$conf['dns']['minimum']         = '3600';       // used as the negative caching value per RFC 2308
$conf['dns']['parent']          = '';
$conf['dns']['defaultdomain']   = 'yourdomain.com';





// Include the localized Database settings
@include("{$base}/local/config/database_settings.inc.php");

// Include the localized configuration settings
// MP: this may not be needed now that "user" configs are in the database
@include("{$base}/local/config/config.inc.php");

// Include the basic system functions
// any $conf settings used in this "require" should not be user adjusted in the sys_config table
require_once($conf['inc_functions']);

// Now that some initial configuration has been set up.  Get the user configuration from the database

// Include the basic database functions
require_once($conf['inc_functions_db']);

// (Re)Connect to the DB now.
global $onadb;
$onadb = db_pconnect('mysqlt', $conf['mysql_context']);

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

// Start the session handler (this calls a function defined in functions_general)
startSession();

// Include the AUTH functions
require_once($conf['inc_functions_auth']);


// Set session inactivity threshold
ini_set("session.gc_maxlifetime", $conf['cookie_life']);


// DON'T put whitespace at the beginning or end of included files!!!
?>
<?php

/////////////////////////////////////////////////////////////////
//           This is the site configuration file.              //
/////////////////////////////////////////////////////////////////

// Used in PHP for include files and such
// Prefix.. each .php file should have already set $base and $include
// if it is written correctly.  We assume that is the case.
$base;
$include;

// Used in URL links
$http   = "http://{$_SERVER['HTTP_HOST']}";
$https  = "https://{$_SERVER['HTTP_HOST']}";
$baseURL = preg_replace('+' . $_SERVER['DOCUMENT_ROOT'] . '+', '', $base);
$baseURL = preg_replace('+/$+', '', $baseURL);
$images = "{$baseURL}/images";

// help URL location
$_ENV['help_url'] = "http://opennetadmin.com/dokuwiki/doku.php?id=documentation:";

$conf = array (
        /* General Setup */
        "version"              => "v1.00",

        /* Logging - Used by the printmsg() function */
        "debug"                => 5,
        "stdout"               => 0,
        "logfile"              => "/var/log/website.dev",
        "syslog"               => 1, // It only syslogs if debug is 0.

        // The mode of this website:
        //     0 == Production
        //     1 == Development
        //   This changes the behavior of several things, including
        //   which database instances are used in SQL queries, style-
        //   sheet being used, etc.
        //   THIS VALUE MAY BE OVERRIDDEN BELOW IN THIS FILE
        "dev_mode"             => 0,

        // Database Context
        // For possible values see the $db_context() array and description below
        "mysql_context"        => 'default',

        /* Other Random Things */
        "contact_phone"        => '888-888-8888',
        "contact_email"        => 'support@opennetadmin.com',
        "money_format"         => '%01.2f',
        "date_format"          => 'M jS, g:ia',
        "search_results_per_page" => 10,
        "suggest_max_results"  => 10,

        /* Used in header.php */
        /* Recommendation: not more than 60 (40?) chars */
        "title"                => 'Open Network Admin :: ',
        /* Recommendation: not more than 150 chars */
        "meta_description"     => '',
        /* Recommendation: not more than 1024 chars, no keywords more than twice */
        "meta_keywords"        => '',
        "html_headers"         => '',

        /* Session Settings */
        "cookie_host"          => $_SERVER['HTTP_HOST'],
        "cookie_life"          => (60*60*24*2),

        /* Include Files: HTML */
        "html_header"          => "$include/html_header.php",
        "html_footer"          => "$include/html_footer.php",
        "html_style_sheet"     => "$include/html_style_sheet.php",
        "loading_icon"         => "<br><center><img src=\"{$images}/loading.gif\"></center><br>",

        /* Include Files: Functions */
        "inc_functions"        => "$include/inc_functions_general.php",
        "inc_functions_ona"    => "$include/inc_functions_ona.php",
        "inc_functions_db"     => "$include/inc_functions_db.php",
        "inc_cc"               => "$include/inc_functions_cc.php",
        "inc_db"               => "$include/inc_db.php",
        "inc_mysqlsessions"    => "$include/session_mysql.php",
        "inc_adodb"            => "$include/adodb/adodb.inc.php",
        "inc_xajax_stuff"      => "$include/xajax_setup.inc.php",
        "inc_diff"             => "$include/DifferenceEngine.php",

        /* Settings for dcm.pl */
        "dcm_module_dir"       => "$include/../modules",
);


// Set the site to development mode if the server name starts with "dev."
if (preg_match('/^dev\./i', $_SERVER['HTTP_HOST'])) {
    $conf["dev_mode"] = 1;
}


// The $self array is used to store globally available temporary data.
// Think of it as a cache or an easy way to pass data around ;)
// I've tried to define the entries that are commonly used:
$self = array (
        // Error messages will often get stored in here
        "error"                => "",

        // All sorts of things get cached in here to speed things up
        "cache"                => array(),

        // Get's automatically set to 1 if we're using HTTPS/SSL
        "secure"               => 0,
);
// If the server port is 443 then this is a secure page
// This is basically used to put a padlock icon on secure pages.
if ($_SERVER['SERVER_PORT'] == 443) { $self['secure'] = 1; }



///////////////////////////////////////////////////////////////////////////////
//                            STYLE SHEET STUFF                              //
///////////////////////////////////////////////////////////////////////////////


// Colors
$color['bg']         = '#FFFFFF';
$color['content_bg'] = '#FFFFFF';
$color['bar_bg']     = '#FFFFFF';
// $color['border']     = '#896465';
$color['border']     = '#1A1A1A';
$color['form_bg']    = '#FFEFB6';

$color['font_default']   = '#3E3E3E';
$color['font_title']     = '#4E4E4E';
$color['font_subtitle']  = '#5A5A5A';
$color['font_error']     = '#E35D5D';

$color['link']   = '#6B7DD1';
$color['vlink']  = '#6B7DD1';
$color['alink']  = '#6B7DD1';

$color['link_nav']         = '#0048FF';   // was '#7E8CD7';
$color['link_act']         = '#FF8000'; // was '#EB8F1F';
$color['link_zone']        = 'green';  // was '#5BA65B';

$color['button_normal'] = '#FFFFFF';
$color['button_hover'] = '#E0E0E0';

// Style variables (used in PHP in various places)
$style['font-family'] = "Arial, Sans-Serif";
$style['borderT'] = "border-top: 1px solid {$color['border']};";
$style['borderB'] = "border-bottom: 1px solid {$color['border']};";
$style['borderL'] = "border-left: 1px solid {$color['border']};";
$style['borderR'] = "border-right: 1px solid {$color['border']};";


// Define database context names
//   The concept here is that we need multiple copies of the entire IP
//   database to support different "contexts".  For example - the current
//   database scheme doesn't allow a single server to have both an
//   internal and external DNS name .. or an IP address in two different
//   MPLS networks.  By having multiple IP Database instances we can leave
//   all the code the same and simply switch the "context", or database,
//   we are using.
//
//   I use this array as a place to store the MySQL database info as well,
//   to keep all database connection info in one place.
//
//   Note: after adding a new context here you also need to add the details
//         for that new context in inc_db.php as well.
//   Note: the context used is determined by the value of $conf['mysql_context']
//         at the time inc_db.php is included.
//   Note: Available ADODB types:
//         mysql, oracle, oci8, mssql, postgres, sybase, vfp, access, ibase and many others.
$db_context = array (

    // Note:  I set the login up as ona-sys so that we could have
    // a more "functional" user to do connections with that is not root or
    // some sort of full admin.  I do have an ona-admin that has full "root"
    // like privs
    //
    // Type:
    'mysql' => array(
        // Name:
        'default' => array(
            'description' => 'Website metadata',
            'production' => array(
                'db_type'     => 'mysql',
                'db_host'     => '127.0.0.1',
                'db_login'    => 'ona-sys',
                'db_passwd'   => 'onar0x',
                'db_database' => 'ona',
                'db_debug'    => false,
            ),
            'development' => array(
                'db_type'     => 'mysql',
                'db_host'     => '127.0.0.1',
                'db_login'    => 'ona-sys',
                'db_passwd'   => 'onar0x',
                'db_database' => 'ona',
                'db_debug'    => false,
            ),
        ),
    ),

);



// Set session inactivity threshold
ini_set("session.gc_maxlifetime", $conf['cookie_life']);


// DON'T put whitespace at the beginning or end of included files!!!
?>
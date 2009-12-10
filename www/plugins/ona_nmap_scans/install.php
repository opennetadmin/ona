<?php

// Get some standard global variables
global $base, $conf, $self, $onadb;

// Gather various bits of information about the plugin
$onainstalldir = dirname($base);
$plugindir = str_replace($onainstalldir.'/www', '', dirname(__FILE__));
$installfile = __FILE__;
$installdir = dirname(__FILE__);
$stat = 0;

// Check permissions
if (!auth('advanced')) {
    $window['js'] = "alert('Permission denied!'); removeElement('{$window_name}');";
    return;
}


//----------------------------Change these values for your plugin---------------------------

// Define this plugins name, must be same as the directory it will live in
$plugin_name = 'ona_nmap_scans';

// Set a title
$window['title'] = "ONA Nmap Scans Install";

// Add any DCM module names related to this plugin
// each new module requires a description and a file path name
// the dcm module name is the first field in the array
//
// EXAMPLE
// $pmodules['rack_del']['desc'] = 'Delete a rack';
// $pmodules['rack_del']['file'] = "..{$plugindir}/{$plugin_name}.inc.php";
//
// If you do not specify a file entry, it will default to the path listed in the example
//
$pmodules = array();
//$pmodules['puppet_fact_add']['desc'] = 'Add puppet facts to a host';
//$pmodules['puppet_fact_add']['file'] = "..{$plugindir}/{$plugin_name}.inc.php";

//------------------------------------------------------------------------------------------




// Provide basic javascript for the new popup window
$window['js'] .= <<<EOL
    /* Put a minimize icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

    /* Put a help icon in the title bar */
    el('{$window_name}_title_r').innerHTML =
        '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;

EOL;

$window['html'] .= "<div style='max-height: 500px;max-width:750;overflow: auto;padding: 5px;'>";


if (!is_writable($conf['plugin_dir'])) {
    $window['html'] .= "<br><img src='{$images}/silk/error.png' border='0'><font color=\"red\"> ERROR=> The plugin directory '{$conf['plugin_dir']}' is not writable by the web server!</font><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;You might execute the command: <font color='orange'>chown -R {$_ENV['APACHE_RUN_USER']} {$conf['plugin_dir']}</font><br>";
    $stat++;
}

// If we have defined modules, process them
if (count($pmodules) > 0 ) {
    $window['html'] .= <<<EOL
<br><b>Installing new DCM modules:</b><br>
EOL;



    // Get list of existing DCM modules to see if they are already installed, Use cache if possible
    if (!is_array($self['cache']['modules']) or !array_key_exists('get_module_list', $self['cache']['modules'])) {
        require_once($conf['dcm_module_dir'] . '/get_module_list.inc.php');
        list($status, $self['cache']['modules']) = get_module_list('type=array');
    }

    // If the new module does not already exist, add it
    foreach ($pmodules as $modname => $attributes) {
        if (!array_key_exists($modname,$self['cache']['modules'])) {
            // default the file location if it is not set to use the main lugin file
            if (!$attributes['file']) $attributes['file'] = "..{$plugindir}/{$plugin_name}.inc.php";
            list($status, $output) = run_module('add_module', array('name' => $modname, 'desc' => $attributes['desc'], 'file' => $attributes['file']));
            if ($status) {
                $stat++;
                $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> {$modname} failed to install.<br>";
            } else {
                printmsg("DEBUG => Plugin install for {$plugin_name} created new DCM module {$modname}.",2);
                $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$modname}<br>";
            }
        } else {
            $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$modname}, already installed.<br>";
        }
    }
}

// If there is a SQL file to process. lets do that
$sqlfile = dirname(__FILE__)."/{$plugin_name}.sql";
if (file_exists($sqlfile)) {

    $sqlcontent = file_get_contents($sqlfile);
    $statements = preg_split("/;/", $sqlcontent);
//print_r($statement);

    $has_trans = $onadb->BeginTrans();
    if (!$has_trans) printmsg("WARNING => Transactions support not available on this database, this can cause problems!", 1);

    // If begintrans worked and we support transactions, do the smarter "starttrans" function
    if ($has_trans) {
        printmsg("DEBUG => Starting transaction", 2);
        $onadb->StartTrans();
    }


    // Run the SQL
    printmsg("DEBUG => Installing {$modname} plugin SQL statements.", 4);
    $i = 0;
    while ($i < count($statements)-1) {

        // The SQL statements are split above based on a ; character.
        // This may not always work but should cover most things, just be aware.
        //$window['html'] .= $statements[$i].'---<br><br>';
        $ok = $onadb->Execute($statements[$i].';');
        $error = $onadb->ErrorMsg();

        if ($ok === false or $error) {
            if ($has_trans) {
                printmsg("INFO => There was a module error, marking transaction for a Rollback!", 1);
                $onadb->FailTrans();
            }
            break;
        }
        $i++;
    }

    // Report any errors
    if ($ok === false or $error) {
        $window['html'] .= <<<EOL
        <br><b>Installing database updates:</b><br>
        <img src='{$images}/silk/error.png' border='0'> <font color="red">ERROR => SQL statements failed:</font><br><pre>{$error}</pre>
        <br><img src='{$images}/silk/error.png' border='0'> Unable to automatically process SQL statements<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color="orange">Please try again, or add the following SQL statements manually:</font>
        <pre>
        {$sqlcontent}
        </pre>
        <br>
        <font color="orange">Possibly use the following command:<br>
        mysql -u {$self['db_login']} -p{$self['db_passwd']} {$self['db_database']} < {$sqlfile}</font><br><br>
EOL;
        $stat++;
    } else {
        $window['html'] .= <<<EOL
        <br><b>Installing database updates:</b><br>
        &nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> All SQL updates were successful.<br>
EOL;
        if ($has_trans) { $onadb->CompleteTrans(); }
    }

}

// Lets check for files that should be installed.
$window['html'] .= "<br><b>Checking required files:</b><br>";

$reqfile = $onainstalldir.'/www/local/nmap_scans';
if (is_dir($reqfile)) {
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$reqfile}<br>";
} else {
    // make the dir
    if (mkdir($reqfile)) {
        $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$reqfile}<br>";
        mkdir($reqfile.'/subnets');
    } else {
        $stat++;
        $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> <font color=\"red\">ERROR=> Unable to make required directory, try the following:</font><br>";
        // Provide some information to the user on how to fix the problem manually
        $window['html'] .= "<font color=\"orange\">Execute shell command:<br>mkdir {$onainstalldir}/www/local/nmap_scans</font><br><br>";
    }
}

$reqfile = $onainstalldir.'/bin/nmap_scan_cron';
if (file_exists($reqfile)) {
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$reqfile}<br>";
} else {
    $stat++;
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> <font color=\"red\">ERROR=> Unable to find required file, try the following:</font><br>";
    // Provide some information to the user on how to fix the problem manually
    $window['html'] .= "<font color=\"orange\">Execute shell command:<br>ln -s {$installdir}/nmap_scan_cron {$reqfile}</font><br><br>";
}

$reqfile = '/usr/bin/nmap';
if (file_exists($reqfile)) {
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'> {$reqfile}<br>";
} else {
    //$stat++;
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> <font color=\"red\">ERROR=> Unable to find required file, try the following:</font><br>";
    // Provide some information to the user on how to fix the problem manually
    $window['html'] .= "<font color=\"orange\">Install nmap package or adjust {$installdir}/nmap_scan_cron to point to proper nmap locaiton</font><br><br>";
}

$window['html'] .= "<br><b>Disabling install script:</b><br>";
// If there were no errors, move this install file out of the way.
if (!$stat) {
    $window['html'] .= @rename(__FILE__, __FILE__.'.completed') ? "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/accept.png' border='0'>Moved install files.<br><br><center><b>Install complete.</b><br><a onclick=\"removeElement('{$window_name}');\">CLOSE WINDOW</a></center>" : "<br>&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> <font color=\"red\">ERROR=> Unable to rename install file, do it manually then close this window:</font><br><br><font color=\"orange\">mv {$installfile} {$installfile}.completed</font><br><br>";
} else {
    $window['html'] .= "&nbsp;&nbsp;&nbsp;&nbsp;<img src='{$images}/silk/error.png' border='0'> Not disabling install script due to previous errors.<br><br><center><a onclick=\"removeElement('{$window_name}');toggle_window('{$window_name}');\">Fix the errors and then click to TRY AGAIN</a></center>";

}

$window['html'] .= "<br><br><center><font color='green'>END OF INSTALL</font></center></div>";

?>

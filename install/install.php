<?php

$base = dirname(__FILE__);
$baseURL=dirname($_SERVER['SCRIPT_NAME']); $baseURL = rtrim($baseURL, '/');

// stuff and notes:
//  maybe change the mysqlt type to a variable..

// Init and setup some variables.
$DBname = $database_name;
$DBPrefix = '';
$text = '';
$status=0;
$installdir = dirname($base);
$runinstall = $installdir.'/www/local/config/run_install';
$sqlfile = $base.'/ona-tables.sql';
$sqlfile_data = $base.'/ona-data.sql';
$dbconffile = $installdir.'/www/local/config/database_settings.inc.php';
$license_text = file_get_contents($base.'/../docs/LICENSE');
$new_ver = trim(file_get_contents($installdir.'/VERSION'));


// Get some pre-requisite information
$version = phpversion() > '4.1' ? 'Yes' : '<font color="red">No</font>';
$hasmysql = function_exists( 'mysql_connect' ) ? 'Yes' : '<font color="red">No</font>';
$hasgmp = function_exists( 'gmp_init' ) ? 'Yes' : 'Recommended';
$dbconfwrite = @is_writable($installdir.'/www/local/config/') ? 'Yes' : '<font color="red">No</font>';

$blankmain = "<script>el('main').style.display = 'none';</script>";

// This is the div that contains the license
$licensediv = <<<EOL
            <div id="license">
                <center><b>OpenNetAdmin is released under the following license:</b></center>
                <textarea class="edit" rows="25" cols="75">{$license_text}</textarea><br><br>
                <center>
                    <input class='edit' type="button" value="I Agree!" onclick="el('work').style.display = '';el('input1').focus();el('license').style.display = 'none';" />&nbsp;&nbsp;
                    <a style="text-decoration: none;" href="/"><input class='edit' type="button" value="I don't like free stuff?" onclick="" /></a>
                </center>
            </div>
EOL;

// Div with the prerequisite checks
$requisitediv = <<<EOL
            <div id="checksdiv">
                <table id="checks">
                    <tr><th colspan="5">Prerequisite checks</th></tr>
                    <tr><td>PHP version > 4.1:</td><td>{$version}</td></tr>
                    <tr><td>Has MySQL support:</td><td>{$hasmysql}</td></tr>
                    <tr title="The PHP GMP modules provide extra functionality, but are not required."><td>Has GMP support:</td><td>{$hasgmp}</td></tr>
                    <tr title="The local config directory must be writable by the web server."><td>{$installdir}/www/local/config dir writable:</td><td>{$dbconfwrite}</td></tr>
                </table>
            </div>
EOL;

// Initial text for the greeting div
$greet_txt = "It looks as though this is your first time running OpenNetAdmin. Please answer a few questions and we'll initialize the system for you. We've pre-populated some of the fields with suggested values.  If the database you specify below already exists, it will be overwritten.";


$upgrademain = '';

if (@file_exists($dbconffile)) {
    // Get the existing database config so we can connect using its settings
    include($dbconffile);
    // Connect to the database as the administrator user
    $con = mysql_connect($db_context['mysqlt']['default']['primary']['db_host'],$db_context['mysqlt']['default']['primary']['db_login'],$db_context['mysqlt']['default']['primary']['db_passwd']);
    if (!$con) {
        $status++;
        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to connect to '{$db_context['mysqlt']['default']['primary']['db_host']}' as '{$db_context['mysqlt']['default']['primary']['db_login']}'.<br>";
    } else {
        if (mysql_select_db($db_context['mysqlt']['default']['primary']['db_database'], $con)) {
            $result = mysql_query("SELECT value FROM sys_config WHERE name like 'version';",$con);
            $curr_ver = @mysql_result($result, 0);

            if ($curr_ver == '') { $curr_ver = 'PRE-v08.02.18'; }

            // Update the greet text with new info
            $greet_txt = "It looks as though you already have version '{$curr_ver}' of OpenNetAdmin installed.  You should make a backup of the data before proceeding with the upgrade.<br><br>We will be upgrading to the version '{$new_ver}'.";

            $upgrademain = <<<EOL
            <div id="upgrademain">
                <form id="upgradeform">
                    <input type='hidden' name='install_submit' value='Y' />
                    <input id='upgrade' type='hidden' name='upgrade' value='N' />
                    <a style="text-decoration: none;" href="/"><input class='edit' type="button" value="Cancel upgrade" onclick="" /></a>
                    <input class='edit' type='button' name='upgrade' value='Perform the upgrade.' onclick="el('upgrade').value='Y';el('upgradeform').submit();" />
                </form>
            </div>
EOL;
        }
    }
    // Close the database connection
    @mysql_close($con);
}

$main = <<<EOL
            <div id="main" style="{$mainstyle}">
                <form id="mainform">
                    <input type='hidden' name='install_submit' value='Y' />
                    <input id='overwrite' type='hidden' name='overwrite' value='N' />
                    <input id='keep' type='hidden' name='keep' value='N' />
                    <table>
                        <tr onmouseover="el('help').innerHTML = input1text;">
                            <td>Database Host:</td><td><input id='input1' class='edit' type='text' name='database_host' value='localhost' onfocus="el('help').innerHTML = input1text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input2text;">
                            <td>Database Admin:</td><td><input class='edit' type='text' name='admin_login' value='root' onfocus="el('help').innerHTML = input2text;" /></td></tr>
                        <tr onmouseover="el('help').innerHTML = input3text;">
                            <td>Database Admin Password:</td><td><input class='edit' type='password' name='admin_passwd' value='{$admin_passwd}' onfocus="el('help').innerHTML = input3text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input4text;">
                            <td>Database Name:</td><td><input class='edit' type='text' name='database_name' value='ona' onfocus="el('help').innerHTML = input4text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input5text;">
                            <td>System User Name:</td><td><input class='edit' type='text' name='sys_login' value='ona_sys' onfocus="el('help').innerHTML = input5text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input6text;">
                            <td>System User Password:</td><td><input class='edit' type='password' name='sys_passwd' value='{$sys_passwd}' onfocus="el('help').innerHTML = input6text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input7text;">
                            <td>Default Domain Name:</td><td><input class='edit' type='text' name='default_domain' value='example.com' onfocus="el('help').innerHTML = input7text;"/></td></tr>
                        <tr><td colspan=2 style="text-align: center;"><br><input class='edit' type='submit' value='Create my database!' /></td></tr>
                    </table>
                </form>
                <script type="text/javascript" language="javascript">

                    var input1text = '<b>Database Host:</b> The hostname or IP address of the database server that will house your database.';
                    var input2text = '<b>Database Admin:</b> The username for the database administrator. This account will be used to create the new database and must have proper privledges to do so.';
                    var input3text = '<b>Database Admin Password:</b> The password for the database administrator account.';
                    var input4text = '<b>Database Name:</b> The name of the database that will store the OpenNetAdmin tables.  We suggest "ona"';
                    var input5text = '<b>System User Name:</b> The application username used to access the database.  We suggest "ona_sys"';
                    var input6text = '<b>System User Password:</b> The password for the application user.';
                    var input7text = '<b>Default Domain Name:</b> The default DNS domain for your site.  This will be your primary domain to add hosts to and will serve the default domain for certain tasks.';

                </script>
            </div>
EOL;






$dbconfig_contents = <<<EOL
<?php
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
//         for that new context in functions_db.php as well.
//   Note: the context used is determined by the value of \$conf['mysql_context']
//         at the time functions_db.php is included.
//   Note: Available ADODB types:
//         mysql, mysqlt, oracle, oci8, mssql, postgres, sybase, vfp, access, ibase and many others.
\$db_context = array (


    // Type: -- The type should be a transactional database to support proper data rollbacks.
    'mysqlt' => array(
        // Name:
        'default' => array(
            'description' => 'Website metadata',
            'primary' => array(
                'db_type'     => 'mysqlt',  // using mysqlt for transaction support
                'db_host'     => '{$database_host}',
                'db_login'    => '{$sys_login}',
                'db_passwd'   => '{$sys_passwd}',
                'db_database' => '{$database_name}',
                'db_debug'    => false,
            ),
            // You can use this to connect to a secondary server that is
            // syncronized on the back end.
            'secondary' => array(
                'db_type'     => 'mysqlt',
                'db_host'     => '{$database_host}',
                'db_login'    => '{$sys_login}',
                'db_passwd'   => '{$sys_passwd}',
                'db_database' => '{$database_name}',
                'db_debug'    => false,
            ),
        ),
    ),
);
?>
EOL;





// If they have selected to keep the tables then remove the run_install file
if ($install_submit == 'Y' && $upgrade == 'Y') {
    // Connect to the database as the administrator user
    $con = mysql_connect($db_context['mysqlt']['default']['primary']['db_host'],$db_context['mysqlt']['default']['primary']['db_login'],$db_context['mysqlt']['default']['primary']['db_passwd']);
    if (!$con) {
        $status++;
        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to connect to '{$db_context['mysqlt']['default']['primary']['db_host']}' as '{$db_context['mysqlt']['default']['primary']['db_login']}'.<br>";
    } else {
        mysql_select_db($db_context['mysqlt']['default']['primary']['db_database'], $con);

        $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Keeping your original data.<br>";

        // Get the current upgrade index if there is one.
        if (mysql_select_db($db_context['mysqlt']['default']['primary']['db_database'], $con)) {
            $result = mysql_query("SELECT value FROM sys_config WHERE name like 'upgrade_index';",$con);
            $upgrade_index = @mysql_result($result, 0);
        }

        if ($upgrade_index == '') {
            $text .= "<img src=\"{$images}/silk/error.png\" border=\"0\" /> Auto upgrades not yet supported. Please see docs/UPGRADES<br>";
        } else {
            // loop until we have processed all the upgrades
            while(1 > 0) {
                // Find out what the next index will be
                $new_index = $upgrade_index + 1;
                // Determine file name
                $upgrade_sqlfile = "{$base}/{$upgrade_index}-to-{$new_index}.sql";
                // Check that the upgrade file exists
                if (file_exists($upgrade_sqlfile)) {
                    populate_db($db_context['mysqlt']['default']['primary']['db_database'],$DBPrefix,$upgrade_sqlfile);
                    $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Upgraded from index {$upgrade_index} to {$new_index}.<br>";
                    // Update the upgrade_index element in the sys_config table
                    if(mysql_query("UPDATE sys_config SET value='{$new_index}' WHERE name like 'upgrade_index';",$con)) {
                        $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Updated database upgrade_index variable to '{$new_index}'.<br>";
                    }
                    else {
                        $status++;
                        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to update upgrade_index variable in table 'sys_config'.<br>";
                    }
                    $upgrade_index++;
                } else {
                    break;
                }
            }

        }

        // Update the version element in the sys_config table
        if(mysql_query("UPDATE sys_config SET value='{$new_ver}' WHERE name like 'version';",$con)) {
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Updated database version variable to '{$new_ver}'.<br>";
        }
        else {
            $status++;
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to update version info in table 'sys_config'.<br>";
        }

        $text .= "You can now <a href='{$baseURL}'>CLICK HERE</a> to start using OpenNetAdmin! Enjoy!";

        if (!@unlink($runinstall)) {
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to delete the file '{$runinstall}'.<br>";
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Please remove '{$runinstall}' manually.<br>";
        }
    }
    // Close the database connection
    @mysql_close($con);
}






// If the initialize button was clicked, lets go for it!
if ($install_submit == 'Y' && !isset($upgrade)) {
    // Connect to the database as the administrator user
    $con = @mysql_connect($database_host,$admin_login,$admin_passwd);
    if (!$con) {
        $status++;
        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to connect to '{$database_host}' as '{$admin_login}'.<br>";
    } else {
        mysql_select_db($database_name, $con);
        $text .= "<script>el('mainform').style.display = 'none';</script><img src=\"{$images}/silk/accept.png\" border=\"0\" /> Connected to '{$database_host}' as '{$admin_login}'.<br>";
        // Drop out any existing database and user
        if (@mysql_query("DROP DATABASE IF EXISTS {$database_name};",$con)) {
            @mysql_query("DROP USER IF EXISTS '{$sys_login}'@'%';",$con);
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Dropped existing instance of '{$database_name}'.<br>";
        }
        else {
            $status++;
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to drop existing instance of '{$database_name}'.<br>";
        }

        // Create the database
        if (@mysql_query("CREATE DATABASE {$database_name};",$con)) {
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created new database '{$database_name}'.<br>";

            // Open a sql file and load it into the database
            // Start with the base tables
            populate_db($DBname,$DBPrefix,$sqlfile);
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created tables within database '{$database_name}'.<br>";

            populate_db($DBname,$DBPrefix,$sqlfile_data);
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Loaded tables with default data.<br>";

            // Add the system user to the database
            if(@mysql_query("GRANT ALL ON `{$database_name}`.* TO '{$sys_login}'@'localhost' IDENTIFIED BY '{$sys_passwd}';",$con)) {
                @mysql_query("GRANT ALL ON `{$database_name}`.* TO '{$sys_login}'@'%' IDENTIFIED BY '{$sys_passwd}';",$con);
                @mysql_query("GRANT ALL ON `{$database_name}`.* TO '{$sys_login}'@'{$database_host}' IDENTIFIED BY '{$sys_passwd}';",$con);
                @mysql_query("FLUSH PRIVILEGES;",$con);
                $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created system user '{$sys_login}'.<br>";
            }
            else {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to create system user '{$sys_login}'.<br>";
            }

            // add the default domain to the system
            // This is a manual add with hard coded values for timers.
            if (@mysql_query("INSERT INTO domains (id,name,admin_email,default_ttl,refresh,retry,expiry,minimum) VALUES (1,'{$default_domain}','hostmaster', 86400, 86400, 3600, 3600, 3600);",$con)) {
                @mysql_query("UPDATE sys_config SET value='{$default_domain}' WHERE name like 'dns_defaultdomain';",$con);
                $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created default DNS domain '{$default_domain}'.<br>";
            }
            else {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to create default DNS domain '{$default_domain}'.<br>";
            }

            // Open the database config and write the contents to it.
            if (!$fh = @fopen($dbconffile, 'w')) {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to open config file for writing: '{$dbconffile}'.<br>";
            }
            else {
                fwrite($fh, $dbconfig_contents);
                fclose($fh);
                $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created database config file.<br>";
            }

            // Update the version element in the sys_config table
            if(@mysql_query("UPDATE sys_config SET value='{$new_ver}' WHERE name like 'version';",$con)) {
               // $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Updated local version info.<br>";
            }
            else {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to update version info in table 'sys_config'.<br>";
            }

        } else {
            $status++;
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to create new database '{$database_name}'.<br>";
        }

        if ($status > 0) {
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> There was a fatal error. Install may be incomplete. Fix the issue and <a href=\"{$baseURL}\">try again</a>.<br>";
        } else {
            // remove the run_install file in the install dir
            if (!@unlink($runinstall)) {
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to delete the file '{$runinstall}'.<br>";
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Please remove '{$runinstall}' manually.<br>";
            }
            $text .= "You can now <a href='{$baseURL}'>CLICK HERE TO START</a> using OpenNetAdmin!<br>You can log in as 'admin' with a password of 'admin'<br>Enjoy!";
        }
    }
    // Close the database connection
    @mysql_close($con);
}



// Start printing the html
print <<<EOL
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
        <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
    </head>
    <style type="text/css">

        body {
            margin: 0px;
            font-family: Arial, Sans-Serif;
            color: 000000;
            background-color: FFFFFF;
            vertical-align: top;
        }

        textarea.edit {
            font-family: monospace;
            border: 1px solid #8CACBB;
            color: Black;
            background-color: white;
            padding: 3px;
            width:100%;
        }

        input.edit,select.edit {
            border: 1px solid #8CACBB;
            color: Black;
            background-color: white;
            vertical-align: middle;
            padding: 1px;
            display: inline;
        }
        #checks {
            font-size: small;
            border: 1px solid;
            margin-top: 15px;
        }
        #license {
            width: 550px;
            padding: 35px 0px;
            text-align: left;
        }
        #greeting, #upgreeting {
            width: 450px;
            padding: 20px 0px;
            text-align: left;
        }
        #help {
            -moz-border-radius: 6;
            padding: 5px;
            border: 1px solid;
            text-align: left;
            width:500px;
        }
        #maintitle {
            background-color: #D3DBFF;
            font-size: xx-large;
            width: 100%;
            padding: 0px 60px;
            -moz-border-radius-bottomleft: 10;
            -moz-border-radius-bottomright: 10;
        }
        #status {
            padding: 5px;
            border: 1px solid;
            text-align: left;
            width:500px;
        }

    </style>
    <body>
        <div align="center" style="width:100%;">
            <span id="maintitle">OpenNetAdmin Install</span><br>
EOL;




// print the GPL license and have them "ok" it to continue.
if ($install_submit != 'Y' and $overwrite != 'Y') { echo $licensediv; }

// Print a status to the user
print <<<EOL
            <div id="work" style="display: none;">
                <div id="prereq">{$requisitediv}</div>
                <div id="Greeting">{$greet_txt}</div>
EOL;

if ($install_submit == 'Y') { print "<script>el('work').style.display = '';</script>"; }

if ($upgrademain != '') {
    print $upgrademain;
    print $main;
    print $blankmain;
} else {
    print $main;
}

if ($install_submit == 'Y') {print "<div id='status'>{$text}</div><br>"; }

if ($install_submit == 'Y') {print '<div id="help">Thanks for using ONA. Please visit <a href="http://opennetadmin.com>http://opennetadmin.com</a></div>';}
if ($upgrademain == '') {print '<div id="help"></div>';}

print <<<EOL
                </div>
            </div>
    </body>
</html>
EOL;








/////////////////////////////////////////////////////////////////////
// The following functions were taken from the php-syslog-ng install scripts
// http://code.google.com/p/php-syslog-ng/
//
// They are used to take a mysqldump file and turn it into something the mysql_query function can use
/////////////////////////////////////////////////////////////////////
function populate_db($DBname, $DBPrefix, $sqlfile) {

    mysql_select_db($DBname);
    $mqr = @get_magic_quotes_runtime();
    @set_magic_quotes_runtime(0);
    $query = fread(fopen($sqlfile, "r"), filesize($sqlfile));
    @set_magic_quotes_runtime($mqr);
    $pieces  = split_sql($query);

    for ($i=0; $i<count($pieces); $i++) {
        $pieces[$i] = trim($pieces[$i]);
        if(!empty($pieces[$i]) && $pieces[$i] != "#") {
            $pieces[$i] = str_replace( "#__", $DBPrefix, $pieces[$i]);
            if (!$result = mysql_query ($pieces[$i])) {
                $errors[] = array ( mysql_error(), $pieces[$i] );
            }
        }
    }
}



function split_sql($sql) {
    $sql = trim($sql);
    $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);

    $buffer = array();
    $ret = array();
    $in_string = false;

    for($i=0; $i<strlen($sql)-1; $i++) {
        if($sql[$i] == ";" && !$in_string) {
            $ret[] = substr($sql, 0, $i);
            $sql = substr($sql, $i + 1);
            $i = 0;
        }

        if($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
            $in_string = false;
        }
        elseif(!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
            $in_string = $sql[$i];
        }
        if(isset($buffer[1])) {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$i];
    }

    if(!empty($sql)) {
        $ret[] = $sql;
    }
    return($ret);
}




?>
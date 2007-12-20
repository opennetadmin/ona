<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
//while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
//$include = $base . '/include';
//if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
//require_once($base . '/config/config.inc.php');
//require_once($conf['inc_functions']);
/* --------------------------------------------------------- */

$baseURL=dirname($_SERVER['SCRIPT_NAME']); $baseURL = rtrim($baseURL, '/');

// stuff and notes:
//  maybe change the mysqlt type to a variable..

$mainstyle='visible';

print <<<EOL
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.inc.php">
        <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
    </head>
    <body>
        <div align="center" style="width:100%;">
            <span style="background-color: #D3DBFF; font-size: xx-large;width: 100%;padding: 0px 60px;-moz-border-radius-bottomleft: 10;-moz-border-radius-bottomright: 10;">OpenNetAdmin Install</span><br>
EOL;
// print the GPL license and have them "ok" it to continue.
if ($install_submit != 'Y') {
    $license_text = file_get_contents($base.'/../docs/LICENSE');
    $mainstyle='hidden';
    print <<<EOL
            <div id="license" style="width: 550px;padding: 35px 0px;text-align: left;">
                <center><b>OpenNetAdmin is released under the following license:</b></center>
                <textarea class="edit" rows="25" cols="75">{$license_text}</textarea><br><br>
                <center><input class='edit' type="button" value="I Agree!" onclick="el('main').style.visibility = 'visible';el('input1').focus();el('license').style.display = 'none';" /></center>
            </div>
EOL;
}
print <<<EOL
            <div id="main" style="visibility: {$mainstyle};">
                <div id="Greeting" style="width: 450px;padding: 35px 0px;text-align: left;">
                It looks as though this is your first time running OpenNetAdmin. Please answer a few questions and we'll initialize the system for you. We've pre-populated some of the fields with suggested values.
                </div>
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
EOL;




// Init and setup some variables.
$DBname = $database_name;
$DBPrefix = '';
$text = '';
$status=0;
$runinstall = $base.'/run_install';
$sqlfile = $base.'/ona-tables.sql';
$sqlfile_data = $base.'/ona-data.sql';
$dbconffile = $base.'/../www/local/config/database_settings.inc.php';

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

    // Note:  I set the login up as ona-sys so that we could have
    // a more "functional" user to do connections with that is not root or
    // some sort of full admin.
    //
    // Type:
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





// http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

// If they have selected to keep the tables then remove the run_install file
if ($install_submit == 'Y' && $keep == 'Y') {
    unlink($runinstall);
    print <<<EOL
                <br>
                <img src="{$images}/silk/accept.png" border="0" /> Keeping your original data.<br>
                You can now <a href='{$baseURL}'>CLICK HERE</a> to start using OpenNetAdmin! Enjoy!
            </div>
        </div>
    </body>
</html>
EOL;
    exit;
}

// If the initialize button was clicked, lets go for it!
if ($install_submit == 'Y') {
    // Connect to the database as the administrator user
    $con = @mysql_connect($database_host,$admin_login,$admin_passwd);
    if (!$con) {
        $status++;
        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to connect to '{$database_host}' as '{$admin_login}'.<br>";
    }
    else {
        // If the selected database already exists. we should warn of that.
        if (mysql_select_db($database_name, $con) && $overwrite != 'Y') {
            $text .= "<img src=\"{$images}/silk/exlamation.png\" border=\"0\" /> The database '{$database_name}' seems to already exist, are you sure you want to over write it?.<br>";
print <<<EOL
                <br>
                <img src="{$images}/silk/error.png" border="0" /> The database '{$database_name}' seems to already exist, are you sure you want to over write it? <img src="{$images}/silk/error.png" border="0" /> <br>
                You will loose all data in the current database if you click Yes.  If you are ok with wiping out the data, click Yes.<br>
                If you want to keep the data, click no to bypass the database creation steps.<br><br>
                <input class='edit' type='button' name='over' value='Yes, Clear the data.' onclick="el('overwrite').value='Y';el('mainform').submit();" />&nbsp;&nbsp;&nbsp;
                <input class='edit' type='button' name='keep' value='No, Keep the data.' onclick="el('keep').value='Y';el('mainform').submit();" />
            </div>
        </div>
    </body>
</html>
EOL;
            exit;
        }



        $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Connected to '{$database_host}' as '{$admin_login}'.<br>";
        // Drop out any existing database and user
        if (@mysql_query("DROP DATABASE {$database_name};",$con)) {
            @mysql_query("DROP USER '{$sys_login}'@'%';",$con);
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Dropped existing instance of '{$database_name}'.<br>";
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
            if(@mysql_query("GRANT ALL ON `{$database_name}`.* TO '{$sys_login}'@'%' IDENTIFIED BY '{$sys_passwd}';",$con)) {
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
                $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created default DNS domain '{$default_domain}'.<br>";
            }
            else {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to create default DNS domain '{$default_domain}'.<br>";
            }

            // Open the database config and write the contents to it.
            if (!$fh = fopen($dbconffile, 'w')) {
                $status++;
                $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to open config file for writing: '{$dbconffile}'.<br>";
            }
            else {
                fwrite($fh, $dbconfig_contents);
                fclose($fh);
                $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Created database config file.<br>";
            }


            // maybe check for gmp module and recommend it

        }
        else {
            $status++;
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to create new database '{$database_name}'.<br>";
        }

        if ($status > 0) {
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> There was a fatal error. Install may be incomplete. Fix the issue and try again.<br>";
        }
        else {
            // remove the run_install file in the install dir
            unlink($runinstall);
            $text .= "You can now <a href='{$baseURL}'>CLICK HERE</a> to start using OpenNetAdmin!<br>You can log in as 'admin' with a password of 'admin'<br>Enjoy!";
        }
    }

    // Close the database connection
    @mysql_close($con);
}

// Print a status to the user
print <<<EOL
            <br>
            <script type="text/javascript" language="javascript">el('help').innerHTML = input1text;</script>
EOL;

if ($install_submit == 'Y') {print "<div id='status' style='padding: 5px;border: 1px solid;text-align: left;width:500px;'>{$text}</div>"; }

print <<<EOL
            <br>
            <div id="help" style="-moz-border-radius: 6;padding: 5px;border: 1px solid;text-align: left;width:500px;">Thanks for using ONA. Please visit <a href="http://opennetadmin.com>http://opennetadmin.com</a></div><br>
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
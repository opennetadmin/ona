<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
$onabase = dirname($base);
////while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $onabase . '/www/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }

$conf = array (
    /* General Setup */
    // Database Context
    // For possible values see the $ona_contexts() array  in the database_settings.inc.php file
    "default_context"        => 'DEFAULT',

    /* Include Files: Functions */
    "inc_functions"          => "$include/functions_general.inc.php",
    "inc_functions_db"       => "$include/functions_db.inc.php",
    "inc_functions_auth"     => "$include/functions_auth.inc.php",
    "inc_db_sessions"        => "$include/adodb_sessions.inc.php",
    "inc_adodb"              => "$include/adodb/adodb.inc.php",
    "inc_adodb_xml"          => "$include/adodb/adodb-xmlschema03.inc.php",

    /* Defaults for some user definable options normally in sys_config table */
    "debug"                  => "2",
    "logfile"                => "/var/log/ona.log",

);

// Include the basic system functions
// any $conf settings used in this "require" should not be user adjusted in the sys_config table
require_once("{$include}/functions_general.inc.php");

// Include the basic database functions
#require_once("{$include}/functions_db.inc.php");

// Include the localized Database settings
$dbconffile = "{$onabase}/www/local/config/database_settings.inc.php";
if (file_exists($dbconffile)) {
    if (substr(exec("php -l $dbconffile"), 0, 28) == "No syntax errors detected in") {
        @include($dbconffile);
    } else {
        echo "Syntax error in your DB config file: {$dbconffile}<br>Please check that it contains a valid PHP formatted array, or check that you have the php cli tools installed.<br>You can perf
orm this check maually using the command 'php -l {$dbconffile}'.";
        exit;
    }
} else {
#    require_once($base.'/../install/install.php');
#    exit;
}

#// If it does, run the install process.
#if (file_exists($base.'/local/config/run_install') or @$runinstaller or @$install_submit == 'Y') {
#    // Process the install script
#    require_once($base.'/../install/install.php');
#    exit;
#}






// get adodb xml processing
@require_once($conf['inc_adodb_xml']);
/* --------------------------------------------------------- */


// Init and setup some variables.
$text = '';
$status = 0;
$runinstall = $onabase.'/www/local/config/run_install';
$xmlfile_tables = $base.'/ona-table_schema.xml';
$xmlfile_data = $base.'/ona-data.xml';
$new_ver = trim(file_get_contents($onabase.'/VERSION'));
$curr_ver = '';

# junk output from included functions
$stdout = '';
$syslog = '';
$log_to_db = '';

$install_complete=1;

// Start the menu loop
while($install_complete){

  echo "\nWELCOME TO THE OPENNETADMIN INSTALLER..\n";

  // License info
  echo "ONA is licensed under GPL v2.0.\n";
  $showlicense = promptUser("Would you like to view license? [y/N] ", 'n');
  if ($showlicense == 'y') { system("more -80 {$base}/../docs/LICENSE"); }
  // TODO: Do you agree to the license

  #while(empty($name)){
  #  $name = promptUser(" Enter server name");
  #}

  check_requirements();

  // Check if it is a fresh install or upgrade
  if (@file_exists($dbconffile)) {
     
  } else {
    new_install();
  }

  exit;

}





// Gather requirement information
function check_requirements() {

  global $onabase;

  system('clear');
  // Get some pre-requisite information
  $phpversion = phpversion() > '5.0' ? 'PASS' : 'FAIL';
  $hasgmp = function_exists( 'gmp_init' ) ? 'PASS' : 'FAIL';
  //echo function_exists( 'gmp_init' ) ? '' : 'PHP GMP module is missing.';
  $hasmysql = function_exists( 'mysqli_connect' ) ? 'PASS' : 'FAIL';
  $hasxml = function_exists( 'xml_parser_create' ) ? 'PASS' : 'FAIL';
  $hasmbstring = function_exists( 'mb_internal_encoding' ) ? 'PASS' : 'FAIL';
  $dbconfwrite = @is_writable($onabase.'/www/local/config/') ? 'PASS' : 'FAIL';

  echo <<<EOL

CHECKING PREREQUISITES...

  PHP version greater than 5.0:               $phpversion
  PHP GMP module:                             $hasgmp
  PHP XML module:                             $hasxml
  PHP mysqli function:                        $hasmysql
  PHP mbstring function:                      $hasmbstring
  $onabase/www/local/config dir writable:     $dbconfwrite

EOL;
}




/*
// Initial text for the greeting div
$greet_txt = "It looks as though this is your first time running OpenNetAdmin. Please answer a few questions and we'll initialize the system for you. We've pre-populated some of the fields with suggested values.  If the database you specify below already exists, it will be overwritten entirely.";


$upgrademain = '';

// Get info from old $db_context[] array if ona_contexts does not exist
// this is transitional, hopefully I can remove this part soon.
if (!is_array($ona_contexts) and is_array($db_context)) {
    $type='mysqli';
    $context_name='default';
    $ona_contexts[$context_name]['databases']['0']['db_type']     = $db_context[$type] [$context_name] ['primary'] ['db_type'];
    $ona_contexts[$context_name]['databases']['0']['db_host']     = $db_context[$type] [$context_name] ['primary'] ['db_host'];
    $ona_contexts[$context_name]['databases']['0']['db_login']    = $db_context[$type] [$context_name] ['primary'] ['db_login'];
    $ona_contexts[$context_name]['databases']['0']['db_passwd']   = $db_context[$type] [$context_name] ['primary'] ['db_passwd'];
    $ona_contexts[$context_name]['databases']['0']['db_database'] = $db_context[$type] [$context_name] ['primary'] ['db_database'];
    $ona_contexts[$context_name]['databases']['0']['db_debug']    = $db_context[$type] [$context_name] ['primary'] ['db_debug'];
    $ona_contexts[$context_name]['description']   = 'Default data context';
    $ona_contexts[$context_name]['context_color'] = '#D3DBFF';
}


// If they already have a dbconffile, assume that we are doing and upgrade
if (@file_exists($dbconffile)) {
    // Get the existing database config (again) so we can connect using its settings
    include($dbconffile);

    $context_count = count($ona_contexts);

    $greet_txt = "It looks as though you already have a version of OpenNetAdmin installed.  You should make a backup of the data for each context listed below before proceeding with this upgrade.<br><br>We will be upgrading to version '{$new_ver}'.<br><br>We have found {$context_count} context(s) in your current db configuration file.<br><br>";

    $greet_txt .= "<center><table><tr><th>Context Name</th><th>DB type</th><th>Server</th><th>DB name</th><th>Version</th><th>Upgrade Index</th></tr>";

    // Loop through each context and identify the Databases within
    foreach(array_keys($ona_contexts) as $cname) {

        foreach($ona_contexts[$cname]['databases'] as $cdbs) {
            $curr_ver = '<span style="background-color:#FF7375;">Unable to determine</span>';
            // Make an initial connection to a DB server without specifying a database
            $db = ADONewConnection($cdbs['db_type']);
            @$db->Connect( $cdbs['db_host'], $cdbs['db_login'], $cdbs['db_passwd'], '' );

            if (!$db->IsConnected()) {
                $status++;
                printmsg("INFO => Unable to connect to server '{$cdbs['db_host']}'. ".$db->ErrorMsg(),0);
                $err_txt .= " <img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}] Failed to connect as '{$cdbs['db_login']}'.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
            } else {
                if ($db->SelectDB($cdbs['db_database'])) {
                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'version'");
                    $array = $rs->FetchRow();
                    $curr_ver = $array['value'];

                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'upgrade_index'");
                    $array = $rs->FetchRow();
                    $upgrade_index = $array['value'];

                    $levelinfo = $upgrade_index;

                    if ($curr_ver == '') { $curr_ver = 'PRE-v08.02.18'; }
                    if ($upgrade_index < 8) { $levelinfo = "<span style='background-color:#FF7375;'>Must upgrade to at least v09.09.15 first!</span>"; }
                } else {
                    $status++;
                    $err_txt .= " <img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}] Failed to select DB '{$cdbs['db_database']}'.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
                }
            }
            // Close the database connection
            @$db->Close();


            $greet_txt .= "<tr style='background-color:{$ona_contexts[$cname]['context_color']};'><td >{$cname}</td><td>{$cdbs['db_type']}</td><td>{$cdbs['db_host']}</td><td>{$cdbs['db_database']}</td><td>{$curr_ver}</td><td><center>{$levelinfo}</center></td></tr>";
        }

    }

    $greet_txt .= "</table></center>";


    if ($status == 0) {
        $upgrademain = <<<EOL
            <div id="upgrademain">
                <form id="upgradeform">
                    <input type='hidden' name='install_submit' value='Y' />
                    <input id='upgrade' type='hidden' name='upgrade' value='N' />
                    <a style="text-decoration: none;" href="{$baseURL}"><input class='edit' type="button" value="Cancel upgrade" onclick="" /></a>
                    <input class='edit' type='button' name='upgrade' value='Perform the upgrade.' onclick="el('upgrade').value='Y';el('upgradeform').submit();" />
                </form>
            </div>
EOL;
    } else {
        $upgrademain = <<<EOL
            <div id='status'>There was an error determining database context versions. Please correct them before proceeding.<br><br>Check that the content of your database configuration file:<br> '<i>{$dbconffile}</i>'<br>is accurate and that the databases themselves are configured properly.<br><br>{$err_txt}</div><br>
            <div id="upgrademain">
                <form id="upgradeform">
                    <input type='hidden' name='install_submit' value='Y' />
                    <input id='upgrade' type='hidden' name='upgrade' value='N' />
                    <a style="text-decoration: none;" href="{$baseURL}"><input class='edit' type="button" value="Retry upgrade" onclick="" /></a>
                </form>
            </div>
EOL;
    }

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
                        <tr onmouseover="el('help').innerHTML = inputtext_dbtype;">
                            <td>Database Type:</td>
                            <td>
                                <select class='edit' name='dbtype' onfocus="el('help').innerHTML = inputtext_dbtype;">
                                <option value="mysqli" selected="true">MySQL</option>
                              <!--  <option value="oci8">Oracle (oci8 driver)</option>
                                <option value="oci8po">Oracle (oci8po driver)</option>
                                <option value="postgres7">Postgres7</option>
                                <option value="postgres8">Postgres8</option>-->
                                </select>
                            </td>
                        </tr>
                        <tr onmouseover="el('help').innerHTML = input2text;">
                            <td>Database Admin:</td><td><input class='edit' type='text' name='admin_login' value='root' onfocus="el('help').innerHTML = input2text;" /></td></tr>
                        <tr onmouseover="el('help').innerHTML = input3text;">
                            <td>Database Admin Password:</td><td><input class='edit' type='password' name='admin_passwd' value='{$admin_passwd}' onfocus="el('help').innerHTML = input3text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input4text;">
                            <td>Database Name:</td><td><input class='edit' type='text' name='database_name' value='default' onfocus="el('help').innerHTML = input4text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input5text;">
                            <td>Application Database User Name:</td><td><input class='edit' type='text' name='sys_login' value='ona_sys' onfocus="el('help').innerHTML = input5text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input6text;">
                            <td>Application Database User Password:</td><td><input class='edit' type='password' name='sys_passwd' value='{$sys_passwd}' onfocus="el('help').innerHTML = input6text;"/></td></tr>
                        <tr onmouseover="el('help').innerHTML = input7text;">
                            <td>Default Domain Name:</td><td><input class='edit' type='text' name='default_domain' value='example.com' onfocus="el('help').innerHTML = input7text;"/></td></tr>
                        <tr><td colspan=2 style="text-align: center;"><br><input class='edit' type='submit' value='Create my database!' /></td></tr>
                    </table>
                </form>
                <script type="text/javascript" language="javascript">

                    var input1text = '<b>Database Host:</b> The hostname or IP address of the database server where your database will be located.';
                    var input2text = '<b>Database Admin:</b> The username for the database administrator. This account will be used to create the new database and must have proper privledges to do so.';
                    var input3text = '<b>Database Admin Password:</b> The password for the database administrator account.';
                    var input4text = '<b>Database Name:</b> The name of the database that will store the OpenNetAdmin tables.  We suggest "default", which will become "ona_default" when created.';
                    var input5text = '<b>System User Name:</b> The application username used by the php code to connect to the database.  We suggest "ona_sys"';
                    var input6text = '<b>System User Password:</b> The password for the application user.';
                    var input7text = '<b>Default Domain Name:</b> The default DNS domain for your site.  This will be your primary domain to add hosts to and will serve the default domain for certain tasks.';
                    var inputtext_dbtype = '<b>Database Type:</b> The type of database running on the database host.';

                </script>
            </div>
EOL;






// If they have selected to keep the tables then remove the run_install file
if ($install_submit == 'Y' && $upgrade == 'Y') {

    // Loop through each context and upgrade the Databases within
    foreach(array_keys($ona_contexts) as $cname) {

        foreach($ona_contexts[$cname]['databases'] as $cdbs) {
            printmsg("INFO => [{$cname}/{$cdbs['db_host']}] Performing an upgrade.",0);

            // switch from mysqlt to mysql becuase of adodb problems with innodb and opt stuff when doing xml
            $adotype = $cdbs['db_type'];
            //if ($adotype == 'mysqlt') $adotype = 'mysql';

            // Make an initial connection to a DB server without specifying a database
            $db = ADONewConnection($adotype);
            @$db->NConnect( $cdbs['db_host'], $cdbs['db_login'], $cdbs['db_passwd'], '' );

            if (!$db->IsConnected()) {
                $status++;
                printmsg("INFO => Unable to connect to server '{$cdbs['db_host']}'. ".$db->ErrorMsg(),0);
                $text .= " <img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}] Failed to connect to '{$cdbs['db_host']}' as '{$cdbs['db_login']}'.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
            } else {
                $db->Close();
                if ($db->NConnect( $database_host, $admin_login, $admin_passwd, $cdbs['db_database'])) {


                    // Get the current upgrade index if there is one.
                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'upgrade_index'");
                    $array = $rs->FetchRow();
                    $upgrade_index = $array['value'];

                    if ($upgrade_index < 8) {
                        $status++;
                        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] This database must be on at least v09.09.15 before upgrading to this version.<br>";
                        printmsg("ERROR => [{$cname}/{$cdbs['db_host']}] This database must be on at least v09.09.15 before upgrading to this version.",0);
                        break;
                    }

                    $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Keeping your original data.<br>";

                    // update existing tables in our database to match our baseline xml schema
                    // create a schema object and build the query array.
                    $schema = new adoSchema( $db );
                    // Build the SQL array from the schema XML file
                    $sql = $schema->ParseSchema($xmlfile_tables);
                    // Execute the SQL on the database
                    //$text .= "<pre>".$schema->PrintSQL('TEXT')."</pre>";
                    if ($schema->ExecuteSchema( $sql ) == 2) {
                        $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Upgrading tables within database '{$cdbs['db_database']}'.<br>";
                        printmsg("INFO => [{$cname}/{$cdbs['db_host']}] Upgrading tables within database: {$cdbs['db_database']}",0);
                    } else {
                        $status++;
                        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> There was an error upgrading tables.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
                        printmsg("ERROR => There was an error processing tables: ".$db->ErrorMsg(),0);
                        break;
                    }





                    $script_text = '';
                    if ($upgrade_index == '') {
                        $text .= "<img src=\"{$images}/silk/error.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Auto upgrades not yet supported. Please see docs/UPGRADES<br>";
                    } else {
                        // loop until we have processed all the upgrades
                        while(1 > 0) {
                            // Find out what the next index will be
                            $new_index = $upgrade_index + 1;
                            // Determine file name
                            //$upgrade_sqlfile = "{$base}/{$upgrade_index}-to-{$new_index}.sql";
                            $upgrade_xmlfile = "{$base}/{$upgrade_index}-to-{$new_index}.xml";
                            $upgrade_phpfile = "{$base}/{$upgrade_index}-to-{$new_index}.php";
                            // Check that the upgrade script exists
                            if (file_exists($upgrade_phpfile)) {
                                $script_text .= "<img src=\"{$images}/silk/error.png\" border=\"0\" />Please go to a command prompt and execute 'php {$upgrade_phpfile}' manually to complete the upgrade!<br>";
                            }
                            // Check that the upgrade file exists
                            if (file_exists($upgrade_xmlfile)) {
                                // get the contents of the sql update file
                                // create new tables in our database
                                // create a schema object and build the query array.
                                $schema = new adoSchema( $db );
                                // Build the SQL array from the schema XML file
                                $sql = $schema->ParseSchema($upgrade_xmlfile);
                                // Execute the SQL on the database
                                if ($schema->ExecuteSchema( $sql ) == 2) {
                                    $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Processed XML update file.<br>";
                                    printmsg("INFO => [{$cname}/{$cdbs['db_host']}] Processed XML update file.",0);

                                    // update index info in the DB
                                    $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Upgraded from index {$upgrade_index} to {$new_index}.<br>";
                                    // Update the upgrade_index element in the sys_config table
                                    if($db->Execute("UPDATE sys_config SET value='{$new_index}' WHERE name like 'upgrade_index'")) {
                                        $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Updated DB upgrade_index variable to '{$new_index}'.<br>";
                                    }
                                    else {
                                        $status++;
                                        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Failed to update upgrade_index variable in table 'sys_config'.<br>";
                                        break;
                                    }
                                    $upgrade_index++;
                                } else {
                                    $status++;
                                    $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Failed to process XML update file.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
                                    printmsg("ERROR => [{$cname}/{$cdbs['db_host']}] Failed to process XML update file.  ".$db->ErrorMsg(),0);
                                    break;
                                }
                            } else {
                                break;
                            }
                        }

                    }


                    // Update the version element in the sys_config table if there were no previous errors
                    if($status == 0) {
                        if($db->Execute("UPDATE sys_config SET value='{$new_ver}' WHERE name like 'version'")) {
                            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Updated DB version variable to '{$new_ver}'.<br>";
                        }
                        else {
                            $status++;
                            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Failed to update version info in table 'sys_config'.<br>";
                        }
                    }
                } else {
                    $status++;
                    $text .= " <img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> [{$cname}/{$cdbs['db_host']}] Failed to select DB '{$cdbs['db_database']}'.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
                }
            }
            // Close the database connection
            @$db->Close();

        }

    }

    // If we still have the old reference to db_context in our config, upgrade it
    if (is_array($db_context)) {
        // set default db name to uppercase
        $ona_contexts['DEFAULT'] = $ona_contexts['default'];unset($ona_contexts['default']);

        // Open the database config and write the contents to it.
        if (!$fh = @fopen($dbconffile, 'w')) {
            $status++;
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to open config file for writing: '{$dbconffile}'.<br>";
            printmsg("ERROR => Failed to open config file for writing: '{$dbconffile}'.",0);
        }
        else {
            fwrite($fh, "<?php\n\n\$ona_contexts=".var_export($ona_contexts,TRUE).";\n\n?>");
            fclose($fh);
            $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Upgraded database connection config file to new format.<br>";
            printmsg("INFO => Upgraded database connection config file to new format.",0);
        }
    }


    if($status == 0) {
        $text .= $script_text;
        $text .= "You can now <a href='".parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)."'>CLICK HERE</a> to start using OpenNetAdmin! Enjoy!";

        if (@file_exists($runinstall)) {
          if (!@unlink($runinstall)) {
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Failed to delete the file '{$runinstall}'.<br>";
            $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> Please remove '{$runinstall}' manually.<br>";
          }
        }
    } else {
        $text .= "<img src=\"{$images}/silk/exclamation.png\" border=\"0\" /> There was a fatal error. Upgrade may be incomplete. Fix the issue and <a href=\"{$baseURL}\">try again</a>.<br>";
    }

}


*/





// This is the section for an brand new install
function new_install() {

  echo "\n\n";
  global $text,$xmlfile_data,$xmlfile_tables,$dbconffile;

  // Gather info
  $dbtype = 'mysqli'; $adotype = $dbtype;
  $database_host = promptUser("Database host? ", 'localhost');
  $admin_login = promptUser("Database admin? ", 'root');
  $admin_passwd = promptUser("Database admin password? ", '');
  $sys_login = promptUser("Application Database user name? ", 'ona_sys');
  $sys_passwd = promptUser("Application Database user password? ", 'changeme');
  $database_name = promptUser("Database name? ona_", 'default');
  $default_domain = promptUser("Default DNS domain? ", 'example.com');


    // Just to keep things a little bit grouped, lets prepend the database with ona_
    $database_name = 'ona_'.$database_name;

    // set up initial context connection information
    $context_name = 'DEFAULT';
    $ona_contexts[$context_name]['databases']['0']['db_type']     = $dbtype;
    $ona_contexts[$context_name]['databases']['0']['db_host']     = $database_host;
    $ona_contexts[$context_name]['databases']['0']['db_login']    = $sys_login;
    $ona_contexts[$context_name]['databases']['0']['db_passwd']   = $sys_passwd;
    $ona_contexts[$context_name]['databases']['0']['db_database'] = $database_name;
    $ona_contexts[$context_name]['databases']['0']['db_debug']    = FALSE;
    $ona_contexts[$context_name]['description']   = 'Default data context';
    $ona_contexts[$context_name]['context_color'] = '#D3DBFF';


    // Make an initial connection to a DB server without specifying a database
    $db = ADONewConnection($adotype);
    $db->NConnect( $database_host, $admin_login, $admin_passwd, '' );

    if (!$db->IsConnected()) {
        $status++;
        $text .= "Failed to connect to '{$database_host}' as '{$admin_login}'.\n".$db->ErrorMsg()."\n";
        printmsg("INFO => Unable to connect to server '$database_host'. ".$db->ErrorMsg(),0);
    } else {
        $text .= "Connected to '{$database_host}' as '{$admin_login}'.\n";

        // Drop out any existing database and user
        if (@$db->Execute("DROP DATABASE IF EXISTS {$database_name}")) {
            //@$db->Execute("DROP USER IF EXISTS '{$sys_login}'@'%'");
            $text .= "Dropped existing instance of '{$database_name}'.\n";
            printmsg("INFO => Dropped existing DB: $database_name",0);
        }
        else {
            $status++;
            $text .= "Failed to drop existing instance of '{$database_name}'.\n".$db->ErrorMsg()."\n";
        }

        // MP TODO: when this is done as part of an add conext, we must copy the system_config data from the default context to populate it
        // so that plugins that have created options will show up etc.  Prompt the user that this happened so they can change what they want.

        // Create the new database
        $datadict = NewDataDictionary($db);
        $sqlarray = $datadict->CreateDatabase($database_name);
        if ($datadict->ExecuteSQLArray($sqlarray) == 2) {
            $text .= "Created new database '{$database_name}'.\n";
            printmsg("INFO => Added new DB: $database_name",0);
        }
        else {
            $status++;
            $text .= "Failed to create new database '{$database_name}'.\n".$db->ErrorMsg()."\n";
            printmsg("ERROR => Failed to create new database '{$database_name}'. ".$db->ErrorMsg(),0);
        }


        // select the new database we just created
        $db->Close();
        if ($db->NConnect( $database_host, $admin_login, $admin_passwd, $database_name)) {

            $text .= "Selected existing DB: '{$database_name}'.\n";

            // create new tables in our database
            // create a schema object and build the query array.
            $schema = new adoSchema( $db );
            // Build the SQL array from the schema XML file
            $sql = $schema->ParseSchema($xmlfile_tables);
            // Execute the SQL on the database
            if ($schema->ExecuteSchema( $sql ) == 2) {
                $text .= "Creating and updating tables within database '{$database_name}'.\n";
                printmsg("INFO => Creating and updating tables within new DB: {$database_name}",0);
            } else {
                $status++;
                $text .= "There was an error processing tables.\n".$db->ErrorMsg()."\n";
                printmsg("ERROR => There was an error processing tables: ".$db->ErrorMsg(),0);
            }

             // Load initial data into the new tables
            if ($status == 0) {
                $schema = new adoSchema( $db );
                // Build the SQL array from the schema XML file
                $sql = $schema->ParseSchema($xmlfile_data);
                //$text .= "<pre>".$schema->PrintSQL('TEXT')."</pre>";
                // Execute the SQL on the database
                if ($schema->ExecuteSchema( $sql ) == 2) {
                    $text .= "Loaded tables with default data.\n";
                    printmsg("INFO => Loaded data to new DB: {$database_name}",0);
                } else {
                    $status++;
                    $text .= "Failed load default data.\n".$db->ErrorMsg()."\n";
                    printmsg("ERROR => There was an error loading the data: ".$db->ErrorMsg(),0);
                }
            }

            // Add the system user to the database
            // Run the query
          if ($status == 0) {

            // it is likely that this method here is mysql only?
            if($db->Execute("GRANT ALL ON {$database_name}.* TO '{$sys_login}'@'localhost' IDENTIFIED BY '{$sys_passwd}'")) {
                $db->Execute("GRANT ALL ON {$database_name}.* TO '{$sys_login}'@'%' IDENTIFIED BY '{$sys_passwd}'");
                $db->Execute("GRANT ALL ON {$database_name}.* TO '{$sys_login}'@'{$database_host}' IDENTIFIED BY '{$sys_passwd}'");
                $db->Execute("FLUSH PRIVILEGES");
                $text .= "Created system user '{$sys_login}'.\n";
                printmsg("INFO => Created new DB user: {$sys_login}",0);
            }
            else {
                $status++;
                $text .= "Failed to create system user '{$sys_login}'.\n".$db->ErrorMsg()."\n";
                printmsg("ERROR => There was an error creating DB user: ".$db->ErrorMsg(),0);
            }


            // add the default domain to the system
            // This is a manual add with hard coded values for timers.
            $xmldefdomain = <<<EOL
<?xml version="1.0"?>
<schema version="0.3">
<sql>
    <query>INSERT INTO domains (id,name,admin_email,default_ttl,refresh,retry,expiry,minimum,parent_id,serial,primary_master) VALUES (1,'{$default_domain}','hostmaster', 86400, 86400, 3600, 3600, 3600,0,0,0)</query>
    <query>UPDATE sys_config SET value='{$default_domain}' WHERE name like 'dns_defaultdomain'</query>
</sql>
</schema>
EOL;
            $schema = new adoSchema( $db );

            // Build the SQL array from the schema XML file
            $domainsql = $schema->ParseSchemaString($xmldefdomain);

            // Execute the SQL on the database
            if ($schema->ExecuteSchema( $domainsql ) == 2) {
                $text .= "Created default DNS domain '{$default_domain}'.\n";
            } else {
                $status++;
                $text .= "Failed to create default DNS domain '{$default_domain}'.\n".$db->ErrorMsg()."\n";
            }


            // Open the database config and write the contents to it.
            if (!$fh = @fopen($dbconffile, 'w')) {
                $status++;
                $text .= "Failed to open config file for writing: '{$dbconffile}'.\n";
            }
            else {
                fwrite($fh, "<?php\n\n\$ona_contexts=".var_export($ona_contexts,TRUE).";\n\n?>");
                fclose($fh);
                $text .= "Created database connection config file.\n";
            }

            // Update the version element in the sys_config table
            if(@$db->Execute("UPDATE sys_config SET value='{$new_ver}' WHERE name like 'version'")) {
               // $text .= "<img src=\"{$images}/silk/accept.png\" border=\"0\" /> Updated local version info.<br>";
            }
            else {
                $status++;
                $text .= "Failed to update version info in table 'sys_config'.\n".$db->ErrorMsg()."\n";
            }
          }

        } else {
            $status++;
            $text .= "Failed to select DB '{$database_name}'.\n".$db->ErrorMsg()."\n";
            printmsg("ERROR => Failed to select DB: {$database_name}.  ".$db->ErrorMsg(),0);
        }



//TODO: fix this
        if ($status > 0) {
            $text .= "There was a fatal error. Install may be incomplete. Fix the issue and try again\n";
        } else {
            // remove the run_install file in the install dir
            if (@file_exists($runinstall)) {
              if (!@unlink($runinstall)) {
                $text .= "Failed to delete the file '{$runinstall}'.\n";
                $text .= "Please remove '{$runinstall}' manually.\n";
              }
            }
            $text .= "You can now go the following URL in your browser: ".parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)."' using OpenNetAdmin!\nYou can log in as 'admin' with a password of 'admin'\nEnjoy!";
        }

        // Close the database connection
        @$db->Close();
    }

// Print out the text to the end user
echo $text;

}





if ($install_submit == 'Y') { print "<script>el('work').style.display = '';</script>"; }

if ($upgrademain != '') {
    print $upgrademain;
    print $main;
    print $blankmain;
} else {
    print $main;
}

if ($install_submit == 'Y') {print "<div id='status'>{$text}</div><br>"; }

if ($install_submit == 'Y') {print '<div id="help">Thanks for using ONA. Please visit <a href="http://opennetadmin.com">http://opennetadmin.com</a></div>';}
if ($upgrademain == '' and $install_submit != 'Y') {print '<div id="help"></div>';}







//#######################################################################
//# Function: Prompt user and get user input, returns value input by user.
//#           Or if return pressed returns a default if used e.g usage
//# $name = promptUser("Enter your name");
//# $serverName = promptUser("Enter your server name", "localhost");
//# Note: Returned value requires validation 
// from http://wiki.uniformserver.com/index.php/PHP_CLI:_User_Input
//#.......................................................................
function promptUser($promptStr,$defaultVal=false){;

  if($defaultVal) {                             // If a default set
     echo $promptStr. "[". $defaultVal. "] : "; // print prompt and default
  }
  else {                                        // No default set
     echo $promptStr. ": ";                     // print prompt only
  } 
  $name = chop(fgets(STDIN));                   // Read input. Remove CR
  if(empty($name)) {                            // No value. Enter was pressed
     return $defaultVal;                        // return default
  }
  else {                                        // Value entered
     return $name;                              // return value
  }
}
//========================================= End promptUser ============



?>

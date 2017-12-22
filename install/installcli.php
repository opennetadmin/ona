<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
$onabase = dirname($base);
////while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $onabase . '/www/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }

// MP: Since we know ONA will generate a ton of notice level errors, lets turn them off here
error_reporting (E_ALL ^ E_NOTICE);

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
        echo "Syntax error in your DB config file: {$dbconffile} Please check that it contains a valid PHP formatted array, or check that you have the php cli tools installed. You can perform this check maually using the command 'php -l {$dbconffile}'.";
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
  if ($showlicense == 'y') {
    system("more -80 {$base}/../docs/LICENSE");
    promptUser("[Press Enter To Continue]");
  }

  check_requirements();

  // Check if it is a fresh install or upgrade
  if (@file_exists($dbconffile)) {
    upgrade();
  } else {
    new_install();
  }

  exit;

}





// Gather requirement information
function check_requirements() {

  global $conf,$onabase;

  system('clear');
  // Get some pre-requisite information
  $phpversion = phpversion() > '5.0' ? 'PASS' : 'FAIL';
  $hasgmp = function_exists( 'gmp_init' ) ? 'PASS' : 'FAIL';
  //echo function_exists( 'gmp_init' ) ? '' : 'PHP GMP module is missing.';
  $hasmysql = function_exists( 'mysqli_connect' ) ? 'PASS' : 'FAIL';
  $hasxml = function_exists( 'xml_parser_create' ) ? 'PASS' : 'FAIL';
  $hasmbstring = function_exists( 'mb_internal_encoding' ) ? 'PASS' : 'FAIL';
  $dbconfwrite = @is_writable($onabase.'/www/local/config/') ? 'PASS' : 'FAIL';
  $logfilewrite = @is_writable($conf['logfile']) ? 'PASS' : 'FAIL';

  echo <<<EOL

CHECKING PREREQUISITES...

  PHP version greater than 5.0:               $phpversion
  PHP GMP module:                             $hasgmp
  PHP XML module:                             $hasxml
  PHP mysqli function:                        $hasmysql
  PHP mbstring function:                      $hasmbstring
  $onabase/www/local/config dir writable:     $dbconfwrite
  {$conf['logfile']} writable:                $logfilewrite

EOL;
}




function upgrade() {

  echo "\n\n";
  global $new_ver,$text,$xmlfile_data,$xmlfile_tables,$dbconffile;

  // If they already have a dbconffile, assume that we are doing and upgrade
  if (@file_exists($dbconffile)) {
    // Get the existing database config (again) so we can connect using its settings
    include($dbconffile);

    $context_count = count($ona_contexts);

    $text = "It looks as though you already have a version of OpenNetAdmin installed.\nYou should make a backup of the data for each context listed below before proceeding with this upgrade.\n\nWe will be upgrading to version '{$new_ver}'.\n\nWe have found {$context_count} context(s) in your current db configuration file.\n\n";

    $text .= "Context 	DB type		Server		DB name		Version 	Upgrade Index\n";
    // Loop through each context and identify the Databases within
    foreach(array_keys($ona_contexts) as $cname) {

        foreach($ona_contexts[$cname]['databases'] as $cdbs) {
            $curr_ver = 'Unable to determine';
            // Make an initial connection to a DB server without specifying a database
            $db = ADONewConnection($cdbs['db_type']);
            @$db->Connect( $cdbs['db_host'], $cdbs['db_login'], $cdbs['db_passwd'], '' );

            if (!$db->IsConnected()) {
                $status++;
                printmsg("INFO => Unable to connect to server '{$cdbs['db_host']}'. ".$db->ErrorMsg(),0);
                $text .= "[{$cname}] Failed to connect as '{$cdbs['db_login']}'. ERROR: ".$db->ErrorMsg();
            } else {
                if ($db->SelectDB($cdbs['db_database'])) {
                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'version'");
                    $array = $rs->FetchRow();
                    $curr_ver = $array['value'];

                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'upgrade_index'");
                    $array = $rs->FetchRow();
                    $upgrade_index = $array['value'];

                    $levelinfo = $upgrade_index;

                    if ($upgrade_index < 8) { $levelinfo = "Must upgrade to at least v09.09.15 first!\n"; }
                } else {
                    $status++;
                    $text .= " [{$cname}] Failed to select DB '{$cdbs['db_database']}'. ERROR: ".$db->ErrorMsg();
                }
            }
            // Close the database connection
            @$db->Close();


            $text .= "{$cname}		{$cdbs['db_type']}		{$cdbs['db_host']}	{$cdbs['db_database']}	{$curr_ver}	{$levelinfo}\n";
        }

    }


    if ($status == 0) {
      echo $text."\n";
      $upgrade = promptUser("Perform the upgrade? ", 'y/N');
      $text = '';
    } else {
        $text .= <<<EOL
            There was an error determining database context versions. Please correct them before proceeding. \n\nCheck that the content of your database configuration file '{$dbconffile}' is accurate and that the databases themselves are configured properly.\n\n{$err_txt}\n
EOL;
    }

  }


  $dbtype = 'mysqli'; $adotype = $dbtype;

// If they have selected to keep the tables then remove the run_install file
if ($upgrade == 'Y' or $upgrade == 'y') {

    // Loop through each context and upgrade the Databases within
    foreach(array_keys($ona_contexts) as $cname) {

        foreach($ona_contexts[$cname]['databases'] as $cdbs) {
            printmsg("INFO => [{$cname}/{$cdbs['db_host']}] Performing an upgrade.",0);

            // Make an initial connection to a DB server without specifying a database
            $db = ADONewConnection($adotype);
            @$db->NConnect( $cdbs['db_host'], $cdbs['db_login'], $cdbs['db_passwd'], '' );

            if (!$db->IsConnected()) {
                $status++;
                printmsg("INFO => Unable to connect to server '{$cdbs['db_host']}'. ".$db->ErrorMsg(),0);
                $text .= " [{$cname}] Failed to connect to '{$cdbs['db_host']}' as '{$cdbs['db_login']}'. ERROR: ".$db->ErrorMsg()."\n";
            } else {
                $db->Close();
                if ($db->NConnect( $database_host, $admin_login, $admin_passwd, $cdbs['db_database'])) {


                    // Get the current upgrade index if there is one.
                    $rs = $db->Execute("SELECT value FROM sys_config WHERE name like 'upgrade_index'");
                    $array = $rs->FetchRow();
                    $upgrade_index = $array['value'];

                    $text .= "[{$cname}/{$cdbs['db_host']}] Keeping your original data.\n";

                    // update existing tables in our database to match our baseline xml schema
                    // create a schema object and build the query array.
                    $schema = new adoSchema( $db );
                    // Build the SQL array from the schema XML file
                    $sql = $schema->ParseSchema($xmlfile_tables);
                    // Uncomment the following to display the raw SQL
                    #$text .= "----------\n".$schema->PrintSQL('TEXT')."\n---------\n";
                    // Execute the SQL on the database
                    if ($schema->ExecuteSchema( $sql ) == 2) {
                        $text .= "[{$cname}/{$cdbs['db_host']}] Upgrading tables within database '{$cdbs['db_database']}'.\n";
                        printmsg("INFO => [{$cname}/{$cdbs['db_host']}] Upgrading tables within database: {$cdbs['db_database']}",0);
                    } else {
                        $status++;
                        $text .= "There was an error upgrading tables. ERROR: ".$db->ErrorMsg()."\n";
                        printmsg("ERROR => There was an error processing tables: ".$db->ErrorMsg(),0);
                        break;
                    }





                    $script_text = '';
                    if ($upgrade_index == '') {
                        $text .= "[{$cname}/{$cdbs['db_host']}] Auto upgrades not yet supported. Please see docs/UPGRADES\n";
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
                                $script_text .= "Please go to a command prompt and execute 'php {$upgrade_phpfile}' manually to complete the upgrade!\n";
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
                                    $text .= "[{$cname}/{$cdbs['db_host']}] Processed XML update file.\n";

                                    // update index info in the DB
                                    $text .= "[{$cname}/{$cdbs['db_host']}] Upgraded from index {$upgrade_index} to {$new_index}.\n";
                                    // Update the upgrade_index element in the sys_config table
                                    if($db->Execute("UPDATE sys_config SET value='{$new_index}' WHERE name like 'upgrade_index'")) {
                                        $text .= "[{$cname}/{$cdbs['db_host']}] Updated DB upgrade_index variable to '{$new_index}'.\n";
                                    }
                                    else {
                                        $status++;
                                        $text .= "[{$cname}/{$cdbs['db_host']}] Failed to update upgrade_index variable in table 'sys_config'.\n";
                                        break;
                                    }
                                    $upgrade_index++;
                                } else {
                                    $status++;
                                    $text .= "[{$cname}/{$cdbs['db_host']}] Failed to process XML update file.\n".$db->ErrorMsg()."\n";
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
                            $text .= "[{$cname}/{$cdbs['db_host']}] Updated DB version variable to '{$new_ver}'.\n";
                        }
                        else {
                            $status++;
                            $text .= "[{$cname}/{$cdbs['db_host']}] Failed to update version info in table 'sys_config'.\n";
                        }
                    }
                } else {
                    $status++;
                    $text .= "[{$cname}/{$cdbs['db_host']}] Failed to select DB '{$cdbs['db_database']}'.<br><span style='font-size: xx-small;'>".$db->ErrorMsg()."</span><br>";
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
            $text .= "Upgraded database connection config file to new format.\n";
        }
    }


    if($status == 0) {
        $text .= $script_text;
        $text .= "Upgrade complete, you may start using OpenNetAdmin! Enjoy!\n";

        if (@file_exists($runinstall)) {
          if (!@unlink($runinstall)) {
            $text .= "Buuut.. Failed to delete the file '{$runinstall}'.\n";
            $text .= "Please remove '{$runinstall}' manually.\n";
          }
        }
    } else {
        $text .= "There was a fatal error. Upgrade may be incomplete. Fix the issue and try again\n";
    }

  echo $text."\n";
}


}





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
                // Uncomment the following to display the raw SQL
                #$text .= "----------\n".$schema->PrintSQL('TEXT')."\n---------\n";
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


}



// Print out the text to the end user
echo $text;



if ($upgrademain != '') {
    print $upgrademain;
    print $main;
    print $blankmain;
} else {
    print $main;
}






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

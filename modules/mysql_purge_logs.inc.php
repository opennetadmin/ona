<?
// DON'T put whitespace at the beginning or end of this file!!!



///////////////////////////////////////////////////////////////////////
//  Function: mysql_purge_logs (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = mysql_purge_logs('slaves=STRING');
///////////////////////////////////////////////////////////////////////
function mysql_purge_logs($options="user=root") {
    global $conf, $self, $ona_db;
    printmsg('DEBUG => mysql_purge_logs('.$options.') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['slaves']) {
        $self['error'] = 'ERROR => Insufficient parameters';
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1,
<<<EOM

mysql_purge_logs-v{$version}
Connects to a specified list of MySQL slave servers, checks where they are
in reading/replicating the master server's binary logs, and deletes logs
from the associated master(s) which are no longer needed by any slave system.

A list of slave servers is supplied as input, and master servers are detected
automatically.

  Synopsis: mysql_purge_logs [KEY=VALUE]

  Required:
    slaves=NAME[,NAME ...]    list of slave server(s) to connect to
  
  Optional:
    user=NAME                 mysql username (default: root)
    password=STRING           mysql password (default: blank)

\n

EOM

        ));
    }

    // Split out the list of slave servers into an array (comma-delimited).
    $slaves = preg_split($options['slaves'], '/,/', -1, PREG_SPLIT_NO_EMPTY);

    // Now we begin...
    $masters = array();
    foreach ($slaves as $slave_host) {
        if(!$slave_host or $slave_host == "")
            continue;
        $dbh = db_connect('mysql', $slave_host, $options['user'], $options['password'], 'mysql');
        if(!$dbh)
            continue;

        // Find out this slave's replication status.
        $q = "show slave status;";
        $rs = $dbh->Execute($q);
        $array = $rs->FetchRow();

        // Check if our master is listed, and if so, make sure the oldest
        // binary logfile (by name) is stored in the array.
        $matched = 0;
        foreach($masters as $host => $binlog) {
            if($host == $array['Master_Host'] && $binlog > $array['Master_Log_File']) {
                $masters['$host'] = $array['Master_Log_File'];
                $matched = 1;
                break;
            }
        }

        // If our master wasn't listed, then create a new entry.
        if($matched == 0)
            $masters[$array['Master_Host']] = $array['Master_Log_File'];
    }


    // Now the "output" step...
    $retval_string = "";
    $retval_errlvl = 0;
    foreach($masters as $host => $binlog) {
        $dbh = db_connect('mysql', $host, $options['user'], $options['password'], 'mysql');
        if(!$dbh) {
            $self['error'] .= "ERROR => Could not connect to host '{$host}' to execute query. Skipping.\n";
            $retval_errlvl = 2;
            continue;
        }
        $q = "purge master logs to '{$binlog}'";
        $rs = $dbh->Execute($q);
	$error = $dbh->ErrorMsg();

        // Report any errors
        if ($rs === false or $error) {
            $self['error'] .= 'ERROR => SQL query on host {$host} failed: ' . $error . "\n";
            $retval_errlvl = 2;
        } else {
            $retval_string .= "Successfully executed ({$q}) on host '{$host}'.\n";
        }
    }

    // Return our results, as success strings and (perhaps) error strings.
    return(array($retval_errlvl, $retval_string));
}





///////////////////////////////////////////////////////////////////////
//  Function: db_connect ($db_type, $host, $user, $pass, $database)
//
//  Input Options:
//    $db_type  = name of database driver to use (e.g. 'mysql')
//    $host     = database hostname
//    $user     = database user id
//    $pass     = password (if any) to go with user id
//    $database = which database to connect to
//
//  Output:
//    Returns a single object:
//      1. An object representing the filehandle connected to the selected
//         database.
//
//  Example: <DB_HANDLE> = db_connect('mysql','localhost','root','','somedb');
///////////////////////////////////////////////////////////////////////

function db_connect($db_type, $host, $user, $pass, $database) {
    $object = NewADOConnection($db_type);
    $connected = 0;
    for ($a = 1; $a <= 5 and $connected == 0; $a++) {
        $ok1 = $object->PConnect($host, $user, $pass, $database);
        $ok2 = $object->IsConnected();
        $ok3 = $object->ErrorMsg();
        // If the connection didn't work, bail.
        if (!$ok1 or !$ok2 or $ok3) {  printmsg("ERROR => {$db_type} DB connection failed: " . $object->ErrorMsg(), 1); }
        // Otherwise exit the for loop.
        else { $connected = 1; }
    }

    // If it still isn't connected, return an error.
    if ($connected == 0) {
        printmsg("ERROR => {$db_type} DB connection failed after 5 tries!  Maybe server is down? Error: " . $object->ErrorMsg());
    }

    return $object;
}

?>
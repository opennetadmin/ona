<?php

/*
 * This file contains settings and functions used for database access
 * for the Datacom "modules" and configuration management engine.
 * Not all functions should be in here, but functions that are commonly
 * used are encouraged to be in here to centralize code.
 *
 * WARNING: Don't put whitespace at the beginning or end of include files!
 *
 */



/* ADODB Global Variables (shouldn't need changed) */

// You can control the associative fetch case for certain drivers which
// behave differently. For the sybase, oci8po, mssql, odbc and ibase
// drivers and all drivers derived from them, ADODB_ASSOC_CASE will by
// default generate recordsets where the field name keys are lower-cased.
// Use the constant ADODB_ASSOC_CASE to change the case of the keys.
// There are 3 possible values:
//   0 = assoc lowercase field names. $rs->fields['orderid']
//   1 = assoc uppercase field names. $rs->fields['ORDERID']
//   2 = use native-case field names. $rs->fields['OrderID'] -- this is the default since ADOdb 2.90
//
// To use it, declare it before you incldue adodb.inc.php.
// define('ADODB_ASSOC_CASE', 0);


// Include the database abstraction library, ADODB.
// http://adodb.sourceforge.net/
require_once($conf['inc_adodb']);


// If the database driver API does not support counting the number of
// records returned in a SELECT statement, the function RecordCount()
// is emulated when the global variable $ADODB_COUNTRECS is set to true,
// which is the default. We emulate this by buffering the records, which
// can take up large amounts of memory for big recordsets. Set this variable
// to false for the best performance. This variable is checked every time
// a query is executed, so you can selectively choose which recordsets to count.
$ADODB_COUNTRECS = 1;

// If you are using recordset caching, this is the directory to save your
// recordsets in. Define this before you call any caching functions such
// as CacheExecute( ). We recommend setting register_globals=off in php.ini
// if you use this feature for security reasons.
//
// If you are using Unix and apache, you might need to set your cache directory
// permissions to something similar to the following:
// chown -R apache /path/to/adodb/cache
// chgrp -R apache /path/to/adodb/cache
$ADODB_CACHE_DIR = '/tmp';

// Determines whether to right trim CHAR fields (and also VARCHAR for ibase/firebird).
// Set to true to trim. Default is false. Currently works for oci8po, ibase and
// firebird drivers. Added in ADOdb 4.01.
$ADODB_ANSI_PADDING_OFF = false;

// Determines the language used in MetaErrorMsg(). The default is 'en', for English.
// To find out what languages are supported, see the files in adodb/lang/adodb-$lang.inc.php,
// where $lang is the supported langauge.
$ADODB_LANG = 'en';

// This is a global variable that determines how arrays are retrieved by
// recordsets. The recordset saves this value on creation (eg. in Execute( )
// or SelectLimit( )), and any subsequent changes to $ADODB_FETCH_MODE have
// no affect on existing recordsets, only on recordsets created in the future.
// The following constants are defined:
//   define('ADODB_FETCH_DEFAULT',0);
//   define('ADODB_FETCH_NUM',1);
//   define('ADODB_FETCH_ASSOC',2);
//   define('ADODB_FETCH_BOTH',3);
// If no fetch mode is predefined, the fetch mode defaults to ADODB_FETCH_DEFAULT.
// The behaviour of this default mode varies from driver to driver, so do not rely
// on ADODB_FETCH_DEFAULT. For portability, we recommend sticking to ADODB_FETCH_NUM
// or ADODB_FETCH_ASSOC. Many drivers do not support ADODB_FETCH_BOTH.
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;






///////////////////////////////////////////////////////////////////////
//  Function: db_pconnect($context_type, $contect_name)
//
//  Establishes a persistent connection to the database as specified
//  by $context_type and $context_name.  Uses the global DB context
//  definitions stored in $db_context(), as well as $conf['dev_mode'].
//  This function will try up to 5 times to get the connection working.
//  This was a necessary function, because quite regularly we were
//  getting Oracle errors when ADODB/PHP thought it was connected to
//  the database when it really wasn't.  Good ole Oracle ;-)
//
//  Returns an ADODB database handle object
///////////////////////////////////////////////////////////////////////
function db_pconnect($type, $name) {
    global $conf;
    global $self;
    global $db_context;

    // Determine production/development
    $self['dev_mode'] = 'development';
    if ($conf['dev_mode'] == 0) {
        $self['dev_mode'] = 'production';
    }

    // Get info from $db_context[]
    $self['db_type']     = $db_context[$type] [$name] [$self['dev_mode']] ['db_type'];
    $self['db_host']     = $db_context[$type] [$name] [$self['dev_mode']] ['db_host'];
    $self['db_login']    = $db_context[$type] [$name] [$self['dev_mode']] ['db_login'];
    $self['db_passwd']   = $db_context[$type] [$name] [$self['dev_mode']] ['db_passwd'];
    $self['db_database'] = $db_context[$type] [$name] [$self['dev_mode']] ['db_database'];
    $self['db_debug']    = $db_context[$type] [$name] [$self['dev_mode']] ['db_debug'];

    // Create a new ADODB connection object
    $object = NewADOConnection($self['db_type']);
    $object->debug = $self['db_debug'];

    $connected = 0;
    for ($a = 1; $a <= 5 and $connected == 0; $a++) {
        $ok1 = $object->PConnect($self['db_host'], $self['db_login'], $self['db_passwd'], $self['db_database']);
        $ok2 = $object->IsConnected();
        $ok3 = $object->ErrorMsg();
        // If the connection didn't work, bail.
        if (!$ok1 or !$ok2 or $ok3) {  printmsg("ERROR => {$self['db_type']} DB connection failed: " . $object->ErrorMsg(), 1); }
        // Otherwise exit the for loop.
        else { $connected = 1; }
    }

    // If it still isn't connected, return an error.
    if ($connected == 0) {
        printmsg("ERROR => {$self['db_type']} DB connection failed after 5 tries!  Maybe server is down? Error: " . $object->ErrorMsg());
    }

    return $object;
}








// Note: Added $onadb as a global in hopes to use it instead of $mysql throughout
// the code.  could be confusing if we end up using other backends and everything still
// says mysql on it.  Should remove this global for mysql at some point but I'm
// leaving it for now so crap still works and Brandon knows that I did it.

// (Re)Connect to the DB
global $onadb, $mysql;
$onadb = $mysql = db_pconnect('mysql', $conf['mysql_context']);






///////////////////////////////////////////////////////////////////////
//  Function: get_content(string $name)
//
//  Input:
//    $name
//      The name of the content to load and display from the 'content'
//      mysql table.
//
//  Output:
//    Returns the text from the 'text' field of the specified content
//    record identified by $name.
///////////////////////////////////////////////////////////////////////
function get_content($name) {
    global $mysql;

    // Debugging
    printmsg("DEBUG => get_content($name) called", 3);

    // Get the content to be displayed on this page
    list($status, $rows, $content) = db_get_record($mysql, 'content', array('name' => $name));

    // Build an edit link if they're an author
    $edit = "";
    if ($_SESSION['auth']['user']['admin'] == 1) {
        $edit = <<<EOL
<br>
[<span onClick="xajax_window_submit('content', 'name=>{$name}', 'editor');" style="color: #4B42FF; text-decoration: underline; cursor: pointer; font-size: smaller;">edit</span>]&nbsp;&nbsp;
EOL;
    }

    // If there wasn't content, tell them.
    if ($status or !$rows)
        return("<br><font color=\"red\"><b>Content could not be loaded, please try back soon!</b></font><br>\n" . $edit);
    else
        return($content['text'] . $edit);
}












///////////////////////////////////////////////////////////////////////
//  Function: save_users_acl_in_session()
//
//  Loads a user's ACL into $_SESSION['auth']['acl']...
//  Requires that $_SESSION['auth']['user_id'] is already set.
//  This function does not make sure they have a valid username,
//  call securePage() first.
//
//  Returns 0 on success, 1 on failure.
//
///////////////////////////////////////////////////////////////////////
function save_users_acl_in_session() {
    global $mysql;

    // If we've already saved their acl in the session, don't do anything.
    if ($_SESSION['auth']['acl']['_done_'] == 1) {
        return(0);
    }

    // Make sure we have a user_id in the session
    if (!$_SESSION['auth']['user']['id']) {
        return(1);
    }

    // Select all the user's acl's and add them to $_SESSION['auth']['acl']
    $i = 0;
    do {
        // Loop through each permission the user has
        list($status, $rows, $acl) = db_get_record($mysql, 'acl', array('user_id' => $_SESSION['auth']['user']['id']));
        if (!$rows) { break; }
        $i++;

        // Get the permission's name, and save it in $_SESSION['auth']['acl']
        list($status, $rows_perm, $perm) = db_get_record($mysql, 'permissions', array('id' => $acl['perm_id']));
        if ($rows_perm) {
            $_SESSION['auth']['acl'][$perm['name']] = 1;
        }

    } while ($i < $rows);

    $_SESSION['auth']['acl']['_done_'] = 1;

}













///////////////////////////////////////////////////////////////////////
//  Function: acl_add($user_id, $perm_name)
//
//  Returns 0 on success, 1 on failure.
//  $self['error'] has error messages
//
///////////////////////////////////////////////////////////////////////
function acl_add($user_id, $perm_name) {
    global $mysql;

    // Find the perm_id for the requested perm_name
    list($status, $rows, $perm) = db_get_record($mysql, 'permissions', array('name' => $perm_name));
    if ($status or !$perm['id']) { $self['error'] = "acl_add() ERROR => Invalid permission requested"; return(1); }

    // See if they already have that permission
    list($status, $rows, $acl) = db_get_record($mysql, 'acl', array('perm_id' => $perm['id'], 'user_id' => $_SESSION['auth']['user']['id']));
    if ($status or $acl['id']) { $self['error'] = "acl_add() ERROR => User already has that permission"; return(1); }

    // Add the ACL entry
    list($status, $rows) = db_insert_record($mysql, 'acl', array('perm_id' => $perm['id'], 'user_id' => $_SESSION['auth']['user']['id']));
    if ($status or !$rows) { $self['error'] = "acl_add() ERROR => SQL insert failed"; return(1); }
    $_SESSION['auth']['acl'][$perm_name] = $perm['id'];
    return(0);
}























///////////////////////////////////////////////////////////////////////
//              db_XXXX_record() FUNTCIONS FOLLOW                    //
///////////////////////////////////////////////////////////////////////





///////////////////////////////////////////////////////////////////////
//  Function: db_insert_record($dbh, string $table, array $insert)
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table  the table name to insert into.
//    $insert an associative array of KEY = VALUE pair(s) to insert
//            into table $table.  KEY is the column name, and VALUE
//            is the value to insert into that column.  Values do not
//            need to be quoted, they will be properly quoted before
//            being used in the SQL query.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were inserted (i.e. 1 on success, 0 on
//         error).  Again, if 0 rows were inserted an error message will
//         be stored in $self['error']
//
//  Example: list($status, $rows) = db_insert_record(
//                                      $mysql,
//                                      "HOSTS_B",
//                                      array('ID' => '12354',
//                                            'NAME' => 'test',
//                                            'CREATE_PTR' => 1,
//                                      )
//                                  );
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL insert failed
///////////////////////////////////////////////////////////////////////
function db_insert_record($dbh=0, $table="", $insert="") {
    global $self;
    $self['db_insert_record_count']++;

    // Debugging
    printmsg("DEBUG => db_insert_record(\$dbh, $table, \$insert) called", 3);

    // Return an error if insufficient input was received
    if ( (!$dbh) or (!$dbh->IsConnected()) or
         (!$table) or
         (!$insert) ) {
        $self['error'] = "ERROR => db_insert_record() received invalid input";
        printmsg($self['error'], 3);
        return(array(1, 0));
    }

    // Build the SQL query
    $q  = "INSERT INTO {$table} ( ";
    $first = 1;
    foreach (array_keys($insert) as $key) {
        if (!$first) { $q .= ', '; } $first = 0;
        $q .= $key;
    }
    $q .= " ) VALUES (";
    $first = 1;
    foreach (array_keys($insert) as $key) {
        if (!$first) { $q .= ', '; } $first = 0;
        $q .= $dbh->qstr($insert[$key]);
    }
    $q .= " )";

    // Run the SQL
    printmsg("DEBUG => db_insert_record() Running query: $q", 4);
    $ok = $dbh->Execute($q);
    $error = $dbh->ErrorMsg();

    // Report any errors
    if ($ok === false or $error) {
        $self['error'] = 'ERROR => SQL INSERT failed: ' . $error . "\n";
        return(array(2, 0));
    }

    // Otherwise return success
    printmsg("DEBUG => db_insert_record() Insert was successful", 4);
    return(array(0, 1));
}















///////////////////////////////////////////////////////////////////////
//  Function: db_update_record($dbh, string $table, array/string $where, array $insert)
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table  the table name to query from.
//    $where  an associative array of KEY = VALUE pair(s) used to
//            locate and update the record you want.  If $where is
//            a string, that string is used as the WHERE clause of
//            the sql query instead of generating one from an array.
//            If you do this MAKE sure special characters are quoted
//            properly to avoid security issues or bugs.
//    $insert an associative array of KEY = VALUE pair(s) to update
//            in table $table.  KEY is the column name, and VALUE is
//            the value to insert into that column.  Values do not
//            need to be quoted, they will be properly quoted before
//            being used in the SQL query.
//
//  Output:
//    Updates a *single* record in the specified database table.
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were actually updated (i.e. will
//         always be 1 or 0.)  Note that even if 0 rows are updated
//         the exit status will still be 0 unless the SQL query fails.
//
//  Example: list($status, $rows) = db_update_record(
//                                      'HOSTS_B',
//                                      array('ID' => '12354'),
//                                      array('IP_ADDR' => '34252522533'),
//                                  );
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_update_record($dbh=0, $table="", $where="", $insert="") {
    global $self;
    $self['db_update_record_count']++;

    // Debugging
    printmsg("DEBUG => db_update_record(\$dbh, $table, \$where, \$insert) called", 3);

    // Return an error if insufficient input was received
    if ( (!$dbh) or (!$dbh->IsConnected()) or
         (!$table) or (!$where) or (!$insert) ) {
        $self['error'] = "ERROR => db_update_record() received invalid input";
        printmsg($self['error'], 3);
        return(array(1, 0));
    }

    // Build our $set variable
    $set = '';
    $and = '';
    foreach (array_keys($insert) as $key) {
        $set .= "{$and}{$key} = " . $dbh->qstr($insert[$key]);
        if (!$and) { $and = ", "; }
    }

    // Build the WHERE clause if $where is an array
    if (is_array($where)) {
        $where_str = '';
        $and = '';
        foreach (array_keys($where) as $key) {
            $where_str .= $and . $key . ' = ' . $dbh->qstr($where[$key]);
            if (!$and) { $and = " AND "; }
        }
    }
    // Otherwise we just use the string in $where
    else {
        $where_str = $where;
    }

    // Build the SQL query
    $q  = "UPDATE {$table} SET {$set} WHERE {$where_str}";

    // Execute the query
    printmsg("DEBUG => db_update_record() Running query: $q", 4);
    $rs = $dbh->Execute($q);

    // See if the query worked or not
    if ($rs === false) {
        $self['error'] = 'ERROR => SQL query failed: ' . $dbh->ErrorMsg();
        printmsg($self['error'], 3);
        return(array(2, 0));
    }

    // How many rows were affected?
    $rows = $dbh->Affected_Rows();
    if ($rows === false) { $rows = 0; }
    $rs->Close();

    // Return Success
    printmsg("DEBUG => db_update_record() Query updated {$rows} rows", 4);
    return(array(0, $rows));
}















///////////////////////////////////////////////////////////////////////
//  Function: db_delete_record($dbh, string $table, array/string $where)
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table  the table name to delete from.
//    $where  an associative array of KEY = VALUE pair(s) used to
//            locate and delete the record you want.  If $where is
//            a string, that string is used as the WHERE clause of
//            the sql query instead of generating one from an array.
//            If you do this MAKE sure special characters are quoted
//            properly to avoid security issues or bugs.
//
//  Output:
//    Deletes a *single* record from the database.
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were actually deleted (i.e. will
//         always be 1 or 0.)  Note that even if 0 rows are updated
//         the exit status will still be 0 unless the SQL query fails.
//
//  Example: list($status, $rows) = db_delete_record(
//                                      'HOSTS_B',
//                                      array('ID' => '12354'),
//                                  );
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_delete_record($dbh=0, $table="", $where="") {
    global $self;
    $self['db_delete_record_count']++;

    // Debugging
    printmsg("DEBUG => db_delete_record(\$dbh, $table, \$where) called", 3);

    // Return an error if insufficient input was received
    if ( (!$dbh) or (!$dbh->IsConnected()) or
         (!$table) or (!is_array($where)) ) {
        $self['error'] = "ERROR => db_delete_record() received invalid input";
        printmsg($self['error'], 3);
        return(array(1, 0));
    }

    // Build the WHERE clause if $where is an array
    if (is_array($where)) {
        $where_str = '';
        $and = '';
        foreach (array_keys($where) as $key) {
            $where_str .= $and . $key . ' = ' . $dbh->qstr($where[$key]);
            if (!$and) { $and = " AND "; }
        }
    }
    // Otherwise we just use the string in $where
    else {
        $where_str = $where;
    }

    // Build the SQL query
    $q  = "DELETE FROM {$table} WHERE {$where_str} LIMIT 1";

    // Execute the query
    $rs = $dbh->Execute($q);

    // See if the query worked or not
    if ($rs === false) {
        $self['error'] = 'ERROR => SQL query failed: ' . $dbh->ErrorMsg();
        printmsg($self['error'], 3);
        return(array(2, 0, array()));
    }

    // How many rows were affected?
    $rows = $dbh->Affected_Rows();
    if ($rows === false) { $rows = 0; }
    $rs->Close();

    // Return Success
    printmsg("DEBUG => db_delete_record() Query deleted {$rows} row(s)", 4);
    return(array(0, $rows));
}
















///////////////////////////////////////////////////////////////////////
//  Function: db_get_record($dbh,
//                          string $table,
//                          array/string $where,
//                          string $order)
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table the table name to query from.
//    $where an associative array of KEY = VALUE pair(s) used to
//           locate and return the record you want.  If $where is
//           a string, that string is used as the WHERE clause of
//           the sql query instead of generating one from an array.
//           If you do this MAKE sure special characters are quoted
//           properly to avoid security issues or bugs.
//    $order actual SQL to use in the ORDER BY clause.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows (n) that match values in $where, or 0 on no matches.
//      3. An associative array of a record from the $table table
//         where the values in $where match.  When more than one record is
//         returned from the DB, the first record is returned on the
//         first call.  Each subsequent call with the same table and
//         parameters will cause the function to return the next record.
//         When 'n' records are found, and the function is called 'n+1'
//         times, it loops and the first record is returned again.
//
//  Notes:
//    If you want to "reset" the row offset for a particular query and
//    make sure that your next query is NOT cached, set this global
//    variable before calling db_get_record():
//      $self['db_get_record']['reset_cache'] = 1;
//    These are also globally configurable, but you won't need to set
//    these after each call to db_get_record().
//      $self['db_get_record']['min_rows_to_cache']
//      $self['db_get_record']['secs_to_cache']
//
//
//  Example: list($status, $rows, $record) = db_get_record(
//                                               "HOSTS_B",
//                                               array('ID' => '12354'),
//                                               "PRIMARY_DNS_ZONE_ID ASC"
//                                           );
//  Example: list($status, $rows, $record) = db_get_record(
//                                               "HOSTS_B",
//                                               "ID = '12354'",
//                                               "PRIMARY_DNS_ZONE_ID ASC"
//                                           );
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_get_record($dbh=0, $table="", $where="", $order="") {
    global $self;
    $self['db_get_record_count']++;

    // Debugging
    printmsg("DEBUG => db_get_record(\$dbh, \$where, $table, $order) called", 3);

    // Return an error if insufficient input was received
    if ( (!$dbh) or (!$dbh->IsConnected()) or
         (!$table) or (!$where) ) {
        $self['error'] = "ERROR => db_get_record() received invalid input";
        printmsg($self['error'], 3);
        return(array(1, 0, array()));
    }

    // Build the WHERE clause if $where is an array
    if (is_array($where)) {
        $where_str = '';
        $and = '';
        foreach (array_keys($where) as $key) {
            $where_str .= $and . $key . ' = ' . $dbh->qstr($where[$key]);
            if (!$and) { $and = " AND "; }
        }
    }
    // Otherwise we just use the string in $where
    else {
        $where_str = $where;
    }

    // Build the SQL query
    $q = 'SELECT * ' .
         "FROM {$table} " .
         "WHERE {$where_str} ";
    if ($order) {
        $q .= "ORDER BY {$order}";
    }


    // Caching - our Query Cache policy is this:
    //   1) If this is a new query for $table, don't cache
    //   2) If this isn't a new query for $table and the recordset
    //      has more than 20 records, then cache it and use the
    //      cache for subsequent calls with the same query.
    $use_cache = 0;
    if ($self['cache']["db_get_{$table}_record"]['q'] != $q) {
        // If it's a new query, or , reset row cache and don't use cache
        $self['cache']["db_get_{$table}_record"]['q'] = $q;
        $self['cache']["db_get_{$table}_record"]['row'] = 0;

        printmsg("DEBUG => db_get_record() Row offset reset for table: {$table}", 5);
    }
    // If reset_cache == 1 we don't use cache, and go to row 0
    else if (isset($self['db_get_record']['reset_cache']) and ($self['db_get_record']['reset_cache'])) {
        $self['db_get_record']['reset_cache'] = 0;
        $self['cache']["db_get_{$table}_record"]['row'] = 0;
    }
    // It's the same query again, decide if we should enable adodb disk cache
    else {
        // Set a detault unless it's already set
        if (!$self['db_get_record']['min_rows_to_cache']) {
            $self['db_get_record']['min_rows_to_cache'] = 20;
        }

        // If there are enough records (or were last time we ran this query), lets cache the query this time.
        if ($self['cache']["db_get_{$table}_record"]['rows'] >= $self['db_get_record']['min_rows_to_cache']) {
            $use_cache = 1;
        }

        // Increment the row offset, so we know which row to return
        $self['cache']["db_get_{$table}_record"]['row']++;
    }


    // Select the record from the DB, don't cache results
    if ($use_cache == 0) {
        printmsg("DEBUG => db_get_record() running query: {$q}", 5);
        $rs = $dbh->Execute($q);
    }
    // Select the record from the DB, cache results
    else {
        // Set a detault unless it's already set
        if (!$self['db_get_record']['secs_to_cache']) {
            $self['db_get_record']['secs_to_cache'] = 60;
        }

        printmsg("DEBUG => db_get_record() running (cached) query: {$q}", 5);
        $rs = $dbh->CacheExecute($self['db_get_record']['secs_to_cache'], $q);
    }


    // See if the query worked or not
    if ($rs === false) {
        $self['error'] = 'ERROR => SQL query failed: ' . $dbh->ErrorMsg();
        printmsg($self['error'], 3);
        return(array(2, 0, array()));
    }


    // Save the number of rows for use later
    $rows = $self['cache']["db_get_{$table}_record"]['rows'] = $rs->RecordCount();


    // If there were no rows, return 0 rows
    if (!$rows) {
        // Query returned no results
        printmsg("DEBUG => db_get_record() Query returned no results", 4);
        return(array(0, 0, array()));
    }

    // If there's more than one record
    else if ( ($rows > 1) and ($self['cache']["db_get_{$table}_record"]['row']) ) {
        // If we need to loop back to row 0, lets do that
        if ($self['cache']["db_get_{$table}_record"]['row'] >= $rows) {
            $self['cache']["db_get_{$table}_record"]['row'] = 0;
        }
        // If it's the same query as last time we need to "Move" to right row
        else {
            $rs->Move($self['cache']["db_get_{$table}_record"]['row']);
        }
    }

    // Return the row
    printmsg("DEBUG => db_get_record() Returning record " . ($self['cache']["db_get_{$table}_record"]['row'] + 1) . " of " . $rows, 4);
    $array = $rs->FetchRow();
    $rs->Close();
    return(array(0, $rows, $array));
}









///////////////////////////////////////////////////////////////////////
//  Function: db_get_records($dbh,
//                           string $table,
//                           array/string $where,
//                           [string $order],
//                           [int $rows=-1],
//                           [int $offset=-1]
//                          )
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table  the table name to query from.
//    $where  an associative array of KEY = VALUE pair(s) used to
//            locate and return the record you want.  If $where is
//            a string, that string is used as the WHERE clause of
//            the sql query instead of generating one from an array.
//            If you do this MAKE sure special characters are quoted
//            properly to avoid security issues or bugs.
//    $order  actual SQL to use in the ORDER BY clause.
//    $rows   the number of rows to return. -1 = all rows.
//            NOTE: if $rows is 0, the function will do a SELECT COUNT(*)
//            and return the proper number of total rows.
//    $offset retrieve rows starting with $offset. $offset is 0 based.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows (n) that match values in $where, or 0 on no matches.
//      3. An array of arrays.  Each sub-array is an associative array of
//         a record from the $table table where the values in $where match.
//
//  Example: list($status, $rows, $records) = db_get_record(
//                                                $oracle,
//                                                'HOSTS_B',
//                                                array('ID' => '12354'),
//                                                'PRIMARY_DNS_ZONE_ID ASC',
//                                                '25',
//                                                '50'
//                                            );
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_get_records($dbh=0, $table="", $where="", $order="", $rows=-1, $offset=-1) {
    global $self;
    $self['db_get_records_count']++;

    // Debugging
    printmsg("DEBUG => db_get_records(\$dbh, \$where, $table, $order, $rows, $offset) called", 3);

    // Return an error if insufficient input was received
    if ( (!$dbh) or (!$dbh->IsConnected()) or
         (!$table) or (!$where) ) {
        $self['error'] = "ERROR => db_get_records() received invalid input";
        printmsg($self['error'], 3);
        return(array(1, 0, array()));
    }

    // Build the WHERE clause if $where is an array
    if (is_array($where)) {
        $where_str = '';
        $and = '';
        foreach (array_keys($where) as $key) {
            $where_str .= $and . $key . ' = ' . $dbh->qstr($where[$key]);
            if (!$and) { $and = " AND "; }
        }
    }
    // Otherwise we just use the string in $where
    else {
        $where_str = $where;
    }

    // Return the 0 records, but number of total rows the query would have returned
    // if they requested 0 rows.
    $select = 'SELECT * ';
    if ($rows == 0) {
        $select = 'SELECT COUNT(*) AS COUNT ';
        $rows   = -1;
        $offset = -1;
        $order  = '';
    }

    // Build the SQL query
    $q = $select .
         "FROM {$table} " .
         "WHERE {$where_str} ";
    if ($order) {
        $q .= "ORDER BY {$order}";
    }


    // Select the records from the DB
    printmsg("DEBUG => db_get_records() running query: {$q}", 5);
    $rs = $dbh->SelectLimit($q, $rows, $offset);


    // See if the query worked or not
    if ($rs === false) {
        $self['error'] = 'ERROR => SQL query failed: ' . $dbh->ErrorMsg();
        printmsg($self['error'], 3);
        return(array(2, 0, array()));
    }


    // Save the number of rows for use later
    $rows = $rs->RecordCount();
    if ($select == 'SELECT COUNT(*) AS COUNT ') {
        $record = $rs->FetchRow();
        $rows = $record['COUNT'];
    }

    // If there were no rows, return 0 rows
    if (!$rows) {
        // Query returned no results
        printmsg("DEBUG => db_get_records() Query returned no results", 4);
        return(array(0, 0, array()));
    }

    // Loop and save each row to $recordset
    $recordset = array();
    while (!$rs->EOF) {
        $recordset[] = $rs->FetchRow();
    }

    // Return the row
    printmsg("DEBUG => db_get_records() Returning records", 4);
    $rs->Close();
    return(array(0, $rows, $recordset));
}










?>
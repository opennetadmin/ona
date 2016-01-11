<?php
/* WARNING: Don't put whitespace at the beginning or end of include files! */



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
//  Function: db_pconnect($context_type, $context_name)
//
//  Establishes a persistent connection to the database as specified
//  by $context_name.  Uses the global DB context
//  definitions stored in $ona_contexts().
//  This function will try up to 5 times to get the connection working.
//  This was a necessary function, because quite regularly we were
//  getting Oracle errors when ADODB/PHP thought it was connected to
//  the database when it really wasn't.  Good ole Oracle ;-)
//
//  Returns an ADODB database handle object
///////////////////////////////////////////////////////////////////////
function db_pconnect($type, $context_name) {
    global $conf, $base, $self, $ona_contexts;
    global $db_context;


    // Get info from old $db_context[] array if ona_contexts does not exist
    // this is transitional, hopefully I can remove this part soon.
    if (!is_array($ona_contexts) and is_array($db_context)) {
        $type='mysqlt';
        $ona_contexts[$context_name]['databases']['0']['db_type']     = $db_context[$type] [$context_name] ['primary'] ['db_type'];
        $ona_contexts[$context_name]['databases']['0']['db_host']     = $db_context[$type] [$context_name] ['primary'] ['db_host'];
        $ona_contexts[$context_name]['databases']['0']['db_login']    = $db_context[$type] [$context_name] ['primary'] ['db_login'];
        $ona_contexts[$context_name]['databases']['0']['db_passwd']   = $db_context[$type] [$context_name] ['primary'] ['db_passwd'];
        $ona_contexts[$context_name]['databases']['0']['db_database'] = $db_context[$type] [$context_name] ['primary'] ['db_database'];
        $ona_contexts[$context_name]['databases']['0']['db_debug']    = $db_context[$type] [$context_name] ['primary'] ['db_debug'];
        $ona_contexts[$context_name]['databases']['1']['db_type']     = $db_context[$type] [$context_name] ['secondary'] ['db_type'];
        $ona_contexts[$context_name]['databases']['1']['db_host']     = $db_context[$type] [$context_name] ['secondary'] ['db_host'];
        $ona_contexts[$context_name]['databases']['1']['db_login']    = $db_context[$type] [$context_name] ['secondary'] ['db_login'];
        $ona_contexts[$context_name]['databases']['1']['db_passwd']   = $db_context[$type] [$context_name] ['secondary'] ['db_passwd'];
        $ona_contexts[$context_name]['databases']['1']['db_database'] = $db_context[$type] [$context_name] ['secondary'] ['db_database'];
        $ona_contexts[$context_name]['databases']['1']['db_debug']    = $db_context[$type] [$context_name] ['secondary'] ['db_debug'];
        $ona_contexts[$context_name]['description']   = 'Default data context';
        $ona_contexts[$context_name]['context_color'] = '#D3DBFF';
    }

    // check if the context name passed in is in our array or not
    if (!isset($ona_contexts[$context_name])) {
        setcookie("ona_context_name", $conf['default_context']);
        printmsg("ERROR => Unable to find context name '{$context_name}' in the ona_contexts configuration. Reverting back to '{$conf['default_context']}' context.",0);
        echo "ERROR => Unable to find context name '{$context_name}' in the ona_contexts configuration.  Please check {$base}/local/config/database_settings.inc.php is configured properly.  Reverting back to '{$conf['default_context']}' context.";
        return $object;
    }


    // Populate basic context info into the self storage array
    $self['context_name']  = $context_name;
    $self['context_desc']  = $ona_contexts[$context_name]['description'];
    $self['context_color'] = $ona_contexts[$context_name]['context_color'];

    // loop through each context in the array and try and connect to the databases
    // we will use the first DB we connect to for the specified context
    // ONA will NOT connect to multiple databases at once.
    foreach ((array)$ona_contexts[$context_name]['databases'] as $db) {

        $self['db_type']     = $db['db_type'];
        $self['db_host']     = $db['db_host'];
        $self['db_login']    = $db['db_login'];
        $self['db_database'] = $db['db_database'];
        $self['db_debug']    = $db['db_debug'];

        // Create a new ADODB connection object
        $object = NewADOConnection($self['db_type']);
        $object->debug = $self['db_debug'];
        // MP: this does not seem to be a consistant setting for all adodb drivers
        // leaving it set for those that can use it.
        $object->charSet = $conf['charset'];

        // Try connecting to the database server
        $connected = 0;
        for ($a = 1; $a <= 5 and $connected == 0; $a++) {
            $ok1 = $object->PConnect($self['db_host'], $self['db_login'], $db['db_passwd'], $self['db_database']);
            $ok2 = $object->IsConnected();
            $ok3 = $object->ErrorMsg();

            // If the connection didn't work, bail.
            if (!$ok1 or !$ok2 or $ok3)
                printmsg("ERROR => {$self['db_type']} DB connection failed: " . $object->ErrorMsg(), 0);
            // Otherwise return the object.
            else {
                // MP: not sure how this behaves on other databases.. should work for mysql and postgres
                if ($conf['set_db_charset'])
                    $object->Execute("SET names '{$conf['charset']}'");
                return $object;
            }
        }
    }

    // If it still isn't connected, return an error.
    if ($connected == 0)
        printmsg("ERROR => {$self['db_type']} DB connection failed after 5 tries!  Maybe server is down?", 0);

    return $object;
}





/* MP: Brandons functions, not currently used here.



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
    global $onadb;

    // Debugging
    printmsg("DEBUG => get_content($name) called", 3);

    // Get the content to be displayed on this page
    list($status, $rows, $content) = db_get_record($onadb, 'content', array('name' => $name));

    // Build an edit link if they're an editor
    $edit = "";
    if (auth('editor')) {
        $edit = <<<EOL
<br/>
[<span onClick="xajax_window_submit('fckeditor', 'name=>{$name}', 'editor');" style="color: #4B42FF; text-decoration: underline; cursor: pointer; font-size: smaller;">edit</span>]&nbsp;&nbsp;
EOL;
    }

    // If there wasn't content, tell them.
    if ($status or !$rows)
        return("<br/><span style=\"color: red;\"><b>Content could not be loaded, please try back soon!</b></span><br/>\n" . $edit);
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
    global $onadb;

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
        list($status, $rows, $acl) = db_get_record($onadb, 'acl', array('user_id' => $_SESSION['auth']['user']['id']));
        if (!$rows) { break; }
        $i++;

        // Get the permission's name, and save it in $_SESSION['auth']['acl']
        list($status, $rows_perm, $perm) = db_get_record($onadb, 'permissions', array('id' => $acl['perm_id']));
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
    global $onadb;

    // Find the perm_id for the requested perm_name
    list($status, $rows, $perm) = db_get_record($onadb, 'permissions', array('name' => $perm_name));
    if ($status or !$perm['id']) { $self['error'] = "acl_add() ERROR => Invalid permission requested"; return(1); }

    // See if they already have that permission
    list($status, $rows, $acl) = db_get_record($onadb, 'acl', array('perm_id' => $perm['id'], 'user_id' => $_SESSION['auth']['user']['id']));
    if ($status or $acl['id']) { $self['error'] = "acl_add() ERROR => User already has that permission"; return(1); }

    // Add the ACL entry
    list($status, $rows) = db_insert_record($onadb, 'acl', array('perm_id' => $perm['id'], 'user_id' => $_SESSION['auth']['user']['id']));
    if ($status or !$rows) { $self['error'] = "acl_add() ERROR => SQL insert failed"; return(1); }
    $_SESSION['auth']['acl'][$perm_name] = $perm['id'];
    return(0);
}





*/

















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
//                                      "hosts",
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
    @$self['db_insert_record_count']++;

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
//                                      'hosts',
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
    @$self['db_update_record_count']++;

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
    if ($rows === false) { $rows = 0; $self['error'] = 'Update OK, no rows effected'; }
    $rs->Close();

    // Return Success
    printmsg("DEBUG => db_update_record() Query updated {$rows} rows", 4);
    return(array(0, $rows));
}















///////////////////////////////////////////////////////////////////////
//  Function: db_delete_records($dbh, string $table, array/string $where)
//
//  Input:
//    $dbh    an adodb connection object connected to a database
//    $table  the table name to delete from.
//    $where  an associative array of KEY = VALUE pair(s) used to
//            locate and delete the record(s) you want.  If $where is
//            a string, that string is used as the WHERE clause of
//            the sql query instead of generating one from an array.
//            If you do this MAKE sure special characters are quoted
//            properly to avoid security issues or bugs.
//
//  Output:
//    Deletes records from the database.
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were actually deleted .  Note that even
//         if 0 rows are updated the exit status will still be 0 unless the
//         SQL query fails.
//
//  Example: list($status, $rows) = db_delete_records(
//                                      'hosts',
//                                      array('ID' => '12354'),
//                                  );
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_delete_records($dbh=0, $table="", $where="") {
    global $self;
    @$self['db_delete_records_count']++;

    // Debugging
    printmsg("DEBUG => db_delete_records(\$dbh, $table, \$where) called", 3);

    // Return an error if insufficient input was received
    if ( empty($dbh) or (!$dbh->IsConnected()) or
         empty($table) or empty($where) ) {
        $self['error'] = "ERROR => db_delete_records() received invalid input";
        printmsg($self['error'], 0);
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
    // The LIMIT 1 is only valid in MySQL, so we've removed it.  db_delete_record now deletes all the records that match
    // $q  = "DELETE FROM {$table} WHERE {$where_str} LIMIT 1";
    $q  = "DELETE FROM {$table} WHERE {$where_str}";

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
    printmsg("DEBUG => db_delete_records() Query deleted {$rows} row(s)", 4);
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
//                                               "hosts",
//                                               array('id' => '12354'),
//                                               "domain_id ASC"
//                                           );
//  Example: list($status, $rows, $record) = db_get_record(
//                                               "hosts",
//                                               "id = '12354'",
//                                               "domain_id ASC"
//                                           );
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or insufficient input
//    2  :: SQL query failed
///////////////////////////////////////////////////////////////////////
function db_get_record($dbh=0, $table="", $where="", $order="") {
    global $self;
    @$self['db_get_record_count']++;

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
    if (@$self['cache']["db_get_{$table}_record"]['q'] != $q) {
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
//                                                $onadb,
//                                                'hosts',
//                                                array('id' => '12354'),
//                                                'domain_id ASC',
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
    @$self['db_get_records_count']++;

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
























///////////////////////////////////////////////////////////////////////
//                   OTHER ONA FUNTCIONS FOLLOW                      //
///////////////////////////////////////////////////////////////////////








///////////////////////////////////////////////////////////////////////
//  Function: ona_insert_record(string $table, array $insert)
//
//  See documentation for db_insert_record() in functions_db.php
///////////////////////////////////////////////////////////////////////
function ona_insert_record($table="", $insert="") {
    global $onadb;
    return(db_insert_record($onadb, $table, $insert));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_update_record(string $table, array/string $where, array $insert)
//
//  See documentation for db_update_record() in functions_db.php
///////////////////////////////////////////////////////////////////////
function ona_update_record($table="", $where="", $insert="") {
    global $onadb;
    return(db_update_record($onadb, $table, $where, $insert));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_delete_record(string $table, array/string $where)
//
//  See documentation for db_delete_record() in functions_db.php
///////////////////////////////////////////////////////////////////////
function ona_delete_record($table="", $where="") {
    global $onadb;
    return(db_delete_record($onadb, $table, $where));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_get_record(array/string $where, string $table, string $order)
//
//  See documentation for db_get_record() in functions_db.php
///////////////////////////////////////////////////////////////////////
function ona_get_record($where="", $table="", $order="") {
    global $onadb;
    return(db_get_record($onadb, $table, $where, $order));
}











///////////////////////////////////////////////////////////////////////
//
//         THIS IS DOCUMENTATION FOR ALL OF THE FOLLOWING
//                ona_get_XXX_record FUNCTIONS
//
//  Function: ona_get_XXXXX_record(array $where)
//
//  Input:
//    $where is an associative array of KEY = VALUE pair(s) used to
//    locate and return the host record you want.
//    This puts you a little close to the actual DB, but it allows
//    a great amount of flexability.
//    Example input: ona_get_host_record(array('ID' => '12354'))
//
//  Output:
//    Returns a three part list:
//      1. Function exit status - 0 on success, non-zero on error.
//         When a non-zero exit status is returned a description of the
//         error will be stored in the global variable $self['error']
//      2. The number of rows ('n') that match values in $where, or
//         0 on no matches.
//      3. An associative array of a record from the DB table where the
//         values in $where match.  When more than one record is
//         returned from the DB, the first record is returned on the
//         first call.  Each subsequent call with the same parameters
//         will cause the function to return the next record.  When 'n'
//         records are found, and the function is called 'n+1' times,
//         it loops and the first record is returned again.
//
//  Example: list($status, $rows, $record) = ona_get_host_record(array('ID' => '12354'));
//
///////////////////////////////////////////////////////////////////////

// Returns some additional fields:
//   name        => the base hostname
//   fqdn        => the fqdn of the host (based on it's primary_dns_id)
//   domain_id   => domain id for the associated primary_dns_id
//   domain_name => domain name for the associated primary_dns_id's domain
function ona_get_host_record($array='', $order='') {
    list($status, $rows, $record) = ona_get_record($array, 'hosts', $order);
    list($status_dns, $rows_dns, $dns) = ona_get_dns_record(array('id' => $record['primary_dns_id']));
    $record['name'] = $dns['name'];
    $record['fqdn'] = $dns['fqdn'];
    $record['primary_interface_id'] = $dns['interface_id'];
    $record['dns_view_id'] = $dns['dns_view_id'];
    $record['domain_id'] = $dns['domain_id'];
    $record['domain_fqdn'] = $dns['domain_fqdn'];
    return(array($status + $status_dns, $rows, $record));
}

function ona_get_block_record($array='', $order='') {
    return(ona_get_record($array, 'blocks', $order));
}

function ona_get_location_record($array='', $order='') {
    return(ona_get_record($array, 'locations', $order));
}

function ona_get_interface_record($array='', $order='') {
    list($status, $rows, $record) = ona_get_record($array, 'interfaces', $order);
    if ($rows)
        $record['ip_addr_text'] = ip_mangle($record['ip_addr'], 'dotted');
    return(array($status, $rows, $record));
}

// Returns an additional "fqdn" field
function ona_get_domain_record($array='', $order='') {
    list($status, $rows, $record) = ona_get_record($array, 'domains', $order);
    if ($rows)
        $record['fqdn'] = ona_build_domain_name($record['id']);
    return(array($status, $rows, $record));
}

// Returns an additional "fqdn" field for some dns records
function ona_get_dns_record($array='', $order='') {
    list($status, $rows, $record) = ona_get_record($array, 'dns', $order);

    if ($record['type'] == 'A' or $record['type'] == 'TXT') {
        $record['fqdn'] = $record['name'].'.'.ona_build_domain_name($record['domain_id']);
        $record['domain_fqdn'] = ona_build_domain_name($record['domain_id']);
    }
    if ($record['type'] == 'CNAME') {
        $record['fqdn'] = $record['name'].'.'.ona_build_domain_name($record['domain_id']);
        $record['domain_fqdn'] = ona_build_domain_name($record['domain_id']);
    }
    return(array($status, $rows, $record));
}

function ona_get_dns_view_record($array='', $order='') {
    return(ona_get_record($array, 'dns_views', $order));
}

function ona_get_config_record($array='', $order='ctime DESC') {
    list($status, $rows, $record) = ona_get_record($array, 'configurations', $order);

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_config_type_record(array('id' => $record['configuration_type_id']));
    $status += $status_tmp;
    $record['config_type_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_config_type_record($array='', $order='') {
    return(ona_get_record($array, 'configuration_types', $order));
}

function ona_get_dhcp_failover_group_record($array) {
    return(ona_get_record($array, 'dhcp_failover_groups'));
}

function ona_get_custom_attribute_type_record($array) {
    return(ona_get_record($array, 'custom_attribute_types'));
}

function ona_get_custom_attribute_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'custom_attributes');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_custom_attribute_type_record(array('id' => $record['custom_attribute_type_id']));
    $status += $status_tmp;
    $record['name'] = $record_tmp['name'];
    $record['field_validation_rule'] = $record_tmp['field_validation_rule'];
    $record['failed_rule_text'] = $record_tmp['failed_rule_text'];
    $record['notes'] = $record_tmp['notes'];

    return(array($status, $rows, $record));
}

function ona_get_model_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'models');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_manufacturer_record(
                                                    array('id' => $record['manufacturer_id'])
                                                );
    $status += $status_tmp;
    $record['manufacturer_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_manufacturer_record($array) {
    return(ona_get_record($array, 'manufacturers'));
}

function ona_get_device_type_record($array) {
    return(ona_get_record($array, 'device_types'));
}

function ona_get_device_record($array) {
    return(ona_get_record($array, 'devices'));
}

function ona_get_role_record($array) {
    return(ona_get_record($array, 'roles'));
}

function ona_get_subnet_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'subnets');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
    $status += $status_tmp;
    $record['subnet_type_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_subnet_type_record($array) {
    return(ona_get_record($array, 'subnet_types'));
}

function ona_get_vlan_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'vlans');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_vlan_campus_record(
                                                    array('id' => $record['vlan_campus_id'])
                                                );
    $status += $status_tmp;
    $record['vlan_campus_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_vlan_campus_record($array) {
    return(ona_get_record($array, 'vlan_campuses'));
}

function ona_get_dhcp_option_record($array) {
    return(ona_get_record($array, 'dhcp_options'));
}

function ona_get_dhcp_option_entry_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'dhcp_option_entries');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_dhcp_option_record(array('id' => $record['dhcp_option_id']));
    $status += $status_tmp;
    $record['number'] = $record_tmp['number'];
    $record['name'] = $record_tmp['name'];
    $record['display_name'] = $record_tmp['display_name'];
    $record['type'] = $record_tmp['type'];

    return(array($status, $rows, $record));
}

function ona_get_dhcp_pool_record($array) {
    return(ona_get_record($array, 'dhcp_pools'));
}

function ona_get_dhcp_server_subnet_record($array) {
    return(ona_get_record($array, 'dhcp_server_subnets'));
}

function ona_get_dns_server_domain_record($array) {
    return(ona_get_record($array, 'dns_server_domains'));
}





// FIXME (MP) currently not in use.. however it does provide lookup by ID or name which is not currently working.. probably should fix this function and call it find_config_type

// ///////////////////////////////////////////////////////////////////////
// //  Function: ona_get_configtype_deref($id or $string)
// //
// //  Translates a config type NAME to an ID, or vice versa.
// //  Returns 0 on error.
// //
// ///////////////////////////////////////////////////////////////////////
// function ona_get_configtype_deref($search='') {
//     global $onadb;
//     global $self;
//
//     // Debugging
//     printmsg("DEBUG => ona_get_configtype_deref($search) called", 3);
//
//     // Return 0 if there was no input
//     if (!$search) { return(0); }
//
//     // If $q is numeric
//     if (preg_match('/^\d+$/', $search)) {
//         // Select the type name
//         $q = 'SELECT *
//               FROM IP.CONFIG_TYPE
//               WHERE IP.CONFIG_TYPE.CONFIG_TYPE_ID=' . $onadb->qstr($search);
//         $rs = $onadb->Execute($q);
//         if ($rs === false) {
//             printmsg('ERROR => SQL query failed: ' . $onadb->ErrorMsg(), 3);
//             return(0);
//         }
//         if ($rs->RecordCount() >= 1) {
//             $row = $rs->FetchRow();
//             return($row['CONFIG_TYPE_NAME']);
//         }
//     }
//
//     // Otherwise lookup ID by NAME
//     else {
//         // Select the type name
//         $q = 'SELECT *
//               FROM IP.CONFIG_TYPE
//               WHERE IP.CONFIG_TYPE.CONFIG_TYPE_NAME=' . $onadb->qstr($search);
//         $rs = $onadb->Execute($q);
//         if ($rs === false) {
//             printmsg('ERROR => SQL query failed: ' . $onadb->ErrorMsg(), 3);
//             return(0);
//         }
//         if ($rs->RecordCount() >= 1) {
//             $row = $rs->FetchRow();
//             return($row['CONFIG_TYPE_ID']);
//         }
//     }
//
//     // Just in case
//     return(0);
// }










///////////////////////////////////////////////////////////////////////
//  Function: ona_get_next_id($tablename)
//
//  Returns the next ID for the specified table.  It was decided
//  to create this function instead of using the ADODB GenID() function.
//  We didnt want to have sequence tables cluttering the schema so a single
//  table was created that stored the ID and the tablename it is for.
//
//  Example:
//      $id = ona_get_next_id($tablename);
///////////////////////////////////////////////////////////////////////
function ona_get_next_id($tablename) {
    global $onadb, $self;

    // Debugging
    printmsg("DEBUG => ona_get_next_id() called", 3);

    // Find the sequence value for the specified tablename
    list($status, $rows, $record) = db_get_record($onadb, 'sequences', array('name' => $tablename));

    // Init a new sequence when the tablename is not found
    if (!$rows) {
        list($status, $rows) = db_insert_record($onadb, 'sequences', array('name' => $tablename, 'seq' => 2));
        return(1);
    }
    else {
        // if we did find something increment the sequence in the table
        $seq_inc = $record['seq'] + 1;
        list($status, $rows) = db_update_record($onadb, 'sequences', array('name' => $tablename), array('seq' => $seq_inc));

        if ($status) {
            $self['error'] = 'ERROR => ona_get_next_id() Unable to update sequence value!';
            printmsg($self['error'], 4);
            return(0);
        }

        // If we got an ID, return it.
        if ($record['seq'] > 0) {
            printmsg("DEBUG => ona_get_next_id() Returning ID: " . $record['seq'], 4);
            return($record['seq']);
        }
        // Just in case...
        else {
            $self['error'] = 'ERROR => ona_get_next_id() Something went wrong!';
            printmsg($self['error'], 4);
            return(0);
        }
    }
}








///////////////////////////////////////////////////////////////////////
//  Function: string $domain_name = ona_build_domain_name (id=NUMBER)
//
//  Input:
//    $id = Row ID for a domain record
//
//  Output:
//    Returns the full domain name of the specified domain record.
//
//  Description:
//    Walks up the tree of domain records and returns the full
//    name of the domain record specified.  Usually used for displaying
//    a full domain name over the gui.
//    FIXME: (bz) maybe this should allow a string to be passed too to
//                provide search abilities?
//
//  Example:  $name = ona_build_domain_name(18);
///////////////////////////////////////////////////////////////////////
function ona_build_domain_name($search='') {
    global $conf, $self, $onadb;
    $domain_name = '';
    $status = 0;
    while ($status == 0) {
        list($status, $rows, $domain) = db_get_record($onadb, 'domains', array('id' => $search));
        if ($domain_name == '') { // i.e. the first pass
            $domain_name = $domain['name'];
            if ($domain['parent_id'] != 0) { $search = $domain['parent_id']; }
        } else {
            $domain_name .= '.' . $domain['name'];
            if ($domain['parent_id'] != 0) { $search = $domain['parent_id']; }
        }
        if ($domain['parent_id'] == 0) {
            return($domain_name);
        }
    }
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_host (string $search)
//
//  Input:
//    $search = An FQDN, host ID, IP address (or other unique interface
//              identifier), or any substring that can uniquly identify
//              a host record.
//
//  Output:
//    Returns a three part array: list($status, $rows, $host)
//
//  Description:
//    If $search is not an FQDN:
//      The requested host record is identified via host ID, IP addr,
//      or unique dns name, etc, and the associated host record is
//      returned.
//    If $search is an FQDN:
//      Looks at $fqdn, determines which part of it (if any) is the
//      domain name and which part is the hostname.  Then searches the
//      database for matching hostname record
//      * In the event that the FQDN does not contain a valid dns name
//        a "fake" host record is returned with only the "name" and
//        "fqdn" keys populated.
//      * In the event that a valid, existing, domain can not be found in
//        the FQDN, the default domain "something.com" will be returned.
//        I.E. A valid domain record will always be returned.
//
//  Example:  list($status, $rows, $host) = ona_find_host('myhost.domain.com');
///////////////////////////////////////////////////////////////////////
function ona_find_host($search="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => ona_find_host({$search}) called", 3);

    // By record ID?
    if (is_numeric($search)) {
        list($status, $rows, $host) = ona_get_host_record(array('id' => $search));
        if ($rows) {
            printmsg("DEBUG => ona_find_host({$search}) called, found: {$host['fqdn']}", 3);
            return(array($status, $rows, $host));
        }
    }

    // By Interface ID or IP address?
    list($status, $rows, $interface) = ona_find_interface($search);
    if (!$status and $rows) {
        // Load and return associated info
        list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
        return(array($status, $rows, $host));
    }

    // MP: NEEDS MORE VALIDATION ON THIS PART!!
    // If it does not have a dot in it. append the default domain
    if (!strstr($search,'.')) {
        $search = $search.'.'.$conf['dns_defaultdomain'];
    }

    //
    // It's an FQDN, do a bunch of stuff!
    //

    // lets test out if it has a / in it to strip the view name portion
    $view['id'] = 0;
    if (strstr($search,'/')) {
        list($dnsview,$search) = explode('/', $search);
        list($status, $rows, $view) = db_get_record($onadb, 'dns_views', array('name' => strtoupper($dnsview)));
        printmsg("DEBUG => ona_find_host: DNS view [{$dnsview}] was not found, using default", 2);
        if(!$rows) $view['id'] = 0;
    }


    // FIXME: MP this will currently "fail" if the fqdn of the server
    // is the same as a valid domain name.  not sure why anyone would have this but
    // never say never.  I'll leave this issue unfixed for now

    // Find the 'first', domain name piece of $search
    list($status, $rows, $domain) = ona_find_domain($search,0);
    if (!isset($domain['id'])) {
        printmsg("ERROR => Unable to determine domain name portion of ({$search})!", 3);
        $self['error'] = "ERROR => Unable to determine domain name portion of ({$search})!";
        return(array(3, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ona_find_domain({$search}) returned: {$domain['fqdn']}", 3);

    // Now find what the host part of $search is
    $hostname = str_replace(".{$domain['fqdn']}", '', $search);


    // Let's see if that hostname is valid or not in $domain['id']
    $domain_parts = explode('.', $domain['fqdn']);
    foreach ($domain_parts as $part) {
        // Loop through the parts of the domain to find host.sub domain.com type entries..
        list($status, $dnsrows, $dnsrecs) = db_get_records($onadb, 'dns', array('domain_id' => $domain['id'], 'name' => $hostname, 'dns_view_id' => $view['id']));
        // If we didnt just find a dns record.. lets move the period over and try a deeper domain/host pair.
        if (!$dnsrows) {
            $hostname = $hostname.'.'.$part;
            $name = str_replace("{$part}.", '', $domain['fqdn']);
            list($status, $rows, $domain) = ona_get_domain_record(array('name' => $name));
        } else {
            break;
        }
    }


    // If we found one or more dns records, lets loop through them all and find the first primary host using that name
    if ($dnsrows) {
        foreach ($dnsrecs as $entry) {
            list($status, $rows, $host) = ona_get_host_record(array('primary_dns_id' => $entry['id']));
            if ($host['id'])
                return(array($status, $rows, $host));
        }
    }

    // Otherwise, build a fake host record with only a few entries in it and return that
    $host = array(
        'id'          => 0,
        'name'        => $hostname,
        'fqdn'        => "{$hostname}.{$domain['fqdn']}",
        'domain_id'   => $domain['id'],
        'domain_fqdn' => $domain['fqdn'],
    );

    return(array(0, 0, $host));
}









///////////////////////////////////////////////////////////////////////
//  Function: ona_find_domain (string $fqdn)
//
//  $fqdn = The hostname[.domain] you want to find the domain record
//          for.
//
//  Looks at $fqdn, finds the best-matching domain in it, and returns
//  it.  If $fqdn does not include a valid domain of any sort, we
//  assume $fqdn is a bare hostname, and return the domain record
//  for the user's default domain.
//
//  You can pass a 0 as the second option to instruct it not to send back the 
//  default domain name
//
//  Example: list($status, $rows, $domain) = ona_find_domain('myhost.mydomain.com',1);
///////////////////////////////////////////////////////////////////////
function ona_find_domain($fqdn="", $returndefault=0) {
    global $conf;
    $status=1;
    $fqdn = strtolower($fqdn);
    printmsg("DEBUG => ona_find_domain({$fqdn}) called", 3);

    // lets test out if it has a / in it to strip the view name portion
    if (strstr($fqdn,'/')) {
        list($dnsview,$fqdn) = explode('/', $fqdn);
    }

    // Split it up on '.' and put it in an array backwards
    $parts = array_reverse(explode('.', $fqdn));

    // Find the domain name that best matches
    $name = '';
    $foundone = 0;
    $domain = array();
    foreach ($parts as $part) {
        if (!$foundone) {
            if (!$name) $name = $part;
            else $name = "{$part}.{$name}";
            list($status, $rowsa, $record) = ona_get_domain_record(array('name' => $name));
            if ($rowsa) {
                $domain = $record;
                printmsg("DEBUG => ona_find_domain({$fqdn}) Found: {$domain['fqdn']}", 3);
                $foundone = 1;
            }
        }
        else {
            if (!$name) $name = $part;
            else $name = preg_replace("/.{$domain['fqdn']}$/", '', "{$part}.{$name}");
            list($status, $rowsb, $record) = ona_get_domain_record(array('name' => $name, 'parent_id' => $domain['id']));
            if ($rowsb) {
                $domain = $record;
                printmsg("DEBUG => ona_find_domain({$fqdn}) Found with parent: {$domain['fqdn']}", 3);
                $foundone = 1;
                $name = '';
            } else {
                // try it as a fqdn not with a parent
                list($status, $rowsb, $record) = ona_get_domain_record(array('name' => "{$name}.{$domain['fqdn']}", 'parent_id' => 0));
                if ($rowsb) {
                    $domain = $record;
                    printmsg("DEBUG => ona_find_domain({$fqdn}) Found with parent: {$domain['fqdn']}", 3);
                    $foundone = 1;
                    $name = '';
                }
            }

        }
    }

// FIXME: MP removed since it caused problems when passing in things like rtr.example.comasdl  you would get rtrasdl.example.com back
//     if ($returndefault=1) {
//         // If we don't have a domain yet, lets assume $fqdn is a basic hostname, and return the default domain
//         if (!array_key_exists('id', $domain)) {
//             printmsg("DEBUG => ona_find_domain({$fqdn}) Using system default domain: {$conf['dns_defaultdomain']}", 3);
//             list($status, $rows, $record) = ona_get_domain_record(array('name' => $conf['dns_defaultdomain']));
//             if($rows)
//                 $domain = $record;
//         }
//         // Set status to 0 (ok) since we are returning something
//         $status=0;
//     }

    // FIXME: MP  rows is not right here..  need to look at fixing it.. rowsa/rowsb above doesnt translate.. do I even need that?
    return(array($status, $rows, $domain));
}











///////////////////////////////////////////////////////////////////////
//  Function: ona_find_dns_record (string $search, string $type)
//
//  $search = The hostname[.domain], or ID you want to find the domain record
//          for.
//  $type = The type of dns record you are looking for, A, CNAME, PTR, etc
//  $int_id = The interface_id to use when searching for A records. helps limit to one record
//
//  Looks at $search, finds the best-matching dns record for it, and returns
//  it.  If $search does not include a valid domain of any sort, we
//  assume $search is a bare hostname, and return the dns record with
//  the user's default domain.
//
//  Example: list($status, $rows, $dns) = ona_find_dns_record('myhost.mydomain.com');
///////////////////////////////////////////////////////////////////////
function ona_find_dns_record($search="",$type='',$int_id=0) {
    global $conf, $self, $onadb;
    printmsg("DEBUG => ona_find_dns_record({$search}) called", 3);
    $type   = strtoupper($type);
    $search = strtolower($search);

    // By record ID?
    if (is_numeric($search)) {
        list($status, $rows, $dns) = ona_get_dns_record(array('id' => $search));
        if ($rows) {
            printmsg("DEBUG => ona_find_dns_record({$search}) called, found: {$dns['fqdn']}({$dns['type']})", 3);
            return(array($status, $rows, $dns));
        }
    }


    //
    // It's an FQDN, do a bunch of stuff!
    //

    // lets test out if it has a / in it to strip the view name portion
    $view['id'] = 0;
    if (strstr($search,'/')) {
        list($dnsview,$search) = explode('/', $search);
        list($status, $rows, $view) = db_get_record($onadb, 'dns_views', array('name' => strtoupper($dnsview)));
        if(!$rows) $view['id'] = 0;
    }


    // Find the domain name piece of $search
    list($status, $rows, $domain) = ona_find_domain($search);
    printmsg("DEBUG => ona_find_domain({$search}) returned: {$domain['fqdn']}", 3);

    // Now find what the host part of $search is
    $hostname = str_replace(".{$domain['fqdn']}", '', $search);

    // If the hostname we came up with and the domain name are the same, then assume this is
    // meant to be a domain specific record, like A, MX, NS type records.
    if ($hostname == $domain['fqdn']) $hostname = '';

    // Setup the search array
    $searcharray = array('domain_id' => $domain['id'], 'name' => $hostname, 'dns_view_id' => $view['id']);

    // If an interface_id was passed, add it to the array
    if ($int_id > 0) { $searcharray['interface_id'] = $int_id; }
    // If a type was passed, add it to the array
    if ($type) { $searcharray['type'] = $type; }

    // Let's see if that hostname is valid or not in $domain['id']
    list($status, $rows, $dns) = ona_get_dns_record($searcharray);

    if ($rows) {
        // Return good status, one row, and $dns array
        printmsg("DEBUG => ona_find_dns_record({$search}) called, found: {$dns['fqdn']}({$dns['type']})", 3);
        return(array(0, 1, $dns));
    }

    // Otherwise, build a fake dns record with only a few entries in it and return that
    $dns = array(
        'id'          => 0,
        'name'        => $hostname,
        'fqdn'        => "{$hostname}.{$domain['fqdn']}",
        'domain_id'   => $domain['id'],
        'domain_fqdn' => $domain['fqdn'],
        'type'        => '',
        'dns_id'      => 0
    );

    printmsg("DEBUG => ona_find_dns_record({$search}) called, Nothing found, returning fake entry: {$dns['fqdn']}({$dns['type']})", 3);
    return(array(0, 1, $dns));
}














///////////////////////////////////////////////////////////////////////
//  Function: ona_find_location(string $search)
//
//  Input:
//    $search = A location ID, location number, name or substring that can
//              uniquly identify a location.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An associative array of a record from the LOCATIONS table
//         where $search matchs.
//
//  Example: list($status, $rows, $location_record) = ona_find_location('fairview');
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or no input
//    2  :: No (unique?) match found
///////////////////////////////////////////////////////////////////////
function ona_find_location($search="") {
    printmsg("DEBUG => ona_find_location({$search}) called", 3);

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // Search for it by Location ID
        list($status, $rows, $record) = ona_get_location_record(array('id' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by id", 2);
            return(array(0, $rows, $record));
        }

        // Search for it by zip code
        list($status, $rows, $record) = ona_get_location_record(array('zip_code' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by zip code search", 2);
            return(array(0, $rows, $record));
        }
    }

    // It's a string - do several sql queries and see if we can get a unique match
    foreach (array('reference','name', 'address', 'city', 'state') as $field) {
        // First, try it as they send it
        list($status, $rows, $record) = ona_get_location_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by $field search", 2);
            return(array(0, $rows, $record));
        }
    }

    foreach (array('reference','name', 'address', 'city', 'state') as $field) {
        // Next, do an upper on it to try it as upper case
        $search = strtoupper($search);
        list($status, $rows, $record) = ona_get_location_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by UPPER($field) search", 2);
            return(array(0, $rows, $record));
        }
    }

    foreach (array('reference','name', 'address', 'city', 'state') as $field) {
        // Last, try it all lowercase
        $search = strtolower($search);
        list($status, $rows, $record) = ona_get_location_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by LOWER($field) search", 2);
            return(array(0, $rows, $record));
        }
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    printmsg("DEBUG => ona_find_location() couldn't find a unique location record with specified search criteria", 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_interface(string $search)
//
//  Input:
//    $search = An interface ID, IP address, or any substring that can
//              uniquly identify an interface.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An associative array of a record from the interfaces table
//         where $search matchs.
//
//  Example: list($status, $rows, $interface) = ona_find_interface('10.44.10.123');
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or no input
//    2  :: No (unique?) match found
//    3  :: No such IP address
//    4  :: More than one interface has that IP address
//    5  :: No such MAC address
//    6  :: More than one interface has that MAC address
///////////////////////////////////////////////////////////////////////
function ona_find_interface($search="") {
    printmsg("DEBUG => ona_find_interface({$search}) called", 3);

    // Validate input
    if ($search == "")
        return(array(1, 0, array()));

    // If it's numeric
    if (is_numeric($search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        foreach (array('id', 'host_id', 'ip_addr') as $field) {
            list($status, $rows, $record) = ona_get_interface_record("{$field} like '{$search}'");
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_interface() found interface record by {$field}", 2);
                return(array(0, $rows, $record));
            }
        }
    }

    // If it's an IP address...
    $ip = ip_mangle($search, 1);
    if ($ip != -1) {
        list($status, $rows, $record) = ona_get_interface_record("ip_addr like '{$ip}'");
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_interface() found record by IP address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            printmsg("DEBUG => No interface has the IP address: $search", 2);
            return(array(3, $rows, array()));
        }
        printmsg("DEBUG => More than one interface has the IP address: $search", 2);
        return(array(4, $rows, array()));
    }


    // If it's a MAC address
    $mac = mac_mangle($search, 1);
    if ($mac != -1) {

        // Search for it
        list($status, $rows, $record) = ona_get_interface_record(array('mac_addr' => $mac));

        // If we got it, return it
        if (!$status and $rows == 1) {
            printmsg("DEBUG => ona_find_interface() found record by MAC address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            printmsg("ERROR => No interface has the MAC address: $search", 2);
            return(array(5, 0, array()));
        }
        printmsg("DEBUG => ona_find_interface() More than one interface has the MAC address: " . mac_mangle($mac, 1), 0);
        return(array(6, 0, array()));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    printmsg("DEBUG => ona_find_interface() couldn't find a unique interface record with specified search criteria", 1);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_subnet(string $search)
//
//  Input:
//    $search = A subnet ID or IP address that can uniquly identify a subnet.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the subnets table where $search matchs.
//
//  Example: list($status, $rows, $subnet) = ona_find_subnet('10.44.10.123');
///////////////////////////////////////////////////////////////////////
function ona_find_subnet($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        foreach (array('id', 'ip_addr') as $field) {
            // list($status, $rows, $record) = ona_get_subnet_record(array($field => $search));
            // GDO: don't use array() here, because it breaks ipv6 subnets
            list($status, $rows, $record) = ona_get_subnet_record("$field = $search");
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_subnet() found subnet record by $field $search", 2);
                return(array(0, $rows, $record));
            }
        }
    }

    // If it's an IP address
    $ip = ip_mangle($search, 1);
    if ($ip != -1) {

        // Do a cool SQL query to find the subnet that the given IP address is on
        // Basically we tell the database we want to find an IP address >= than the base
        // of the subnet, and less than the end of the subnet.
        // Description:
        //   (2^32 - 1) == 4294967295 == a 32bit integer with all 1's.
        //   4294967295 - subnet_mask results in the number of hosts on that subnet.
        //   + the base ip_addr results in the top of the subnet.
        if (strlen($ip) > 11) {
            // IPv6.. had to check that it was above ipv4 space
	    $where = "$ip between ip_addr AND ((340282366920938463463374607431768211455 - ip_mask) + ip_addr) and ip_addr > 4294967295";
        } else {
            $where = "$ip >= ip_addr AND $ip <= ((4294967295 - ip_mask) + ip_addr)";
        }

        list($status, $rows, $record) = ona_get_subnet_record($where);

        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_subnet() found record by IP address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            $ip = ip_mangle($ip, 2);
            $self['error'] = "NOTICE => IP supplied, $ip, does not belong to any existing subnet!";
            printmsg($self['error'], 2);
            return(array(3, $rows, array()));
        }
        $self['error'] = "NOTICE => IP supplied, $ip, belongs to more than one subnet! Data corruption?";
        printmsg($self['error'], 2);
        return(array(4, $rows, array()));
    }

    // Try the name field
    // We use all upper-case subnet names
    list($status, $rows, $record) = ona_get_subnet_record(array('name' => strtoupper($search)));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_subnet() found subnet record by its name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique subnet record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_device(string $search)
//
//  Input:
//    $search = A device ID, IP address, or FQDN that can uniquely identify a device.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the devices table where $search matches.
//
//  Example: list($status, $rows, $device) = ona_find_device('1714');
///////////////////////////////////////////////////////////////////////
function ona_find_device($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, assume its a device ID
    if (preg_match('/^\d+$/', $search)) {
        list($status, $rows, $record) = ona_get_device_record(array('id' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_device() found device record by id", 2);
            return(array(0, $rows, $record));
        }
    }

    // If it's an IP address or an FQDN:
//    $ip = ip_mangle($search, 'numeric');
//    if ($ip != -1) {
        // Look up the host first
        list($status, $rows, $host) = ona_find_host($search);
        if ($status == 0 and $rows == 1) {
            list($status, $rows, $record) = ona_get_device_record(array('id' => $host['device_id']));
        }

        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_device() found record by IP address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            //$ip = ip_mangle($ip, 'dotted');
            $self['error'] = "NOTICE => ona_find_device() was unable to locate the record by IP or fqdn";
            printmsg($self['error'], 2);
            return(array(3, $rows, array()));
        }
//        $self['error'] = "NOTICE => ona_find_device() found multiple matching records when searching by IP or fqdn. Data corruption?";
//        printmsg($self['error'], 2);
//        return(array(4, $rows, array()));
//    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => ona_find_device() couldn't find a unique device record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_device_type(string $search)
//
//  Input:
//    $search = A model name string or device_type ID that can uniquely identify
//              a device type from the device_types table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the devices table where
//         $search matchs.
//
//  Example: list($status, $rows, $subnet) = ona_find_device_type('something');
///////////////////////////////////////////////////////////////////////
function ona_find_device_type($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        list($status, $rows, $record) = ona_get_device_type_record(array('id' => $search));
        // If we got it, return it
        if ($status == 0  and $rows == 1) {
            printmsg("DEBUG => ona_find_device_type() found device_type record by id", 2);
            return(array(0, $rows, $record));
        }
/* PK: this was the original code...
        foreach (array('id', 'DEVICE_TYPE_ID', 'MANUFACTURER_ID') as $field) {
            list($status, $rows, $record) = ona_get_model_record(array($field => $search));
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_device() found device record by $field", 2);
                return(array(0, $rows, $record));
            }
        }*/
    }

    // It's a string - do several sql queries and see if we can get a unique match
    list($manufmodel, $role) = split("\(",$search);
    list($manuf, $model) = split(", ",$manufmodel);
    $role = preg_replace(array('/\(/','/\)/'),'',"{$role}");
    list($status, $rows, $manu) = ona_get_manufacturer_record(array('name' => $manuf));
    list($status, $rows, $rol) = ona_get_role_record(array('name' => $role));
    list($status, $rows, $record) = ona_get_model_record(array('name' => $model,'manufacturer_id' => $manu['id']));
    if ($status == 0 and $rows == 1) {
        list($status, $rows, $record) = ona_get_device_type_record(array('model_id' => $record['id'],'role_id' => $rol['id']));
    }
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_device_type() found device_type record by model name", 2);
        return(array(0, $rows, $record));
    }


    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique device_type record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));

}









///////////////////////////////////////////////////////////////////////
//  Function: ona_find_subnet_type(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a subnet
//              type from the "subnet_types" table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the "subnet_types" table where
//         $search matchs.
//
//  Example: list($status, $rows, $net_type) = ona_find_subnet_type('VLAN (802.1Q or ISL)');
///////////////////////////////////////////////////////////////////////
function ona_find_subnet_type($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        $self['error'] = "ERROR => No search string for subnet-type search";
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (is_numeric($search)) {
        $field = 'id';
        list($status, $rows, $record) = ona_get_subnet_type_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_subnet_type() found device record by $field", 2);
            return(array($status, $rows, $record));
        }
    }

    // It's a string - do several sql queries and see if we can get a unique match
    list($status, $rows, $record) = ona_get_subnet_type_record(array('display_name' => $search));
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_subnet_type() found subnet_type record by its name", 2);
        return(array(0, $rows, $record));
    }

    list($status, $rows, $record) = ona_get_subnet_type_record(array('short_name' => $search));
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_subnet_type() found subnet_type record by its name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => Subnet-type not found";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}





///////////////////////////////////////////////////////////////////////
//  Function: ona_find_custom_attribute(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a record
//              from the custom_attributes table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the custom_attributes table where
//         $search matchs.
//
//  Example: list($status, $rows, $net_type) = ona_find_custom_attribute('Status (Testing)');
///////////////////////////////////////////////////////////////////////
function ona_find_custom_attribute($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (is_numeric($search)) {
        $field = 'id';
        list($status, $rows, $record) = ona_get_custom_attribute_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_custom_attribute() found custom attribute record by $field", 2);
            return(array(0, $rows, $record));
        }
    }

    // Split the description based on the () enclosed type
    list($ca_type, $ca_value) = preg_split("/\(|\)/",$search);

    printmsg("DEBUG => ona_find_custom_attribute(): Split is {$ca_type},{$ca_value}", 3);


    // It's a string - do several sql queries and see if we can get a unique match
    list($status, $rows, $type) = ona_get_custom_attribute_type_record(array('name' => trim($ca_type)));

    printmsg("DEBUG => ona_find_custom_attribute(): Found {$rows} custom attribute type record", 3);

    // Find the ID using the type id and value
    list($status, $rows, $record) = ona_get_custom_attribute_record(array('value' => $ca_value,'custom_attribute_type_id' => $type['id']));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_custom_attribute(): Found custom attribute record by its full name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique custom attribute record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_dhcp_option(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a dhcp parm type
//              from the dhcp_parameter_types table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the dhcp_options table where
//         $search matchs.
//
//  Example: list($status, $rows, $dhcp_type) = ona_find_dhcp_option('Default gateway(s)');
///////////////////////////////////////////////////////////////////////
function ona_find_dhcp_option($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (is_numeric($search)) {
        $field = 'id';
        list($status, $rows, $record) = ona_get_dhcp_option_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_dhcp_option(): found type record by $field", 2);
            return(array(0, $rows, $record));
        }
    }

    foreach (array('name', 'display_name', 'number', 'tag') as $field) {
        // Do several sql queries and see if we can get a unique match
        list($status, $rows, $record) = ona_get_dhcp_option_record(array($field => $search));

        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_dhcp_option(): Found type record -> {$record['display_name']}", 2);
            return(array(0, $rows, $record));
        }
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique DHCP option record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_vlan(string $vlan_search, [string $campus_search])
//
//  Input:
//    $vlan_search =
//        A string or ID that can uniqely identify a vlan record from
//        the vlans table in the database.  Often times a vlan
//        description is 'DEFAULT', in which case you can help narrow
//        down the search by also providing $campus_search.. see below.
//    $campus_search =
//        A string or ID that can uniqely identify a vlan campus record
//        from the vlan_campuses table.  Often times a vlan itself can't
//        be identified by name without a campus name too.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the vlans table where $search matchs.
//
//  Example: list($status, $rows, $vlan) = ona_find_vlan('VLAN (802.1Q or ISL)');
///////////////////////////////////////////////////////////////////////
function ona_find_vlan($vlan_search="", $campus_search="") {
    global $self;
    if (!$vlan_search and !$campus_search) return(array(1, 0, array()));

    // If we got a vlan campus search string, let's look for that first.
    if ($campus_search) {
        $campus_search = strtoupper($campus_search);
        // Do a few sql queries and see if we can get a unique match
        $search = $campus_search;
        foreach (array('name', 'id') as $field) {
            list($status, $rows, $campus) = ona_get_vlan_campus_record(array($field => $search));
            if (!$status and $rows == 1) {
                printmsg("DEBUG => ona_find_vlan() found vlan campus record by $field", 2);
                break;
            }
            else
                $campus = array();
        }
    }

    // Search by a vlan number
    if (is_numeric($vlan_search)) {
      $where = array('number' => $vlan_search);
    } else {
      // Search for a vlan by NAME, use the campus[ID] if we have one
      $vlan_search = strtoupper($vlan_search);
      $where = array('name' => $vlan_search);
    }

    if ($campus['id']) $where['vlan_campus_id'] = $campus['id'];
    list($status, $rows, $vlan) = ona_get_vlan_record($where);
    if (!$status and $rows == 1) {
      return(array($status, $rows, $vlan));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique vlan record with specified search criteria";
    printmsg($self['error'], 1);
    return(array(2, 0, array()));
}





///////////////////////////////////////////////////////////////////////
//  Function: ona_find_config (array $search)
//
//  Input Options:
//    $search = See functions below for how this function is used.
//              It's an internal function.
//
//  Output:
//    Returns the data returned from ona_get_config_record()
//    Error messages are stored in global $self['error']
//
///////////////////////////////////////////////////////////////////////
function ona_find_config($options=array()) {

    global $self;

    $status = 1;
    $rows = 0;
    $config = array();

    // If the user specified a config text ID
    if ($options['config']) {
        if (!preg_match('/^\d+$/', $options['config'])) {
            $self['error'] = "ERROR => A non-digit config ID was specified!";
            return(array(2, 0, array()));
        }

        list($status, $rows, $config) = ona_get_config_record(array('id' => $options['config']));
    }

    // Otherwise we're selecting a config by hostname and type
    else if ($options['host'] and $options['type']) {
        // Search for the host first
        list($status, $rows, $host) = ona_find_host($options['host']);

        // Error if the host doesn't exist
        if (!$host['id']) {
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(3, 0, array()));
        }

        // Now find the ID of the config type they entered
        list($status, $rows, $config_type) = ona_get_config_type_record(array('name' => $options['type']));
        if (!$config_type['id']) {
            $self['error'] = "ERROR => The config type specified, {$options['type']}, is invalid!";
            return(array(4, 0, array()));
        }

        // Select the first config record of the specified type and host
        list($status, $rows, $config) = ona_get_config_record(array('host_id' => $host['id'],
                                                                     'configuration_type_id' => $config_type['id']));

        if ($status) {
            $self['error'] = "ERROR => The config type specified, {$options['type']}, is invalid!";
            return(array(5, 0, array()));
        }

    }

    // Return the config record we got
    return(array($status, $rows, $config));

}








// DON'T put whitespace at the beginning or end of this file!!!
?>

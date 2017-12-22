<?php
/* ------------------------------------------------------------------------
 * session_mysql.php
 * ------------------------------------------------------------------------
 * PHP4 MySQL Session Handler
 * Version 1.22 - ADODB
 * by Ying Zhang (ying@zippydesign.com)
 * Last Modified: 2006-11-18 (by Brandon Zehm)
 *   Now uses db_delete_records() for sess_gc()
 *   Now requires ADODB but should require fewer db connections.
 * ------------------------------------------------------------------------
 * TERMS OF USAGE:
 * ------------------------------------------------------------------------
 * You are free to use this library in any way you want, no warranties are
 * expressed or implied.  This works for me, but I don't guarantee that it
 * works for you, USE AT YOUR OWN RISK.
 *
 * ------------------------------------------------------------------------
 * DESCRIPTION:
 * ------------------------------------------------------------------------
 * This library tells the PHP4 session handler to write to a MySQL database
 * instead of creating individual files for each session.
 *
 * Create a new database in MySQL called "sessions" like so:
 *
 * CREATE TABLE sessions (
 *      sesskey char(32) not null,
 *      expiry int(11) unsigned not null,
 *      sessvalue text not null,
 *      PRIMARY KEY (sesskey)
 * );
 *
 * ------------------------------------------------------------------------
 * INSTALLATION:
 * ------------------------------------------------------------------------
 * Make sure you have MySQL support compiled into PHP.  Then copy this
 * script to a directory that is accessible by the rest of your PHP
 * scripts.
 *
 * ------------------------------------------------------------------------
 * USAGE:
 * ------------------------------------------------------------------------
 * Include this file in your scripts before you call session_start(), you
 * don't have to do anything special after that.
 *
 * ------------------------------------------------------------------------
 * DEVELOPERS:
 * ------------------------------------------------------------------------
 * These SQL queries do not need mysql_escape_string in them, PHP
 * does that sort of thing before it passes data to these functions.
*/

global $SESS_DBH, $SESS_LIFE, $onadb, $conf;
$SESS_DBH = $onadb;
$SESS_LIFE = $conf['cookie_life'];

// The following is a fix/workaround for php 5.2 and the fact
// that it handles destructors in a new order.
register_shutdown_function('session_write_close');

function sess_open($save_path, $session_name) { return true; }
function sess_close() { return true; }


// Reads and returns a session's data
function sess_read($key) {
    global $SESS_DBH, $SESS_LIFE;
    printmsg("sess_read($key) called", 6);

    list($status, $rows, $record) = db_get_record($SESS_DBH, 'sessions', "`sesskey` = '$key' AND `expiry` > " . time());
    if ($status or $rows == 0) { return false; }

    if (array_key_exists('sessvalue', $record)) {
        // Update the expiry time (i.e. keep sessions alive even if nothing in the session has changed)
        $expiry = time() + $SESS_LIFE;
        list($status, $rows) = db_update_record($SESS_DBH, 'sessions', "`sesskey` = '$key' AND `expiry` > " . time(), array('expiry' => $expiry));
        if ($status) { return false; }

        // Return the value
        return($record['sessvalue']);
    }

    return false;
}


function sess_write($key, $value) {
    global $SESS_DBH, $SESS_LIFE;
    //printmsg("sess_write($key, $value) called", 6);

    $expiry = time() + $SESS_LIFE;

    // Try inserting the value into the DB
    list($status, $rows) = db_insert_record($SESS_DBH, 'sessions', array('sesskey' => $key, 'expiry' => $expiry, 'sessvalue' => $value));

    // If the insert failed try an update
    if (!$status or $rows == 0) {
        list($status, $rows) = db_update_record($SESS_DBH, 'sessions', array('sesskey' => $key), array('expiry' => $expiry, 'sessvalue' => $value));
    }

    return true;
}


function sess_destroy($key) {
    global $SESS_DBH;
    list($status, $rows) = db_delete_records($SESS_DBH, 'sessions', array('sesskey' => $key));
    return true;
}


function sess_gc($lifetime) {
    global $SESS_DBH;
    list($status, $rows) = db_delete_records($SESS_DBH, "`sessions`", "`expiry` < " . time());
    return true;
}


session_set_save_handler(
    "sess_open",
    "sess_close",
    "sess_read",
    "sess_write",
    "sess_destroy",
    "sess_gc"
);

// DON'T put whitespace at the beginning or end of this file!!!
?>

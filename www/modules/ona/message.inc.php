<?php

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);















///////////////////////////////////////////////////////////////////////
//  Function: message_add (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = message_add('host=test');
//
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    4  :: SQL Query failed
//
//
//  History:
//
//
///////////////////////////////////////////////////////////////////////
function message_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Default expiration
    $exp_default = "+6 week";
    $pri_default = 3;

    // Priority is one of the following:
    // 0 = Informational
    // 1 = red or high
    // 2 = yellow or medium
    // 3 = green or low

    printmsg("DEBUG => message_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['subnet'] and !$options['host']) or
       (!$options['message']) and
       (!$options['expiration'] and
        !$options['priority']
       ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

message_add-v{$version}
Adds the provided message to the host or subnet specified

  Synopsis: message_add

  Required:
    host=NAME[.DOMAIN]|IP     hostname or IP of the host
    OR
    subnet=NAME|IP            name or IP of the subnet

    message="STRING"          the content of the message

  Optional:
    priority=NUMBER           device/model type or ID (default: {$pri_default})
    expiration=DATE           date to expire message (default: NOW {$exp_default}s)

  Notes:
    Priority is one of the following:
    0 = blue or Informational
    1 = red or high
    2 = yellow or medium
    3 = green or low

\n
EOM

        ));
    }


    // If they provided a hostname / ID let's look it up
    if ($options['host']) {
        list($status, $rows, $host) = ona_find_host($options['host']);
        $table_name_ref = 'hosts';
        $table_id_ref = $host['id'];
        $desc = $host['fqdn'];
    }

    // If they provided a subnet name or ip
    else if ($options['subnet']) {
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
        $table_name_ref = 'subnets';
        $table_id_ref = $subnet['id'];
        $desc = $subnet['name'];
    }

    // If we didn't get a record then exit
    if (!$host['id'] and !$subnet['id']) {
        printmsg("DEBUG => No host or subnet found!",3);
        $self['error'] = "ERROR => No host or subnet found!";
        return(array(4, $self['error'] . "\n"));
    }

    // Set the priority
    $priority = (array_key_exists('priority',$options)) ? $options['priority'] : $pri_default;

    if ($priority > 3 or $priority < 0 or !is_numeric($priority) ) {
        $self['error'] = "ERROR => Priority must be a number between 0 and 3!";
        return(array(4, $self['error'] . "\n"));
    }

    // Get a username or "anonymous"
    $username = (isset($_SESSION['username'])) ? $_SESSION['username'] : "anonymous";

    // Expiration date format
    if ($options['expiration']) {
        $expiration = date("Y-m-d G:i:s", strtotime($options['expiration']));
    }
    else {
        $expiration = date("Y-m-d G:i:s", strtotime($exp_default));
    }

    //  TODO: there should probably be some sort of security checks on the message that is passed in.
    //  I suspect this could be a security issue.  SQL injection etc.
    list($status, $rows) = db_insert_record(
        $onadb,
        'messages',
        array(
            'table_name_ref'       => $table_name_ref,
            'table_id_ref'         => $table_id_ref,
            'priority'             => $priority,
            'username'             => $username,
            'expiration'           => $expiration,
            'message_text'         => $options['message']
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => message_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }


    $text = "INFO => Message ADDED to: {$desc}\n";


    // Return the message file
    return(array(0, $text));

}









?>

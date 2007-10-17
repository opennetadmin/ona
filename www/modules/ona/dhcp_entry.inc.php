<?
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);






///////////////////////////////////////////////////////////////////////
//  Function: dhcp_entry_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    host=HOSTNAME[.DOMAIN] or host_id
//    OR
//    subnet=NAME or id
//    OR
//    server=NAME[.DOMAIN] or id
//    AND
//    type=DHCP type
//    value=STRING
//
//  Output:
//    Adds a DHCP entry into the IP database of 'type' with a
//    value of 'value' for the specified 'host', 'subnet', or
//    'server'.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_entry_add('host=q1234.something.com&type="Default gateway(s)"&value=10.1.1.1');
///////////////////////////////////////////////////////////////////////
function dhcp_entry_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_entry_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                ($options['option'] and array_key_exists('value', $options))
                                 and
                                (
                                    ($options['server'] and !($options['host'] or $options['subnet']))
                                    or
                                    ($options['host'] and !($options['server'] or $options['subnet']))
                                    or
                                    ($options['subnet'] and !($options['host'] or $options['server']))
                                )
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_entry_add-v{$version}
Adds a dhcp entry into the database pointing to the specified identifier

  Synopsis: dhcp_entry_add [KEY=VALUE] ...

  Identifier (pick one):
    host=HOSTNAME[.DOMAIN] or ID            host identifier to add to
    subnet=NAME or ID                       subnet identifier to add to
    server=NAME[.DOMAIN] or ID              server identifier to add to

  Options (both required):
    option=DHCP option                      DHCP option name
    value=STRING                            string value for the DHCP type

\n
EOM

        ));
    }



    if ($options['host']) {
        // Determine the host is valid
        list($status, $rows, $host) = ona_find_host($options['host']);

        if (!$host['id']) {
            printmsg("DEBUG => The host specified, {$options['host']}, does not exist!",3);
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        $anchor = 'host';
        $desc = $host['fqdn'];
        $lvl = $host['lvl'];

    } elseif ($options['subnet']) {
        // Determine the subnet is valid
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);

        if (!$subnet['id']) {
            printmsg("DEBUG => The subnet specified, {$options['subnet']}, does not exist!",3);
            $self['error'] = "ERROR => The subnet specified, {$options['subnet']}, does not exist!";
            return(array(3, $self['error'] . "\n"));
        }

        $anchor = 'subnet';
        $desc = "{$subnet['name']} (". ip_mangle($subnet['ip_addr']).")";
        $lvl = $subnet['lvl'];
    } elseif ($options['server']) {
        // Determine the server is valid
        list($status, $rows, $host) = ona_find_host($options['server']);

        if (!$host['id']) {
            printmsg("DEBUG => The server specified, {$options['server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
            return(array(4, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $server) = ona_get_server_record(array('host_id' => $host['id']));

        if (!$server['id']) {
            printmsg("DEBUG => The host specified, {$host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }

        $anchor = 'server';
        $desc = $host['fqdn'];
        $lvl = $host['lvl'];
        $host['id'] = '';
    }

    // trim leading and trailing whitespace from 'value'
    $dhcp_option_value = trim($options['value']);

    // Determine the type is valid
    list($status, $rows, $type) = ona_find_dhcp_option($options['option']);

    if (!$type['id']) {
        printmsg("DEBUG => The DHCP parameter type specified, {$options['option']}, does not exist!",3);
        $self['error'] = "ERROR => The DHCP parameter type specified, {$options['option']}, does not exist!";
        return(array(8, $self['error'] . "\n"));
    }

    printmsg("DEBUG => dhcp_entry_add(): Found DHCP option {$type['display_name']}", 3);


    // Make sure this isn't a duplicate
    $search = array('dhcp_option_id' => $type['id']);
    if ($host['id']) $search['host_id'] = $host['id'];
    if ($subnet['id']) $search['subnet_id'] = $subnet['id'];
    if ($server['id']) $search['server_id'] = $server['id'];
    list($status, $rows, $record) = ona_get_dhcp_option_entry_record($search);
    if ($status or $rows) {
        printmsg("DEBUG => That DHCP option, {$type['display_name']}, is already defined!",3);
        $self['error'] = "ERROR => That DHCP option ({$type['display_name']}) is already defined!";
        return(array(11, $self['error'] . "\n"));
    }



    // Check permissions
    if (!auth('advanced') or !authlvl($lvl)) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next id
    $id = ona_get_next_id('dhcp_option_entries');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }
    printmsg("DEBUG => dhcp_entry_add(): New ID: $id", 3);

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dhcp_option_entries',
            array(
                'id'                      => $id,
                'dhcp_option_id'          => $type['id'],
                'value'                   => $dhcp_option_value,
                'host_id'                 => $host['id'],
                'subnet_id'               => $subnet['id']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_entry_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => DHCP entry ADDED: {$type['display_name']}={$dhcp_option_value} on {$desc} ";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: dhcp_entry_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    id=id
//
//  Output:
//    Deletes a dhcp entry from the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_entry_add('host=test');
///////////////////////////////////////////////////////////////////////
function dhcp_entry_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_entry_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is yes)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['id'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_entry_del-v{$version}
Deletes a DHCP entry from the database

  Synopsis: dhcp_entry_del [KEY=VALUE] ...

  Required:
    id=ID                      ID of the dhcp entry to delete

  Optional:
    commit=[yes|no]            commit db transaction (no)
\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If the option provided is numeric, check to see if it exists
    if (is_numeric($options['id'])) {

        // Debugging
        printmsg("DEBUG => DHCP entry ID selected: {$options['id']}", 3);

        list($status, $tmp_rows, $entry) = ona_get_dhcp_option_entry_record(array('id' => $options['id']));

        // Test to see that we were able to find the specified host_infobit record
        if (!$entry['id']) {
            printmsg("DEBUG => Unable to find the DHCP entry record using ID {$options['id']}!",3);
            $self['error'] = "ERROR => Unable to find the DHCP entry record using ID {$options['id']}!";
            return(array(4, $self['error']. "\n"));
         }

        // Assign a search option based on host or server id
        if ($entry['host_id'])   $search = $entry['host_id'];
        if ($entry['server_id']) $search = $entry['server_id'];

        if ($entry['host_id'] or $entry['server_id']) {
            // Get some host information to display later and determine its valid
            list($status, $rows, $host) = ona_find_host($search);

            // Bail out if you cant find a host
            if (!$host['id']) {
                printmsg("DEBUG => The ID specified, {$search}, does not exist!",3);
                $self['error'] = "ERROR => The ID specified, {$search}, does not exist!";
                return(array(3, $self['error']. "\n"));
            }

            printmsg("DEBUG => dhcp_entry_del(): Using host: {$host['fqdn']} ID: {$host['id']}", 3);
            $desc = $host['fqdn'];
            $lvl = $host['lvl'];

        } elseif ($entry['subnet_id']) {
            // Determine the subnet is valid
            list($status, $rows, $subnet) = ona_find_subnet($entry['subnet_id']);

            if (!$subnet['id']) {
                printmsg("DEBUG => The subnet specified, {$options['subnet']}, does not exist!", 3);
                $self['error'] = "ERROR => The subnet specified, {$options['subnet']}, does not exist!";
                return(array(3, $self['error'] . "\n"));
            }

            printmsg("DEBUG => dhcp_entry_del(): Using subnet: {$subnet['name']} ID: {$subnet['id']}", 3);
            $desc = "{$subnet['name']} (". ip_mangle($subnet['ip_addr']).")";
            $lvl = $subnet['lvl'];

        }

    } else {
            printmsg("DEBUG => {$options['id']} is not a numeric value", 3);
            $self['error'] = "ERROR => {$options['id']} is not a numeric value";
            return(array(15, $self['error'] . "\n"));
    }


    // If "commit" is yes, delte the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced') or !authlvl($lvl)) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'dhcp_option_entries', array('id' => $entry['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => dhcp_entry_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => DHCP entry DELETED: {$entry['display_name']}={$entry['value']} from {$desc} ";
        printmsg($self['error'], 0);
        return(array(0, $self['error'] . "\n"));
    }

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

ON: {$desc}

    Delete the following dhcp entry:
    ENTRY: {$entry['display_name']} = {$entry['value']}

EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: dhcp_entry_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    host=HOSTNAME[.DOMAIN] or host_id
//    OR
//    subnet=NAME or id
//    OR
//    server=NAME[.DOMAIN] or id
//    AND
//    type=DHCP type
//    value=STRING
//
//  Output:
//    Updates an dhcp_entry record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_entry_modify('set_value=something');
///////////////////////////////////////////////////////////////////////
function dhcp_entry_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => dhcp_entry_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
        !(
            ($options['id'])
            and
            (
                ($options['set_option'] and array_key_exists('set_value',$options))
                or
                (array_key_exists('set_value',$options))
            )
         )
       )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_entry_modify-v{$version}
Modifies a DHCP entry in the database

  Synopsis: dhcp_entry_modify [KEY=VALUE] ...

  Where:
    id=ID                                       DHCP entry ID

  Options:
    set_option=DHCP type                        DHCP parameter type
    set_value=STRING                            string value for the DHCP type

  Notes:
    If you specify a type, you must specify a value.
\n
EOM
        ));
    }

    // Determine the entry itself exists
    list($status, $rows, $entry) = ona_get_dhcp_option_entry_record(array('id' => $options['id']));
    if ($status or !$rows) {
        printmsg("DEBUG => Invalid DHCP entry record ID ({$options['id']})!",3);
        $self['error'] = "ERROR => Invalid DHCP entry record ID ({$options['id']})!";
        return(array(2, $self['error']. "\n"));
    }

    printmsg("DEBUG => dhcp_entry_modify(): Found entry, {$entry['display_name']} => {$entry['value']}", 3);
    $desc='';
    // Load associated host, subnet or server record
    $host = $subnet = $server = array();
    if ($entry['host_id']) {
       list($status, $rows, $host) = ona_find_host($entry['host_id']);
       $desc = $host['fqdn'];
    }
    if ($entry['subnet_id']) {
       list($status, $rows, $subnet) = ona_find_subnet($entry['subnet_id']);
       $desc = "{$subnet['name']} (". ip_mangle($subnet['ip_addr']).")";
    }
    if ($entry['server_id']) {
       list($status, $rows, $server)  = ona_get_server_record(array('id' => $entry['server_id']));
       list($status, $rows, $host) = ona_find_host($entry['host_id']);
       $desc = $host['fqdn'];
    }

    // Check permissions on source identifier
    $lvl = 100;
    if ($host['id']) $lvl = $host['lvl'];
    if ($subnet['id']) $lvl = $subnet['lvl'];
    if (!auth('advanced') or (!authlvl($lvl))) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // unset $host if $server is defined .. we don't need it anymore
    if ($server['id']) $host = array();

    // This variable will contain the updated info we'll insert into the DB
    $SET = array();


    if (array_key_exists('set_value', $options)) {
        // trim leading and trailing whitespace from 'value'
        $SET['value'] = trim($options['set_value']);
    }



    if ($options['set_option']) {
        // Make sure they specified a value
        if (!array_key_exists('set_value', $options)) {
            printmsg("DEBUG => No value specified for given DHCP parameter type ({$options['set_option']})!", 3);
            $self['error'] = "ERROR => No value specified for given DHCP parameter type ({$options['set_option']})!";
            return(array(8, $self['error'] . "\n"));
        }

        // Determine the type is valid
        list($status, $rows, $type) = ona_find_dhcp_option(trim($options['set_option']));
        if ($status or !$rows) {
            printmsg("DEBUG => Invalid DHCP parameter type specified ({$options['set_option']})!", 3);
            $self['error'] = "ERROR => Invalid DHCP parameter type specified ({$options['set_option']})!";
            return(array(8, $self['error'] . "\n"));
        }

        printmsg("DEBUG => dhcp_entry_modify(): Found parameter type {$type['display_name']}", 3);

        $SET['dhcp_option_id'] = $type['id'];

        // Make sure this isn't a duplicate
        // TODO: this code seems a bit suspect of being nasty.. possibly fix it up
        $search = array('dhcp_option_id' => $type['id']);
        if ($host['id']) $search['host_id'] = $host['id'];
        if ($subnet['id']) $search['subnet_id'] = $subnet['id'];
        if ($server['id']) $search['server_id'] = $server['id'];
        list($status, $rows, $record) = ona_get_dhcp_option_entry_record($search);
        if ($status or $rows > 1 or ($rows == 1 and $record['id'] != $entry['id']) ) {
            printmsg("DEBUG => That DHCP parameter type is already defined ({$search})!", 3);
            $self['error'] = "ERROR => That DHCP parameter type is already defined ({$search})!";
            return(array(11, $self['error'] . "\n"));
        }

    }

    // Get the dhcp entry record before updating (logging)
    list($status, $rows, $original_entry) = ona_get_dhcp_option_entry_record(array('id' => $entry['id']));

    // Update the record
    list($status, $rows) = db_update_record($onadb, 'dhcp_option_entries', array('id' => $entry['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_entry_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Get the entry again to display details
    list($status, $tmp_rows, $entry) = ona_get_dhcp_option_entry_record(array('id' => $entry['id']));


    // Return the success notice
    $self['error'] = "INFO => DHCP entry UPDATED:{$entry['id']}: \"{$entry['display_name']}\"={$entry['value']} on {$desc} ";

    $log_msg = "INFO => DHCP entry UPDATED:{$entry['id']}: ";
    $more="";
    foreach(array_keys($original_entry) as $key) {
        if($original_entry[$key] != $entry[$key]) {
            $log_msg .= $more . $key . "[" .$original_entry[$key] . "=>" . $entry[$key] . "]";
            $more= ";";
        }
    }

    // only print to logfile if a change has been made to the record
    if($more != '') {
        printmsg($self['error'], 0);
        printmsg($log_msg, 0);
    }

    return(array(0, $self['error'] . "\n"));
}


















///////////////////////////////////////////////////////////////////////
//  Function: dhcp_entry_display (string $options='')
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
//  Example: list($status, $result) = dhcp_entry_display('host=test');
///////////////////////////////////////////////////////////////////////
function dhcp_entry_display($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_entry_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['host'] and !$options['server'] and !$options['subnet']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_entry_display-v{$version}
Displays an dhcp_entry record from the database

  Synopsis: dhcp_entry_display [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN] or id      hostname or id of the host to display
    OR
    subnet=NAME or id            description or id of the subnet to display
    OR
    server=NAME[.DOMAIN] or id    hostname or id of the server to display

  Notes:
    * DOMAIN will default to {$conf['dns']['defaultdomain']} if not specified
\n
EOM

        ));
    }

    if ($options['host']) {
        // Determine the host is valid
        list($status, $rows, $host) = ona_find_host($options['host']);

        if (!$host['id']) {
            printmsg("DEBUG => The host specified, {$options['host']}, does not exist!", 3);
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        $anchor = 'host';
        $desc = $host['FQDN'];
        $where = array('HOST_id' => $host['id']);
    } elseif ($options['subnet']) {
        // Determine the subnet is valid
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);

        if (!$subnet['id']) {
            printmsg("DEBUG => The subnet specified, {$options['subnet']}, does not exist!", 3);
            $self['error'] = "ERROR => The subnet specified, {$options['subnet']}, does not exist!";
            return(array(3, $self['error'] . "\n"));
        }

        $anchor = 'subnet';
        $desc = "{$subnet['DESCRIPTION']} (". ip_mangle($subnet['IP_ADDRESS']).")";
        $where = array('NETWORK_id' => $subnet['id']);

    } elseif ($options['server']) {
        // Determine the server is valid
        list($status, $rows, $host) = ona_find_host($options['server']);

        if (!$host['id']) {
            printmsg("DEBUG => The server specified, {$options['server']}, does not exist!", 3);
            $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
            return(array(4, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $server) = ona_get_server_record(array('HOST_id' => $host['id']));

        if (!$server['id']) {
            printmsg("DEBUG => The host specified, {$host['FQDN']}, is not a server!", 3);
            $self['error'] = "ERROR => The host specified, {$host['FQDN']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }

        $anchor = 'server';
        $desc = $host['FQDN'];
        $where = array('SERVER_id' => $server['id']);

    }


    // Debugging
    printmsg("DEBUG => dhcp_entry_display(): Found {$anchor}: {$desc}", 3);







    // Build text to return
    $text  = strtoupper($anchor) . " RECORD ({$desc})\n";

    // Display the record(s)
    $i = 0;
    do {
        list($status, $rows, $entry) = ona_get_dhcp_entry_record($where);
        if ($rows == 0) {
            $text .= "\nNO ASSOCIATED DHCP ENTRY RECORDS\n";
            break;
        }
        $i++;
        $text .= "\nASSOCIATED DHCP ENTRY RECORD ({$i} of {$rows})\n";
        $text .= format_array($entry);
    } while ($i < $rows);

    // Return the success notice
    return(array(0, $text));


}










// DON'T put whitespace at the beginning or end of this file!!!
?>
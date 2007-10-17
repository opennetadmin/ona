<?
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);

// Set up default failover information
$conf['dhcp']['response_delay']   = '60';
$conf['dhcp']['unacked_updates']  = '10';
$conf['dhcp']['max_balance']  = '3';
$conf['dhcp']['priport'] = '647';
$conf['dhcp']['peerport']   = '847';
$conf['dhcp']['mclt']  = '1800';
$conf['dhcp']['split'] = '255';




///////////////////////////////////////////////////////////////////////
//  Function: dhcp_failover_group_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    pri_server=NAME[.DOMAIN] or id
//    sec_server=NAME[.DOMAIN] or id
//    response_delay=NUMBER
//    unacked_updates=NUMBER
//    max_balance=NUMBER
//    priport=NUMBER
//    peerport=NUMBER
//    mclt=NUMBER
//    split=NUMBER
//
//  Output:
//    Adds a dhcp failover group entry into the IP database.
//
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_failover_group_add('name=host.something.com');
///////////////////////////////////////////////////////////////////////
function dhcp_failover_group_add($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => dhcp_failover_group_add({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                ($options['pri_server'] and $options['sec_server'])
                                 or
                                ($options['response_delay'] or
                                 $options['unacked_updates'] or
                                 $options['max_balance'] or
                                 $options['priport'] or
                                 $options['peerport'] or
                                 $options['mclt'] or
                                 $options['split']
                                )
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_failover_group_add-v{$version}
Adds a DHCP failover group into the database

  Synopsis: dhcp_failover_group_add [KEY=VALUE] ...

  Required:
    pri_server=NAME[.DOMAIN] or ID          identifier of the primary server
    sec_server=NAME[.DOMAIN] or ID          identifier of the secondary server
  Optional:
    response_delay=NUMBER                   Default ({$conf['dhcp']['response_delay']})
    unacked_updates=NUMBER                  Default ({$conf['dhcp']['unacked_updates']})
    max_balance=NUMBER                      Default ({$conf['dhcp']['max_balance']})
    priport=NUMBER                          Default ({$conf['dhcp']['priport']})
    peerport=NUMBER                         Default ({$conf['dhcp']['peerport']})
    mclt=NUMBER                             Default ({$conf['dhcp']['mclt']})
    split=NUMBER                            Default ({$conf['dhcp']['split']})



EOM

        ));
    }

    if ($options['pri_server']) {
        // Determine the server is valid
        list($pri_host, $tmp) = ona_find_host($options['pri_server']);

        if (!$pri_host['id']) {
            printmsg("DEBUG => The server specified, {$options['pri_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['pri_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $pri_server) = ona_get_server_record(array('host_id' => $pri_host['id']));

        if (!$pri_server['id']) {
            printmsg("DEBUG => The host specified, {$pri_host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$pri_host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }
    }

    if ($options['sec_server']) {
        // Determine the server is valid
        list($sec_host, $tmp) = ona_find_host($options['sec_server']);

        if (!$sec_host['id']) {
            printmsg("DEBUG => The server specified, {$options['sec_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['sec_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $sec_server) = ona_get_server_record(array('HOST_id' => $sec_host['id']));

        if (!$sec_server['id']) {
            printmsg("DEBUG => The host specified, {$sec_host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$sec_host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }
    }


    // The pri/sec server can not be the same
    if ($pri_server['id'] == $sec_server['id']) {
        printmsg("DEBUG => The primary server and the secondary server cannot be the same ({$pri_host['fqdn']}).",3);
        $self['error'] = "ERROR => The primary server and the secondary server cannot be the same ({$pri_host['fqdn']}).";
        return(array(9, $self['error'] . "\n"));
    }


    //TODO: does a check of pri/sec AND sec/pri need to be done? why would you have groups that are reverse of eachother?

    // Validate that this failover group doesnt already exist
    list($status, $rows, $record) = ona_get_dhcp_failover_group_record(array('primary_server_id'   => $pri_server['id'],
                                                                              'secondary_server_id' => $sec_server['id']));

    if ($rows) {
        printmsg("DEBUG => A failover group using, PRI:{$options['pri_server']} and SEC:{$options['sec_server']}, already exists!",3);
        $self['error'] = "ERROR => A failover group using, PRI:{$options['pri_server']} and SEC:{$options['sec_server']}, already exists!";
        return(array(11, $self['error'] . "\n"));
    }



    // Use default if something was not passed on command line
    if ($options['response_delay'])   { $response_delay   = $options['response_delay'];  } else { $response_delay  = $conf['dhcp']['response_delay']; }
    if ($options['unacked_updates'])  { $unacked_updates  = $options['unacked_updates']; } else { $unacked_updates = $conf['dhcp']['unacked_updates'];}
    if ($options['max_balance'])      { $max_balance  = $options['max_balance']; } else { $max_balance  = $conf['dhcp']['max_balance'];  }
    if ($options['priport'])          { $priport = $options['priport'];} else { $priport = $conf['dhcp']['priport']; }
    if ($options['peerport'])         { $peerport   = $options['peerport'];  } else { $peerport   = $conf['dhcp']['peerport'];   }
    if ($options['mclt'])             { $mclt  = $options['mclt']; } else { $mclt  = $conf['dhcp']['mclt'];  }
    if ($options['split'])            { $split = $options['split'];} else { $split = $conf['dhcp']['split']; }



    // Check permissions
    if (!auth('advanced')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next id
    $first_id = $id = ona_get_next_id('dhcp_failover_groups');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }
    printmsg("DEBUG => dhcp_failover_group_add(): New failover group id: {$id}", 3);


    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dhcp_failover_groups',
            array(
                'id'                           => $id,
                'primary_server_id'            => $pri_server['id'],
                'secondary_server_id'          => $sec_server['id'],
                'max_response_delay'           => $response_delay,
                'max_unacked_updates'          => $unacked_updates,
                'max_load_balance'             => $max_balance,
                'primary_port'                 => $priport,
                'peer_port'                    => $peerport,
                'mclt'                         => $mclt,
                'split'                        => $split
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_failover_group_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => DHCP failover group ADDED: {$id} => PRI:{$pri_host['fqdn']} SEC:{$sec_host['fqdn']}";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: dhcp_failover_group_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    id=id
//
//  Output:
//    Deletes a dhcp failover group from the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_failover_group_del('zone=test');
///////////////////////////////////////////////////////////////////////
function dhcp_failover_group_del($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => dhcp_failover_group_del({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

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

dhcp_failover_group_del-v{$version}
Deletes a DHCP failover group from the database

  Synopsis: dhcp_failover_group_del [KEY=VALUE] ...

  Required:
    id=id               id of the failover group to delete

  Optional:
    commit=[Y|N]        commit db transaction (no)
\n
EOM

        ));
    }


    // Test that the group actually exists.
    list($status, $tmp_rows, $entry) = ona_get_dhcp_failover_group_record(array('id' => $options['id']));
    if (!$entry['id']) {
        printmsg("DEBUG => Unable to find a DHCP failover group record using id {$options['id']}!",3);
        $self['error'] = "ERROR => Unable to find a DHCP failover group record using id {$options['id']}!";
        return(array(4, $self['error']. "\n"));
    }

    // Debugging
    printmsg("DEBUG => DHCP failover group selected: {$entry['id']}", 3);


    // Display an error if pools are using this zone
    list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', array('id' => $entry['id']));
    if ($rows) {
        printmsg("DEBUG => DHCP failover group ({$entry['id']}) can't be deleted, it is in use on 1 or more pools!",3);
        $self['error'] = "ERROR => DHCP failover group ({$entry['id']}) can't be deleted, it is in use on 1 or more pools!";
        return(array(5, $self['error'] . "\n"));
    }

    list($status, $rows, $pri_host) = ona_find_host($entry['primary_server_id']);
    list($status, $rows, $sec_host) = ona_find_host($entry['secondary_server_id']);



    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }


        // Delete actual zone
        list($status, $rows) = db_delete_records($onadb, 'dhcp_failover_groups', array('id' => $entry['id']));
        if ($status) {
            $self['error'] = "ERROR => dhcp_failover_group_del() SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(9, $self['error'] . "\n"));
        }


        // Return the success notice
        $self['error'] = "INFO => DHCP failover group DELETED: {$entry['id']} => PRI:{$pri_host['fqdn']} SEC:{$sec_host['fqdn']}";
        printmsg($self['error'], 0);
        return(array(0, $self['error'] . "\n"));
    }

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

     NAME:  {$entry['id']}
  PRIMARY:  {$pri_host['fqdn']}
SECONDARY:  {$sec_host['fqdn']}


EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: dhcp_failover_group_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//  Where:
//    id=id
//  Optional:
//    set_pri_server=NAME[.DOMAIN] or id
//    set_sec_server=NAME[.DOMAIN] or id
//    set_response_delay=NUMBER
//    set_unacked_updates=NUMBER
//    set_max_balance=NUMBER
//    set_priport=NUMBER
//    set_peerport=NUMBER
//    set_mclt=NUMBER
//    set_split=NUMBER
//
//  Output:
//    Updates an DHCP failover group record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_failover_group_modify('set_mclt=1111&id=host.something.com');
///////////////////////////////////////////////////////////////////////
function dhcp_failover_group_modify($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => dhcp_failover_group_modify({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                ($options['id'])
                                 and
                                ($options['set_pri_server'] or
                                 $options['set_sec_server'] or
                                 $options['set_response_delay'] or
                                 $options['set_unacked_updates'] or
                                 $options['set_max_balance'] or
                                 $options['set_priport'] or
                                 $options['set_peerport'] or
                                 $options['set_mclt'] or
                                 $options['set_split'])
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_failover_group_modify-v{$version}
Modifies a DHCP failover group in the database

  Synopsis: dhcp_failover_group_modify [KEY=VALUE] ...

  Where:
    id=id                                  id for failover record
  Optional:
    set_pri_server=NAME[.DOMAIN] or id     identifier of the primary server
    set_sec_server=NAME[.DOMAIN] or id     identifier of the secondary server
    set_response_delay=NUMBER              Default ({$conf['dhcp']['response_delay']})
    set_unacked_updates=NUMBER             Default ({$conf['dhcp']['unacked_updates']})
    set_max_balance=NUMBER                 Default ({$conf['dhcp']['max_balance']})
    set_priport=NUMBER                     Default ({$conf['dhcp']['priport']})
    set_peerport=NUMBER                    Default ({$conf['dhcp']['peerport']})
    set_mclt=NUMBER                        Default ({$conf['dhcp']['mclt']})
    set_split=NUMBER                       Default ({$conf['dhcp']['split']})


EOM
        ));
    }



    // Determine the entry itself exists
    list($status, $rows, $failovergroup) = ona_get_dhcp_failover_group_record(array('id' => $options['id']));

    // Test to see that we were able to find the specified record
    if (!$failovergroup['id']) {
        printmsg("DEBUG => Unable to find the DHCP failover group record using {$options['id']}!",3);
        $self['error'] = "ERROR => Unable to find the DHCP failover group record using {$options['id']}!";
        return(array(4, $self['error']. "\n"));
    }

    list($status, $rows, $pri_server) = ona_find_host($failovergroup['primary_server_id']);
    list($status, $rows, $sec_server) = ona_find_host($failovergroup['secondary_server_id']);


    // Debugging
    printmsg("DEBUG => dhcp_failover_group_display(): Found id:{$failovergroup['id']}", 3);


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();



    if (array_key_exists('set_pri_server',$options) and $options['set_pri_server']) {
        // Determine the server is valid
        list($pri_host, $tmp) = ona_find_host($options['set_pri_server']);

        if (!$pri_host['id']) {
            printmsg("DEBUG => The server specified, {$options['set_pri_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['set_pri_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $pri_server) = ona_get_server_record(array('host_id' => $pri_host['id']));

        if (!$pri_server['id']) {
            printmsg("DEBUG => The host specified, {$pri_host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$pri_host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }



        $SET['primary_server_id'] = $pri_server['id'];
    }

    if (array_key_exists('set_sec_server',$options) and $options['set_sec_server']) {
        // Determine the server is valid
        list($sec_host, $tmp) = ona_find_host($options['set_sec_server']);

        if (!$sec_host['id']) {
            printmsg("DEBUG => The server specified, {$options['set_sec_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['set_sec_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $sec_server) = ona_get_server_record(array('host_id' => $sec_host['id']));

        if (!$sec_server['id']) {
            printmsg("DEBUG => The host specified, {$sec_host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$sec_host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }



        $SET['secondary_server_id'] = $sec_server['id'];
    }


    // define the remaining entries
    if ($options['set_response_delay'])  $SET['max_response_delay'] = $options['set_response_delay'];
    if ($options['set_unacked_updates']) $SET['max_unacked_updates']     = $options['set_unacked_updates'];
    if ($options['set_max_balance'])     $SET['max_load_balance']            = $options['set_max_balance'];
    if ($options['set_priport'])         $SET['primary_port']            = $options['set_priport'];
    if ($options['set_peerport'])        $SET['peer_port']               = $options['set_peerport'];
    if ($options['set_mclt'])            $SET['mclt']        = $options['set_mclt'];
    if ($options['set_split'])           $SET['split'] = $options['set_split'];



    // Check permissions
    if (!auth('advanced')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the alias record before updating (logging)
    list($status, $rows, $original_fgroup) = ona_get_dhcp_failover_group_record(array('id' => $failovergroup['id']));

    // Update the record
    list($status, $rows) = db_update_record($onadb, 'dhcp_failover_groups', array('id' => $failovergroup['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_failover_group_modify() SQL Query failed: {$self['error']}";
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }

    list($status, $rows, $fgroup) = ona_get_dhcp_failover_group_record(array('id' => $failovergroup['id']));
    list($status, $rows, $pri_host) = ona_find_host($fgroup['primary_server_id']);
    list($status, $rows, $sec_host) = ona_find_host($fgroup['secondary_server_id']);

    // Return the success notice
    $self['error'] = "INFO => DHCP failover group UPDATED:{$failovergroup['id']}: PRI:{$pri_host['fqdn']} SEC:{$sec_host['fqdn']}";

    $log_msg = "INFO => DHCP failover group UPDATED:{$failovergroup['id']}: ";
    $more="";
    foreach(array_keys($original_fgroup) as $key) {
        if($original_fgroup[$key] != $fgroup[$key]) {
            $log_msg .= $more . $key . "[" .$original_fgroup[$key] . "=>" . $fgroup[$key] . "]";
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
//  Function: dhcp_failover_group_display (string $options='')
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
//  Example: list($status, $result) = dhcp_failover_group_display('id=1');
///////////////////////////////////////////////////////////////////////
function dhcp_failover_group_display($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_failover_group_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(($options['id']) or ($options['pri_server'] and $options['sec_server']))) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_failover_group_display-v{$version}
Displays an DHCP failover group record from the database

  Synopsis: dhcp_failover_group_display [KEY=VALUE] ...

  Required:
    id=id                              id of the DHCP failover group to display
    OR
    pri_server=NAME[.DOMAIN] or id     identifier of the primary server
    sec_server=NAME[.DOMAIN] or id     identifier of the secondary server



EOM

        ));
    }


    $search = array();

    if ($options['pri_server'] and $options['sec_server']) {
        // Determine the server is valid
        list($pri_host, $tmp) = ona_find_host($options['pri_server']);

        if (!$pri_host['id']) {
            printmsg("DEBUG => The server specified, {$options['pri_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['pri_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $pri_server) = ona_get_server_record(array('host_id' => $pri_host['id']));

        if (!$pri_server['id']) {
            printmsg("DEBUG => The host specified, {$pri_host['FQDN']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$pri_host['FQDN']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }

        // Determine the server is valid
        list($sec_host, $tmp) = ona_find_host($options['sec_server']);

        if (!$sec_host['id']) {
            printmsg("DEBUG => The server specified, {$options['sec_server']}, does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['sec_server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

        // Determine the host that was found is actually a server
        list($status, $rows, $sec_server) = ona_get_server_record(array('HOST_id' => $sec_host['id']));

        if (!$sec_server['id']) {
            printmsg("DEBUG => The host specified, {$sec_host['fqdn']}, is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$sec_host['fqdn']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }

        $search['primary_server_id']   = $pri_server['id'];
        $search['secondary_server_id'] = $sec_server['id'];
    }




    if ($options['id']) {
        $search['id'] = $options['id'];
    }

    // Determine the entry itself exists
    list($status, $rows, $failovergroup) = ona_get_dhcp_failover_group_record($search);

    // Test to see that we were able to find the specified record
    if (!$failovergroup['id']) {
        printmsg("DEBUG => Unable to find the DHCP failover group record using {$options['id']}!",3);
        $self['error'] = "ERROR => Unable to find the DHCP failover group record using {$options['id']}!";
        return(array(4, $self['error']. "\n"));
    }

    list($pri_server, $pri_zone) = ona_find_host($failovergroup['primary_server_id']);
    list($sec_server, $sec_zone) = ona_find_host($failovergroup['secondary_server_id']);
    $failovergroup['pri_server_name'] = $pri_server['fqdn'];
    $failovergroup['sec_server_name'] = $sec_server['fqdn'];



    // Debugging
    printmsg("DEBUG => dhcp_failover_group_display(): Found id:{$failovergroup['id']}", 3);


    // Build text to return
    $text  = "DHCP FAILOVER GROUP RECORD:\n";
    $text .= format_array($failovergroup);



    // Return the success notice
    return(array(0, $text));


}










// DON'T put whitespace at the beginning or end of this file!!!
?>
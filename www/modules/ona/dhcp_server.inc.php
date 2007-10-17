<?
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);



///////////////////////////////////////////////////////////////////////
//  Function: dhcp_server_add (string $options='')
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
//  Example: list($status, $result) = dhcp_server_add('subnet=&server=hp34.something.com');
///////////////////////////////////////////////////////////////////////
function dhcp_server_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_server_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help']
            or
        !($options['subnet'] and $options['server'])
        ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_server_add-v{$version}
Assigns an existing subnet record to a DHCP server

  Synopsis: dhcp_server_add [KEY=VALUE] ...

  Required:
    subnet=NAME or ID               subnet name or ID
    server=NAME[.DOMAIN] or ID      server name or ID

  Notes:
    DOMAIN will default to .something.com if not specified

EOM

        ));
    }


    if (is_numeric($options['subnet'])) {
        $subnetsearch['id'] = $options['subnet'];
    } else {
        $subnetsearch['name'] = strtoupper($options['subnet']);
    }

    // Determine the entry itself exists
    list($status, $rows, $subnet) = ona_get_subnet_record($subnetsearch);

    // Test to see that we were able to find the specified record
    if (!$subnet['id']) {
        printmsg("DEBUG => Unable to find the subnet record using {$options['subnet']}!",3);
        $self['error'] = "ERROR => Unable to find the subnet record using {$options['subnet']}!";
        return(array(4, $self['error']. "\n"));
    }

    printmsg("DEBUG => dhcp_server_add(): Found subnet, {$subnet['name']}", 3);

    // Determine the server is valid
    list($status, $rows, $host) = ona_find_host($options['server']);

    if (!$host['id']) {
        printmsg("DEBUG => The server ({$options['server']}) does not exist!",3);
        $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }

    // Check permissions
    if (!auth('advanced') or !authlvl($host['LVL']) or !authlvl($subnet['LVL'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(12, $self['error'] . "\n"));
    }

// FIXME: MP commented out for now.. not using server table or anything, plan to remove this later

    // Determine the host that was found is actually a server
//    list($status, $rows, $server) = ona_get_server_record(array('HOST_ID' => $host['ID']));

//     $add_to_error = "";
//     if (!$server['ID']) {
//         // NEED TO ADD THIS HOST AS A SERVER TO THE SERVER_B TABLE
//         // Get the next ID
//         printmsg("DEBUG => dhcp_server_add(): Need to make the host ({$host['FQDN']}) a server", 3);
//         $server_id = ona_get_next_id();
//         if (!$server_id) {
//             $self['error'] = "ERROR => The ona_get_next_id() call failed!";
//             printmsg($self['error'],0);
//             return(array(6, $self['error'] . "\n"));
//         }
//         printmsg("DEBUG => dhcp_server_add(): New dhcp server ID: $server_id", 3);
//
//         // Add new record to server_b
//         list($status, $rows) =
//             db_insert_record(
//                                 $onadb,
//                                 'SERVER_B',
//                                 array(
//                                     'ID'                        => $server_id,
//                                     'DHCP_SERVER'               => 'Y',
//                                     'DNS_SERVER'                => 'N',
//                                     'SERVER_TYPE'               => 'ISC',
//                                     'HOST_ID'                   => $host['ID']
//                                 )
//                             );
//         if ($status or !$rows) {
//             $self['error'] = "ERROR => dhcp_server_add() SQL Query failed:" . $self['error'];
//             printmsg($self['error'],0);
//             return(array(8, $self['error'] . "\n"));
//         }
//         $server['ID']=$server_id;
//         printmsg("INFO => Server Record ADDED: {$host['FQDN']} as server ID={$server_id}", 0);
//         $add_to_error .= "INFO => Server Record ADDED: {$host['FQDN']} as server ID={$server_id}\n";
//     }
//     else {
//         // server ID already exists
//         printmsg("DEBUG => dhcp_server_add(): Found server, {$host['FQDN']}", 3);
//
//         // Test that this subnet isnt already assigned to the server
//         list($status, $rows, $dhcpserver) = ona_get_dhcp_server_subnet_record(array('SERVER_ID' => $server['ID'],'SUBNET_ID' => $subnet['ID']));
//         if ($rows) {
//             printmsg("DEBUG => Subnet {$subnet['DESCRIPTION']} already assigned to {$host['FQDN']}",3);
//             $self['error'] = "ERROR => Subnet {$subnet['DESCRIPTION']} already assigned to {$host['FQDN']}";
//             return(array(11, $self['error'] . "\n"));
//         }
//     }

    // Test that this subnet isnt already assigned to the server
    list($status, $rows, $dhcpserver) = ona_get_dhcp_server_subnet_record(array('host_id' => $host['id'],'subnet_id' => $subnet['id']));
    if ($rows) {
        printmsg("DEBUG => Subnet {$subnet['name']} already assigned to {$host['fqdn']}",3);
        $self['error'] = "ERROR => Subnet {$subnet['name']} already assigned to {$host['fqdn']}";
        return(array(11, $self['error'] . "\n"));
    }


    // Get the next ID
    $id = ona_get_next_id('dhcp_server_subnets');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(6, $add_to_error . $self['error'] . "\n"));
    }

    printmsg("DEBUG => dhcp_server_add(): New dhcp server subnet ID: $id", 3);

    // Add new record to dhcp_server_subnets_b
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dhcp_server_subnets',
            array(
                'id'                      => $id,
                'host_id'                 => $host['id'],
                'subnet_id'               => $subnet['id']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_server_add() SQL Query failed:" . $self['error'];
        printmsg($self['error'],0);
        return(array(8, $add_to_error . $self['error'] . "\n"));
    }


    // Return the success notice
    $self['error'] = "INFO => DHCP Subnet/Server Pair ADDED: {$subnet['name']}/{$host['fqdn']} ";
    printmsg($self['error'],0);
    return(array(0, $add_to_error . $self['error'] . "\n"));


}






///////////////////////////////////////////////////////////////////////
//  Function: dhcp_server_del (string $options='')
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
//  Example: list($status, $result) = dhcp_server_del('subnet=TEST-MODIFY-ADD&server=test-server.something.com');
///////////////////////////////////////////////////////////////////////
function dhcp_server_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => dhcp_server_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is yes)
    $options['commit'] = sanitize_YN($options['commit'], 'N');


    // Return the usage summary if we need to
    if ($options['help'] or !($options['subnet'] and $options['server']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_server_del-v{$version}
Removes a subnet record from a DHCP server

  Synopsis: dhcp_server_del [KEY=VALUE] ...

  Required:
    subnet=NAME or ID               subnet name or ID
    server=NAME[.DOMAIN] or ID      server name or ID

  Optional:
    commit=[Y|N]                    commit db transaction (no)

  Notes:
    DOMAIN will default to .something.com if not specified


EOM

        ));
    }


    if (is_numeric($options['subnet'])) {
        $subnetsearch['id'] = $options['subnet'];
    } else {
        $subnetsearch['name'] = strtoupper($options['subnet']);
    }

    // Determine the entry itself exists
    list($status, $rows, $subnet) = ona_get_subnet_record($subnetsearch);

    // Test to see that we were able to find the specified record
    if (!$subnet['id']) {
        printmsg("DEBUG => Unable to find the subnet record using {$options['subnet']}!",3);
        $self['error'] = "ERROR => Unable to find the subnet record using {$options['subnet']}!";
        return(array(4, $self['error']. "\n"));
    }

    printmsg("DEBUG => dhcp_server_del(): Found subnet, {$subnet['name']}", 3);

    if ($options['server']) {
        // Determine the server is valid
        list($status, $rows, $host) = ona_find_host($options['server']);

        if (!$host['id']) {
            printmsg("DEBUG => The server ({$options['server']}) does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }

// FIXME: MP commented out for now.. not using server table or anything, plan to remove this later
/*        // Determine the host that was found is actually a server
        list($status, $rows, $server) = ona_get_server_record(array('HOST_ID' => $host['ID']));

        if (!$server['ID']) {
            printmsg("DEBUG => The host ({$options['server']}) is not a server!",3);
            $self['error'] = "ERROR => The host specified, {$host['FQDN']}, is not a server!";
            return(array(5, $self['error'] . "\n"));
        }*/
    }

    //printmsg("DEBUG => dhcp_server_del(): Found server, {$host['FQDN']}", 3);

    // Test that this subnet is even assigned to the server
    list($status, $rows, $dhcpserver) = ona_get_dhcp_server_subnet_record(array('host_id' => $host['id'],'subnet_id' => $subnet['id']));
    if (!$rows) {
        printmsg("DEBUG => Unable to find {$subnet['name']} on server {$host['fqdn']}",3);
        $self['error'] = "ERROR => Unable to find {$subnet['name']} on server {$host['fqdn']}";
        return(array(11, $self['error'] . "\n"));
    }


    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced') or !authlvl($host['LVL']) or !authlvl($subnet['LVL'])) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }





        // check if allowed to remove subnet from server
        // check for pool assigned to the server itself
        list($status, $rows, $pools) = db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));
        foreach($pools as $pool) {
// FIXME: MP commented out for now.  server is  not directly associated with pool now.  that is done via the subnet. I.E. this record we are deleting
//             if ($pool['SERVER_ID'] == $server['ID']) {
//                 printmsg("DEBUG => Subnet ({$subnet['DESCRIPTION']}) has a pool assigned to this Server ({$host['FQDN']})",3);
//                 $self['error'] = "ERROR => Subnet ({$subnet['DESCRIPTION']}) has a pool assigned to this Server ({$host['FQDN']})";
//                 return(array(12, $self['error'] . "\n"));
//             }
            if ($pool['dhcp_failover_group_id']) {
                $foundfg = 0;
                list($status, $rows, $primary)   = ona_get_dhcp_failover_group_record(array('id' => $pool['dhcp_failover_group_id'],'primary_server_id' => $host['id']));
                if ($rows) $foundfg++;
                list($status, $rows, $secondary) = ona_get_dhcp_failover_group_record(array('id' => $pool['dhcp_failover_group_id'],'secondary_server_id' => $host['id']));
                if ($rows) $foundfg++;

                // if a subnet/server pair is found in dhcp pools, don't allow removal
                if ($foundfg > 0) {
                    printmsg("DEBUG => Subnet ({$subnet['name']}) has a pool assigned to this Server ({$host['fqdn']}), which is part of a failover group.  The server must be removed from the failover group first.",3);
                    $self['error'] = "ERROR => Subnet ({$subnet['name']}) has a pool assigned to this Server ({$host['fqdn']}), which is part of a failover group.  The server must be removed from the failover group first.";
                    return(array(12, $self['error'] . "\n"));
                }

            }
        }





        // check if there are any DHCP parameters assigned to the subnet
        list($status, $rows, $data) = ona_get_dhcp_option_entry_record(array('subnet_id' => $subnet['id']));

        // if so, check that this is not the last DHCP server that services this subnet
        if ($rows > 0) {
            list($status, $rows, $data) = ona_get_dhcp_server_subnet_record(array('subnet_id' => $subnet['id']));

            // If this is the last DHCP server that services this subnet, don't allow removal until DHCP parameters are removed
            if($rows <= 1){
                printmsg("DEBUG => Subnet ({$subnet['name']}) has DHCP parameters assigned which need to be removed first",3);
                $self['error'] = "ERROR => Subnet ({$subnet['name']}) has DHCP parameters assigned which need to be removed first";
                return(array(12, $self['error'] . "\n"));
            }
        }


        // delete record from dhcp_server_subnets
        list($status, $rows) = db_delete_records($onadb, 'dhcp_server_subnets', array('id' => $dhcpserver['id']));
        if ($status) {
            $self['error'] = "ERROR => dhcp_server_del() SQL Query failed:" . $self['error'];
            printmsg($self['error'],0);
            return(array(9, $self['error'] . "\n"));
        }


        // Return the success notice
        $self['error'] = "INFO => DHCP Subnet/Server Pair DELETED: {$subnet['name']}/{$host['fqdn']} ";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
    Record(s) NOT DELETED (see "commit" option)
    Displaying record(s) that would have been removed:

    {$subnet['name']} from: {$host['fqdn']}

EOL;

    return(array(6, $text));


}




// DON'T put whitespace at the beginning or end of this file!!!
?>
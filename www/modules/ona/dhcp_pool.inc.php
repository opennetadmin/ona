<?php
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);



// setup defaults for pool
$conf['dhcp_pool']['llength']     = '604800';
$conf['dhcp_pool']['lgrace']      = '0';
$conf['dhcp_pool']['lrenewal']    = '0';
$conf['dhcp_pool']['lrebind']     = '0';
$conf['dhcp_pool']['allow_bootp'] = '0';

///////////////////////////////////////////////////////////////////////
//  Function: dhcp_pool_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    failover_group=failover_group_id
//    OR
//    server=NAME[.DOMAIN] or id
//    AND
//    start=start ip addres
//    end=end ip address
//    llength=lease time
//
//  Output:
//    Adds a DHCP pool into the IP database using specified 'server' OR
//    'failover_group'.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_pool_add('server=q1234.something.com&start=10.1.1.1&end=10.1.1.5');
///////////////////////////////////////////////////////////////////////
function dhcp_pool_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.03';

    printmsg("DEBUG => dhcp_pool_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                (
                                 $options['start'] and
                                 $options['end']
                                )
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_pool_add-v{$version}
Adds a dhcp pool into the database pointing to the specified identifier

  Synopsis: dhcp_pool_add [KEY=VALUE] ...

  Identifier (pick one):
    failover_group=ID               group identifier to add to

  Required:
    start=IP                        IP address for start of pool range
    end=IP                          IP address for end of pool range

  Optional:
    llength=NUMBER                  Length in seconds for leases ({$conf['dhcp_pool']['llength']})

\n
EOM

        ));
    }

    // set the lease time if it was passed. otherwise, use the default
    $llength = ($options['llength']) ? $options['llength'] : $conf['dhcp_pool']['llength'];

    // make it blank for now.
    $failovergroupid = 0;

    // make sure that the start address is actually part of an existing subnet
    list($status, $rows, $subnet) = ona_find_subnet(ip_mangle($options['start'], 'dotted'));
    if (!$rows) {
        printmsg("DEBUG => Unable to find a subnet related to starting address ({$options['start']}).",3);
        $self['error'] = "ERROR => Unable to find a subnet related to starting address ({$options['start']}).";
        return(array(1, $self['error'] . "\n"));
    }



    if ($options['failover_group']) {
        list($status, $rows, $fg) = ona_get_dhcp_failover_group_record(array('id' => $options['failover_group']));

        if (!$fg['id']) {
            printmsg("DEBUG => The failover_group ({$options['failover_group']}) does not exist!",3);
            $self['error'] = "ERROR => The failover_group ({$options['failover_group']}) does not exist!";
            return(array(4, $self['error'] . "\n"));
        }

        // get the server names for the two servers
        list($fail_host1, $fail_zone1) = ona_find_host($fg['primary_server_id']);
        list($fail_host2, $fail_zone2) = ona_find_host($fg['secondary_server_id']);
        $desc = $fail_host1['fqdn'] .'/'. $fail_host2['fqdn'];

        $failovergroupid = $fg['id'];
    }

    // check that start and end are not the same
    //if ($options['start'] == $options['end']) {
     //   printmsg("DEBUG => The start and end IP addresses ({$options['start']}) cannot be the same!",3);
      //  $self['error'] = "ERROR => The start and end IP addresses ({$options['start']}) cannot be the same!";
       // return(array(2, $self['error'] . "\n"));
    //}


    $start_dec = ip_mangle($options['start'], 'numeric');
    $end_dec   = ip_mangle($options['end'], 'numeric');
    $net_end = ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']);

    // Validate that the IP address supplied isn't the base or broadcast of the subnet
    if ($start_dec == $subnet['ip_addr'] or $end_dec == $subnet['ip_addr']) {
        printmsg("DEBUG => IP address can't be a subnet's base address (" . ip_mangle($subnet['ip_addr'],'dotted') . ")!",3);
        $self['error'] = "ERROR => IP address can't be a subnet's base address(" . ip_mangle($subnet['ip_addr'],'dotted') . ")!";
        return(array(7, $self['error'] . "\n"));
    }

    if ($start_dec == $net_end or $end_dec == $net_end) {
        printmsg("DEBUG => IP address can't be a subnet's broadcast address (" . ip_mangle($net_end,'dotted') . ")!",3);
        $self['error'] = "ERROR => IP address can't be the subnet broadcast address (" . ip_mangle($net_end,'dotted') . ")!";
        return(array(8, $self['error'] . "\n"));
    }

    // check that start is not after the end
    if ($start_dec > $end_dec) {
        printmsg("ERROR => The start IP address ({$options['start']}) falls after the end IP address ({$options['end']})!",3);
        $self['error'] = "ERROR => The start IP addresses ({$options['start']}) falls after the end IP address ({$options['end']})!";
        return(array(2, $self['error'] . "\n"));
    }


    // check for existing hosts inside the pool range
    list($status, $rows, $interface) = db_get_records($onadb, 'interfaces', 'subnet_id = '.$subnet['id'].' AND ip_addr BETWEEN '.$start_dec.' AND '.$end_dec, '',0);
    if ($rows) {
        printmsg("DEBUG => IP conflict: Specified range ({$options['start']}-{$options['end']}) encompasses  {$rows} host(s).", 3);
        $self['error'] = "ERROR => IP conflict: Specified range ({$options['start']}-{$options['end']}) encompasses {$rows} host(s)";
        return(array(4, $self['error'] . "\n"));
    }






    // *** Check to see if the new pool overlaps any existing pools *** //
    // Look for overlaps like this (where new pool address starts inside an existing pool):
    //            [ -- new pool -- ]
    //    [ -- old pool --]
    list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', $start_dec.' BETWEEN ip_addr_start AND ip_addr_end');
    if ($rows != 0) {
        printmsg("DEBUG => Pool address conflict! New pool ({$options['start']}-{$options['end']}) starts inside an existing pool.", 3);
        $self['error'] = "ERROR => Pool address conflict! New pool ({$options['start']}-{$options['end']}) starts inside an existing pool.";
        return(array(5, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$pool['id']}\n"));
    }


    // Look for overlaps like this (where the new pool ends inside an existing pool):
    //    [ -- new pool -- ]
    //           [ -- old pool --]
    list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', $end_dec.' BETWEEN ip_addr_start AND ip_addr_end');
    if ($rows != 0) {
        printmsg("DEBUG => Pool address conflict! New pool ({$options['start']}-{$options['end']}) ends inside an existing pool.", 3);
        $self['error'] = "ERROR => Pool address conflict! New pool ({$options['start']}-{$options['end']}) ends inside an existing pool.";
        return(array(6, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$pool['id']}\n"));
    }



    // Look for overlaps like this (where the new pool entirely overlaps an existing pool):
    //    [ -------- new pool --------- ]
    //           [ -- old pool --]
    list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', 'ip_addr_start BETWEEN '.$start_dec.' AND '.$end_dec.' OR ip_addr_end BETWEEN '.$start_dec.' AND '.$end_dec);
    if ($rows != 0) {
        printmsg("DEBUG => Pool address conflict! New pool ({$options['start']}-{$options['end']}) would encompass an existing pool.", 3);
        $self['error'] = "ERROR => Pool address conflict! New pool ({$options['start']}-{$options['end']}) would encompass an existing pool.";
        return(array(7, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$pool['id']}\n"));
    }




    // Check permissions
    if (!auth('advanced') or !authlvl($subnet['lvl'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(8, $self['error'] . "\n"));
    }




    // Get the next id
    $id = ona_get_next_id('dhcp_pools');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'], 0);
        return(array(9, $add_to_error . $self['error'] . "\n"));
    }
    printmsg("DEBUG => dhcp_pool_add(): New ID: $id", 3);

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dhcp_pools',
            array(
                'id'                            => $id,
                'subnet_id'                     => $subnet['id'],
                'dhcp_failover_group_id'        => $failovergroupid,
                'ip_addr_start'                 => $start_dec,
                'ip_addr_end'                   => $end_dec,
                'lease_length'                  => $llength,
                'lease_grace_period'            => $conf['dhcp_pool']['lgrace'],
                'lease_renewal_time'            => $conf['dhcp_pool']['lrenewal'],
                'lease_rebind_time'             => $conf['dhcp_pool']['lrebind'],
                'allow_bootp_clients'           => $conf['dhcp_pool']['allow_bootp']

            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_pool_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(10, $add_to_error .  $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => DHCP pool ADDED: {$options['start']}->{$options['end']}.";
    printmsg($self['error'],0);
    return(array(0, $add_to_error . $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: dhcp_pool_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    id=ID
//
//  Output:
//    Deletes a dhcp pool from the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_entry_add('host=test');
///////////////////////////////////////////////////////////////////////
function dhcp_pool_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_pool_del({$options}) called", 3);

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

dhcp_pool_del-v{$version}
Deletes a DHCP pool from the database

  Synopsis: dhcp_pool_del [KEY=VALUE] ...

  Required:
    id=ID                      ID of the DHCP pool to delete

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

        list($status, $tmp_rows, $pool) = ona_get_dhcp_pool_record(array('id' => $options['id']));

        // Test to see that we were able to find the specified pool record
        if (!$pool['id']) {
            printmsg("DEBUG => Unable to find the DHCP pool record using ID: {$options['id']}!",3);
            $self['error'] = "ERROR => Unable to find the DHCP pool record using ID: {$options['id']}!";
            return(array(2, $self['error']. "\n"));
        }

        $start = ip_mangle($pool['ip_addr_start'], 'dotted');
        $end   = ip_mangle($pool['ip_addr_end'], 'dotted');
        list($status, $tmp_rows, $subnet) = ona_get_subnet_record(array('id' => $pool['subnet_id']));


    } else {
            printmsg("DEBUG => {$options['id']} is not a numeric value!",3);
            $self['error'] = "ERROR => {$options['id']} is not a numeric value";
            return(array(3, $self['error'] . "\n"));
    }


    // If "commit" is yes, delte the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced') or !authlvl($subnet['lvl'])) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'dhcp_pools', array('id' => $pool['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => dhcp_pool_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(5, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => DHCP pool DELETED: {$start}-{$end} from {$subnet['name']}.";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    Delete the following dhcp pool:
    ENTRY: {$start}=>{$end} from {$subnet['name']}

EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: dhcp_pool_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//  Where:
//    pool=ID                   Table ID for the pool in dhcp_pool_b

//  Optional:
//    set_start=IP          Start ip address of pool
//    set_end=IP            End IP of pool
//    set_llength=NUMBER      Lease Time. Default ({$conf['dhcp_pool']['llength']})
//    set_lgrace=NUMBER     Lease Grace Period. Default ({$conf['dhcp_pool']['lgrace']})
//    set_lrenewal=NUMBER   Lease Renewal. Default ({$conf['dhcp_pool']['lrenewal']})
//    set_lrebind=NUMBER    Lease Rebind. Default ({$conf['dhcp_pool']['lrebind']})
//
//  Output:
//    Updates an dhcp_pool record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_pool_modify('pool=23243,set_start=10.1.1.10');
///////////////////////////////////////////////////////////////////////
function dhcp_pool_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.03';

    printmsg("DEBUG => dhcp_pool_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                ($options['pool'])
                                 and
                                (
                                 $options['set_failover_group'] or
                                 $options['set_start'] or
                                 $options['set_end'] or
                                 $options['set_llength'] or
                                 $options['set_lgrace'] or
                                 $options['set_lrenewal'] or
                                 $options['set_lrebind']
                                )
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_pool_modify-v{$version}
Updates a dhcp pool in the database pointing to the specified identifier

  Synopsis: dhcp_pool_modify [KEY=VALUE] ...

  Where:
    pool=ID                             Table ID for the pool

  Optional:
    set_failover_group=ID               group identifier
    set_server=NAME[.DOMAIN] or ID      server identifier
    set_start=IP                        Start ip address of pool
    set_end=IP                          End IP of pool
    set_llength=NUMBER                  Lease Time. Default ({$conf['dhcp_pool']['llength']})
    set_lgrace=NUMBER                   Lease Grace Period. Default ({$conf['dhcp_pool']['lgrace']})
    set_lrenewal=NUMBER                 Lease Renewal. Default ({$conf['dhcp_pool']['lrenewal']})
    set_lrebind=NUMBER                  Lease Rebind. Default ({$conf['dhcp_pool']['lrebind']})

\n
EOM

        ));
    }


    // get the existing pool to edit
    list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', array('id' => $options['pool']));
    if (!$rows) {
        printmsg("DEBUG => Unable to find the DHCP pool record using id: {$options['id']}!",3);
        $self['error'] = "ERROR => Unable to find a pool using id: {$options['pool']}";
        return(array(1, $self['error'] . "\n"));
    }

    // set the pool id in the set variable
    $SET['id'] = $pool['id'];

    // NOTE: currently modify pool does not allow you to change subnets
    // Get subnet info..
    list($status, $rows, $subnet) = ona_find_subnet($pool['subnet_id']);
    $SET['subnet_id'] = $subnet['id'];

    // make sure that the start address is actually part of an existing subnet
    if($options['set_start']) {
        list($status, $rows, $subnetstart) = ona_find_subnet(ip_mangle($options['set_start'], 'dotted'));
        if (!$rows) {
            printmsg("DEBUG => Unable to find a subnet related to starting address ({$options['set_start']})!",3);
            $self['error'] = "ERROR => Unable to find a subnet related to your starting address of {$options['set_start']}.";
            return(array(1, $self['error'] . "\n"));
        }

        if ($subnetstart['id'] != $pool['subnet_id']) {
            printmsg("DEBUG => The starting address ({$options['set_start']}) is not on the same subnet of the pool ({$pool['id']}) you are editing!",3);
            $self['error'] = "ERROR => The starting address ({$options['set_start']}) is not on the same subnet of the pool ({$pool['id']}) you are editing!";
            return(array(1, $self['error'] . "\n"));
        }
    }

    // make sure that the end address is actually part of an existing subnet
    if($options['set_end']) {
        list($status, $rows, $subnetend) = ona_find_subnet(ip_mangle($options['set_end'], 'dotted'));
        if (!$rows) {
            printmsg("DEBUG => Unable to find a subnet related to ending address ({$options['set_end']})!",3);
            $self['error'] = "ERROR => Unable to find a subnet related to your ending address of {$options['set_end']}.";
            return(array(1, $self['error'] . "\n"));
        }

        if ($subnetend['id'] != $pool['subnet_id']) {
            printmsg("DEBUG => The ending address ({$options['set_end']}) is not on the same subnet of the pool ({$pool['id']}) you are editing!",3);
            $self['error'] = "ERROR => The ending address ({$options['set_end']}) is not on the same subnet of the pool ({$pool['id']}) you are editing!";
            return(array(1, $self['error'] . "\n"));
        }
    }

    // Assign which failover group to use
    if ($options['set_failover_group'] == 0) {
        $desc = 'Not using a failover group';
        $SET['dhcp_failover_group_id'] = 0;
    }
    else {
        list($status, $rows, $fg) = ona_get_dhcp_failover_group_record(array('id' => $options['set_failover_group']));

        if (!$fg['id']) {
            printmsg("DEBUG => The failover_group specified ({$options['set_failover_group']}) does not exist",3);
            $self['error'] = "ERROR => The failover_group specified ({$options['set_failover_group']}) does not exist!";
            return(array(4, $self['error'] . "\n"));
        }

        // get the server names for the two servers
        list($fail_host1, $fail_zone1) = ona_find_host($fg['primary_server_id']);
        list($fail_host2, $fail_zone2) = ona_find_host($fg['secondary_server_id']);

        $desc = $fail_host1['fqdn'] .'/'. $fail_host2['fqdn'];
        $SET['dhcp_failover_group_id'] = $fg['id'];
    }

    // check that start and end are not the same
    //if ($options['set_start'] and $options['set_end'] and $options['set_start'] == $options['set_end']) {
    //    printmsg("DEBUG => The start and end IP addresses (" . ip_mangle($options['set_start'],'dotted') . ") cannot be the same!",3);
    //    $self['error'] = "ERROR => The start and end IP addresses (" . ip_mangle($options['set_start'],'dotted') . ") cannot be the same!";
    //    return(array(2, $self['error'] . "\n"));
    //}

    if($options['set_start'])
        $start_dec = ip_mangle($options['set_start'], 'numeric');
    else
        $start_dec = $pool['ip_addr_start'];

    if($options['set_end'])
        $end_dec   = ip_mangle($options['set_end'], 'numeric');
    else
        $end_dec   = $pool['ip_addr_end'];

    $net_end   = ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']);

    // Validate that the IP address supplied isn't the base or broadcast of the subnet
    if ($start_dec == $subnet['ip_addr'] or $end_dec == $subnet['ip_addr']) {
        printmsg("DEBUG => IP address can't be a subnet's base address (" . ip_mangle($subnet['ip_addr'],'dotted') . ")!",3);
        $self['error'] = "ERROR => IP address can't be a subnet's base address (" . ip_mangle($subnet['ip_addr'],'dotted') . ")!";
        return(array(7, $self['error'] . "\n"));
    }

    if ($start_dec == $net_end or $end_dec == $net_end) {
        printmsg("DEBUG => IP address can't be a subnet's broadcast address (" . ip_mangle($net_end,'dotted') . ")!",3);
        $self['error'] = "ERROR => IP address can't be the subnet broadcast address(" . ip_mangle($net_end,'dotted') . ")!";
        return(array(8, $self['error'] . "\n"));
    }

    // check that start is not after the end
    if ($start_dec > $end_dec) {
        printmsg("DEBUG => The start IP addresses (" . ip_mangle($start_dec,'dotted') . ") falls after the end IP address (" . ip_mangle($end_dec,'dotted') . ")!",3);
        $self['error'] = "ERROR => The start IP addresses (" . ip_mangle($start_dec,'dotted') . ") falls after the end IP address(" . ip_mangle($end_dec,'dotted') . ")!";
        return(array(2, $self['error'] . "\n"));
    }


    // check for existing hosts inside the pool range
    list($status, $rows, $interface) = db_get_records($onadb, 'interfaces', 'subnet_id = '.$subnet['id'].' AND ip_addr BETWEEN '.$start_dec.' AND '.$end_dec, '',0);
    if ($rows) {
        printmsg("DEBUG => IP conflict: Specified range (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") encompasses {$rows} host(s)!",3);
        $self['error'] = "ERROR => IP conflict: Specified range (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") encompasses {$rows} host(s)";
        return(array(4, $self['error'] . "\n"));
    }






    // *** Check to see if the new pool overlaps any existing pools *** //
    // Look for overlaps like this (where new pool address starts inside an existing pool):
    //            [ -- new pool -- ]
    //    [ -- old pool --]
    list($status, $rows, $tmp) = db_get_record($onadb, 'dhcp_pools', 'id != '. $SET['id']. ' AND '.$start_dec.' BETWEEN ip_addr_start AND ip_addr_end');
    if ($rows != 0) {
        printmsg("DEBUG =>  Pool address conflict: New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") starts inside an existing pool!",3);
        $self['error'] = "ERROR => Pool address conflict! New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") starts inside an existing pool.";
        return(array(5, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$tmp['id']}\n"));
    }


    // Look for overlaps like this (where the new pool ends inside an existing pool):
    //    [ -- new pool -- ]
    //           [ -- old pool --]
    list($status, $rows, $tmp) = db_get_record($onadb, 'dhcp_pools', 'id != '. $SET['id']. ' AND '.$end_dec.' BETWEEN ip_addr_start AND ip_addr_end');
    if ($rows != 0) {
        printmsg("DEBUG =>  Pool address conflict: New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") ends inside an existing pool!",3);
        $self['error'] = "ERROR => Pool address conflict! New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") ends inside an existing pool.";
        return(array(6, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$tmp['id']}\n"));
    }



    // Look for overlaps like this (where the new pool entirely overlaps an existing pool):
    //    [ -------- new pool --------- ]
    //           [ -- old pool --]
    list($status, $rows, $tmp) = db_get_record($onadb, 'dhcp_pools', 'id != '. $SET['id']. ' AND (ip_addr_start BETWEEN '.$start_dec.' AND '.$end_dec.' OR ip_addr_end BETWEEN '.$start_dec.' AND '.$end_dec.')');
    if ($rows != 0) {
        printmsg("DEBUG =>  Pool address conflict: New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") would encompass an existing pool!",3);
        $self['error'] = "ERROR => Pool address conflict! New pool (" . ip_mangle($start_dec,'dotted') . "-" . ip_mangle($end_dec,'dotted') . ") would encompass an existing pool.";
        return(array(7, $self['error'] . "\n" .
                        "INFO  => Conflicting pool record ID: {$tmp['id']}\n"));
    }




    // Check permissions
    if (!auth('advanced') or !authlvl($subnet['lvl'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(8, $self['error'] . "\n"));
    }

    // define the remaining entries
    if (array_key_exists('set_lgrace',$options))    $SET['lease_grace_period']     = $options['set_lgrace'];
    if (array_key_exists('set_llength',$options))   $SET['lease_length']           = $options['set_llength'];
    if (array_key_exists('set_lrenewal',$options))  $SET['lease_renewal_time']     = $options['set_lrenewal'];
    if (array_key_exists('set_lrebind',$options))   $SET['lease_rebind_time']      = $options['set_lrebind'];

    // Set the IPs if you got this far
    $SET['ip_addr_start'] = $start_dec;
    $SET['ip_addr_end']   = $end_dec;


    // Get the DHCP pool record before updating (logging)
    list($status, $rows, $original_pool) = ona_get_dhcp_pool_record(array('id' => $SET['id']));

    // Update the record
    list($status, $rows) = db_update_record($onadb, 'dhcp_pools', array('id' => $SET['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => dhcp_pool_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $add_to_error . $self['error'] . "\n"));
    }
    $success_start = ip_mangle($SET['ip_addr_start'],'dotted');
    $success_end = ip_mangle($SET['ip_addr_end'],'dotted');

    // Get the DHCP pool record after updating (logging)
    list($status, $rows, $new_pool) = ona_get_dhcp_pool_record(array('id' => $SET['id']));

    // Return the success notice
    $self['error'] = "INFO => DHCP pool UPDATED:{$original_pool['id']}: {$success_start}-{$success_end} on {$subnet['name']}.";

    $log_msg = "INFO => DHCP pool UPDATED:{$original_pool['id']}: ";
    $more="";
    foreach(array_keys($original_pool) as $key) {
        if($original_pool[$key] != $new_pool[$key]) {
            $log_msg .= $more . $key . "[" .$original_pool[$key] . "=>" . $new_pool[$key] . "]";
            $more= ";";
        }
    }

    // only print to logfile if a change has been made to the record
    if($more != '') {
        printmsg($self['error'], 0);
        printmsg($log_msg, 0);
    }

    return(array(0, $add_to_error . $self['error'] . "\n"));
}


















///////////////////////////////////////////////////////////////////
//  Function: dhcp_lease_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    raw=raw text from dhcp log
//
//  Output:
//    Adds a DHCP lease entry into the database using specified IP.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dhcp_lease_add('raw=DHCPACK on 10.47.118.247 to 00:11:20:48:04:06 via 10.47.118.2');
///////////////////////////////////////////////////////////////////////
function dhcp_lease_add($options="") {

    // The important globals
    global $conf, $self, $mysql;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dhcp_lease_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !(
                                ($options['raw'])
                                or
                                ($options['ip'] and $options['mac'])
                              )
        )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dhcp_lease_add-v{$version}
Adds a dhcp lease entry into the tracking database

  Synopsis: dhcp_lease_add [KEY=VALUE] ...

  Required:
    raw=TEXT               The raw text from the DHCP log

  Notes:
    Adding a lease here does NOT effect the actual functioning
    DHCP server.  This is purely to keep track of lease information
    derived from the DHCP logfile itself.
\n
EOM

        ));
    }

    $name = 'N/A';

    if ($options['raw']) {
        // break the line down into its parts
        $line = preg_split("/( on | to | via )/",$options['raw']);
        $mac = $line[2];
        $iptext = $line[1];
        $ip = ip_mangle($iptext,'numeric');

        // if the second field has mac and a name in parens then break it out
        if (strpos($line[2],'(')) {
            list($mac,$name) = split('[()]', $line[2]);
            $name = trim($name);
        }

        // make sure the mac has been trimmed
        $mac = trim($mac);

        $text = "DHCP Lease Add: IP={$ip} IPTEXT={$line[1]} MAC={$mac} NAME={$name}\n";
        printmsg("DEBUG => {$text}",3);
    }

    if ($options['ip'] and $options['mac']) {
        $ip = ip_mangle($options['ip'],'numeric');
        // make sure the mac has been trimmed
        $mac = trim($options['mac']);
        if ($options['name']) $name = $options['name'];

        $text = "DHCP Lease Add: IP={$ip} IPTEXT={$line[1]} MAC={$mac} NAME={$name}\n";
        printmsg("DEBUG => {$text}",3);

    }

    // Check to see if the IP is already in the database
//    list($status, $rows, $lease) = db_get_record($mysql, "dhcp_leases", array('IP_ADDRESS' => $ip));
//    if ($rows) {
//        printmsg("DEBUG => updating existing record", 2);
//        list($status, $rows)  = db_update_record($mysql, "dhcp_leases", array('IP_ADDRESS' => $ip), array('MAC' => $mac, 'HOSTNAME' => $name));
//    } else {
//        printmsg("DEBUG => inserting new record", 2);
//        list($status, $rows)  = db_insert_record($mysql, "dhcp_leases", array('IP_ADDRESS' => $ip,
//                                                                              'IP_TEXT' => $iptext,
//                                                                              'MAC' => $mac,
//                                                                              'HOSTNAME' => $name)
 //                                               );
//    }


    // Return the success notice
    return(array(0, $text));
}









// DON'T put whitespace at the beginning or end of this file!!!
?>

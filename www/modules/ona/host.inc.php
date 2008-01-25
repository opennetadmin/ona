<?
// DON'T put whitespace at the beginning or end of this file!!!





///////////////////////////////////////////////////////////////////////
//  Function: host_add (string $options='')
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
//  Example: list($status, $result) = host_add('host=test&type=&unit=');
///////////////////////////////////////////////////////////////////////
function host_add($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.03';

    printmsg("DEBUG => host_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['type']) ) {
//FIXME: PK:    if ($options['help'] or !($options['host'] and $options['type'] and $options['unit']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

host_add-v{$version}
Add a new host

  Synopsis: host_add [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN]        hostname for new DNS record
    type=TYPE or ID           device/model type or ID

  Optional:
    notes=NOTES               textual notes

  Optional, add an interface too:
    ip=ADDRESS                ip address (numeric or dotted)
    mac=ADDRESS               mac address (most formats are ok)
    name=NAME                 interface name (i.e. "FastEthernet0/1.100")
    description=TEXT          brief description of the interface

\n
EOM
        ));
    }

    // Validate that there isn't already another interface with the same IP address
    if ($options['ip']) {
        list($status, $rows, $interface) = ona_get_interface_record(array('ip_addr' => $options['ip']));
        if ($rows) {
            printmsg("DEBUG => host_add() IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!",3);
            $self['error'] = "ERROR => host_add() IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!";
            return(array(4, $self['error'] . "\n" .
                            "INFO => Conflicting interface record ID: {$interface['id']}\n"));
        }
    }


// FIXME: commented out by Paul K 3/21/07 because we probably won't use units
//    // Find the Unit ID to use
//    list($status, $rows, $unit) = ona_find_unit($options['unit']);
//    if ($status or $rows != 1 or !$unit['UNIT_ID']) {
//        printmsg("DEBUG => The unit specified, {$options['unit']}, does not exist!", 3);
//        return(array(2, "ERROR => The unit specified, {$options['unit']}, does not exist!\n"));
//    }
//    printmsg("DEBUG => Unit selected: {$unit['UNIT_NAME']} Unit number: {$unit['UNIT_NUMBER']}", 3);


   // Find the Device Type ID (i.e. Type) to use
   list($status, $rows, $device_type) = ona_find_device_type($options['type']);
   if ($status or $rows != 1 or !$device_type['id']) {
       printmsg("DEBUG => The device type specified, {$options['type']}, does not exist!", 3);
       return(array(3, "ERROR => The device type specified, {$options['type']}, does not exist!\n"));
   }
   printmsg("DEBUG => Device type selected: {$device_type['model_description']} Device ID: {$device_type['id']}", 3);


    // Sanitize "security_level" option
    $options['security_level'] = sanitize_security_level($options['security_level']);
    if ($options['security_level'] == -1) {
        printmsg("DEBUG => Sanitize security level failed either ({$options['security_level']}) is invalid or is higher than user's level!", 3);
        return(array(3, $self['error'] . "\n"));
    }


    // Determine the real hostname to be used --
    // i.e. add .something.com, or find the part of the name provided
    // that will be used as the "domain".  This means testing many
    // domain names against the DB to see what's valid.
    //
    list($status, $rows, $host) = ona_find_host($options['host']);

    // FIXME: MP: there is an issue here.. it fails to say an existing DNS entry was found.  It gets invalide host name first.

    // Validate that the DNS name has only valid characters in it
    $host['name'] = sanitize_hostname($host['name']);
    if (!$host['name']) {
        printmsg("DEBUG => Invalid host name ({$host['name']})!", 3);
        $self['error'] = "ERROR => Invalid host name ({$host['name']})!";
        return(array(4, $self['error'] . "\n"));
    }
    // Debugging
    printmsg("DEBUG => Host selected: {$host['name']}.{$host['domain_fqdn']} Domain ID: {$host['domain_id']}", 3);

    // Validate that there isn't already any dns record named $host['name'] in the domain $host_domain_id.
    $h_status = $h_rows = 0;
    // does the domain $host_domain_id even exist?
    list($d_status, $d_rows, $d_record) = ona_get_dns_record(array('name' => $host['name'], 'domain_id' => $host['domain_id']));
    if (!$d_status && $d_rows) {
        list($h_status, $h_rows, $h_record) =  ona_get_host_record(array('primary_dns_id' => $d_record[0]));
    }

    error_log($d_status.",".$d_rows.",".print_r($d_record,true));
    error_log($h_status.",".$h_rows.",".print_r($h_record,true));
    if ($h_status or $h_rows) {
        printmsg("DEBUG => Another DNS record named {$host['name']}.{$host['domain_fqdn']} already exists!",3);
        $self['error'] = "ERROR => Another DNS record named {$host['name']}.{$host['domain_fqdn']} already exists!";
        return(array(5, $self['error'] . "\n"));
    }

    // Check permissions
    if (!auth('host_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new host record
    $id = ona_get_next_id('hosts');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('hosts') call failed!";
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new host record: $id", 3);

//     // Get the next ID for the new dns record
//     $host['primary_dns_id'] = ona_get_next_id('dns');
//     if (!$id) {
//         $self['error'] = "ERROR => The ona_get_next_id('dns') call failed!";
//         printmsg($self['error'], 0);
//         return(array(7, $self['error'] . "\n"));
//     }
//     printmsg("DEBUG => ID for new dns record: $id", 3);

    // Get the next ID for the new device record
    $host['device_id'] = ona_get_next_id('devices');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('device') call failed!";
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new device record: $id", 3);


    // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
    $options['notes'] = str_replace('\\=','=',$options['notes']);
    $options['notes'] = str_replace('\\&','&',$options['notes']);

    // Add the device record
    // FIXME: (MP) quick add of device record. more detail should be looked at here to ensure it is done right
// FIXME: MP this should use the run_module('device_add')!!! when it is ready
    list($status, $rows) = db_insert_record(
        $onadb,
        'devices',
        array(
            'id'                => $host['device_id'],
            'device_type_id'    => $device_type['id'],
            'location_id'       => 1 // FIXME: (MP) hard coding location as it is a required field
            // FIXME: (MP) add in the asset tag and serial number stuff too
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => host_add() SQL Query failed adding device: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Add the host record
    // FIXME: (PK) Needs to insert to multiple tables for e.g. name and domain_id.
    list($status, $rows) = db_insert_record(
        $onadb,
        'hosts',
        array(
            'id'                   => $id,
            'primary_dns_id'       => '',  // Unknown at this point.. needs to be added afterwards
            'device_id'            => $host['device_id'],
            'notes'                => $options['notes']
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => host_add() SQL Query failed adding host: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

// // FIXME: MP this should use the run_module('dns_record_add')!!!
//     // Add the dns record
//     list($status, $rows) = db_insert_record(
//         $onadb,
//         'dns',
//         array(
//             'id'                   => $host['primary_dns_id'],
//             'type'                 => 'A',
//             'ttl'                  => '3600', // FIXME: (PK) pull this from the parent domain? (MP) also allow it to be passed on commandline
//             'name'                 => $host['name'],
//             'domain_id'            => $host['domain_id']
//         )
//     );
//     if ($status or !$rows) {
//         $self['error'] = "ERROR => host_add() SQL Query failed adding dns record: " . $self['error'];
//         printmsg($self['error'], 0);
//         return(array(6, $self['error'] . "\n"));
//     }

    // Else start an output message
    $text = "INFO => Host ADDED: {$host['name']}.{$host['domain_fqdn']}";
    printmsg($text,0);
    $text .= "\n";

    // FIXME: MP, it seems to me that when adding a host you always need an IP so that the A record is added to it.
    // If we are going to add an interface, call that module now:
    if ($options['ip']) {
        // since we have no name yet, we need to use the ID of the new host as the host option for the following module calls
        $options['host'] = $id;

        printmsg("DEBUG => host_add() ({$host['name']}.{$host['domain_fqdn']}) calling interface_add() ({$options['ip']})", 3);
        list($status, $output) = run_module('interface_add', $options);
        if ($status)
            return(array($status, $output));
        $text .= $output;

        // Find the interface_id for the interface we just added
        list($status, $rows, $int) = ona_find_interface($options['ip']);

        // make the dns record type A
        $options['type'] = 'A';
        // FIXME: MP I had to force the name value here.  name is comming in as the interface name.  this is nasty!
        $options['name'] = "{$host['name']}.{$host['domain_fqdn']}";
        // And we will go ahead and auto add the ptr.  the user can remove it later if they dont want it.  FIXME: maybe create a checkbox on the host edit
        $options['addptr'] = '1';

        // Add the DNS entry with the IP address etc
        printmsg("DEBUG => host_add() ({$host['name']}.{$host['domain_fqdn']}) calling dns_record_add() ({$options['ip']})", 3);
        list($status, $output) = run_module('dns_record_add', $options);
        if ($status)
            return(array($status, $output));
        $text .= $output;

        // FIXME: MP this is a temp fix until ona_find_dns_record is written
        list($status, $rows, $dnsrecord) = ona_get_dns_record(array('name' => $host['name'], 'domain_id' => $host['domain_id'], 'interface_id' => $int['id'], 'type' => 'A'));


        // Set the primary_dns_id to the dns record that was just added
        list($status, $rows) = db_update_record($onadb, 'hosts', array('id' => $id), array('primary_dns_id' => $dnsrecord['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => host_add() SQL Query failed to update primary_dns_id for host: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }

        return(array(0, $text));
    }

    // Return the success notice
    $text .= "INFO => Please add an interface to the new host!\n";
    return(array(0, $text));
}











///////////////////////////////////////////////////////////////////////
//  Function: host_modify (string $options='')
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
//  Example: list($status, $result) = host_modify('FIXME: blah blah blah');
///////////////////////////////////////////////////////////////////////
function host_modify($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.04';

    printmsg("DEBUG => host_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['interface'] and !$options['host']) or
       (!$options['set_host'] and
        !$options['set_type'] and
        !$options['set_unit'] and
        !$options['set_security_level'] and
        !$options['set_notes']
       ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

host_modify-v{$version}
Modify a host record

  Synopsis: host_modify [KEY=VALUE] ...

  Where:
    host=NAME[.DOMAIN] or ID  select host by hostname or ID
      or
    interface=[ID|IP|MAC]     select host by IP or MAC

  Update:
    set_host=NAME[.DOMAIN]    change hostname and/or domain
    set_type=TYPE or ID       change device/model type or ID
    set_unit=NAME or ID       change location/unit ID
    set_security_level=LEVEL  change numeric security level ({$conf['ona_lvl']})
    set_notes=NOTES           change the textual notes
\n
EOM
        ));
    }

    //
    // Find the host record we're modifying
    //

    // If they provided a hostname / ID let's look it up
    if ($options['host'])
        list($status, $rows, $host) = ona_find_host($options['host']);

    // If they provided a interface ID, IP address, interface name, or MAC address
    else if ($options['interface']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $interface) = ona_find_interface($options['interface']);
        if ($status or !$rows) {
            printmsg("DEBUG => Interface not found ({$options['interface']})!",3);
            $self['error'] = "ERROR => Interface not found ({$options['interface']})!";
            return(array(4, $self['error'] . "\n"));
        }
        // Load the associated host record
        list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
    }

    // If we didn't get a record then exit
    if (!$host['id']) {
        printmsg("DEBUG => Host not found ({$options['host']})!",3);
        $self['error'] = "ERROR => Host not found ({$options['host']})!";
        return(array(4, $self['error'] . "\n"));
    }

    // Get related Device record info
    list($status, $rows, $device) = ona_get_device_record(array('id' => $host['device_id']));


    //
    // Define the records we're updating
    //

    // This variable will contain the updated info we'll insert into the DB
    $SET = array();
    $SET_DNS = array();

    // Set options['set_host']?
    // Validate that the DNS name has only valid characters in it
    if ($options['set_host']) {
        $options['set_host'] = sanitize_hostname($options['set_host']);
        if (!$options['set_host']) {
            printmsg("DEBUG => Invalid host name ({$options['set_host']})!", 3);
            $self['error'] = "ERROR => Invalid host name ({$options['set_host']})!";
            return(array(5, $self['error'] . "\n"));
        }
        // Get the host & domain part
        list($status, $rows, $tmp_host) = ona_find_host($options['set_host']);
        // If the function above returned a host, and it's not the one we're editing, stop!
        if ($tmp_host['id'] and $tmp_host['id'] != $host['id']) {
            printmsg("DEBUG => Another host named {$tmp_host['fqdn']} already exists!",3);
            $self['error'] = "ERROR => Another host named {$tmp_host['fqdn']} already exists!";
            return(array(5, $self['error'] . "\n"));
        }
        if($host['name'] != $tmp_host['name'])
            $SET_DNS['name']      = $tmp_host['name'];
        if($host['domain_id'] != $tmp_host['domain_id'])
            $SET_DNS['domain_id'] = $tmp_host['domain_id'];
    }


    // Set options['set_type']?
    if ($options['set_type']) {
        // Find the Device Type ID (i.e. Type) to use
        list($status, $rows, $device_type) = ona_find_device_type($options['set_type']);
        if ($status or $rows != 1 or !$device_type['id']) {
            printmsg("DEBUG => The device type specified, {$options['set_type']}, does not exist!",3);
            $self['error'] = "ERROR => The device type specified, {$options['set_type']}, does not exist!";
            return(array(6, $self['error'] . "\n"));
        }
        printmsg("DEBUG => Device type ID: {$device_type['id']}", 3);

        // Everything looks ok, add it to $SET if it changed...
        if ($device['device_type_id'] != $device_type['id'])
            $SET_DEV_TYPE['device_type_id'] = $device_type['id'];
    }


/*PK    // Set options['set_unit']?
    if ($options['set_unit']) {
        // Find the Unit ID to use
        list($status, $rows, $unit) = ona_find_unit($options['set_unit']);
        if ($status or $rows != 1 or !$unit['UNIT_ID']) {
            printmsg("DEBUG => The unit specified, {$options['set_unit']}, does not exist!",3);
            $self['error'] = "ERROR => The unit specified, {$options['set_unit']}, does not exist!";
            return(array(7, $self['error'] . "\n"));
        }
        printmsg("DEBUG => Unit selected: {$unit['UNIT_NAME']} Unit number: {$unit['UNIT_NUMBER']}", 3);
        $SET['UNIT_ID'] = $unit['UNIT_ID'];
    }*/

/* PK
    // Set options['set_security_level']
    if ($options['set_security_level']) {
        // Sanitize "security_level" option
        $options['set_security_level'] = sanitize_security_level($options['set_security_level']);
        if ($options['set_security_level'] == -1) {
            printmsg("DEBUG => Sanitize security level failed either ({$options['set_security_level']}) is invalid or is higher than user's level!", 3);
            return(array(3, $self['error'] . "\n"));
        }
        $SET['LVL'] = $options['set_security_level'];
    }
*/

    // Set options['set_notes'] (it can be a null string!)
    if (array_key_exists('set_notes', $options)) {
        // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
        $options['set_notes'] = str_replace('\\=','=',$options['set_notes']);
        $options['set_notes'] = str_replace('\\&','&',$options['set_notes']);
        // If it changed...
        if ($host['notes'] != $options['set_notes'])
            $SET['notes'] = $options['set_notes'];
    }

    // Check permissions
    if (!auth('host_modify') or !authlvl($host['LVL'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the host record before updating (logging)
    $original_host = $host;

    // Update the host record if necessary
    if(count($SET) > 0) {
        list($status, $rows) = db_update_record($onadb, 'hosts', array('id' => $host['id']), $SET);
        if ($status or !$rows) {
            $self['error'] = "ERROR => host_modify() SQL Query failed for host: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }
    }
    // Update DNS table if necessary
    if(count($SET_DNS) > 0) {
        list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $host['primary_dns_id']), $SET_DNS);
        if ($status or !$rows) {
            $self['error'] = "ERROR => host_modify() SQL Query failed for dns record: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }
    }
    // Update device table if necessary
    if(count($SET_DEV_TYPE) > 0) {
        list($status, $rows) = db_update_record($onadb, 'devices', array('id' => $host['device_id']), $SET_DEV_TYPE);
        if ($status or !$rows) {
            $self['error'] = "ERROR => host_modify() SQL Query failed for device type: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }
    }

    // Get the host record after updating (logging)
    list($status, $rows, $new_host) = ona_get_host_record(array('id' => $host['id']));

    // Return the success notice
    $self['error'] = "INFO => Host UPDATED:{$host['id']}: {$new_host['fqdn']}";

    $log_msg = "INFO => Host UPDATED:{$host['id']}: ";
    $more="";
    foreach(array_keys($host) as $key) {
        if($host[$key] != $new_host[$key]) {
            $log_msg .= "{$more}{$key}: {$host[$key]} => {$new_host[$key]}";
            $more= "; ";
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
//  Function: host_del (string $options='')
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
//  Example: list($status, $result) = host_del('host=test');
///////////////////////////////////////////////////////////////////////
function host_del($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => host_del({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.16';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['host']) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

host_del-v{$version}
Deletes a host, up to one interface, and all aliases from the database

  Synopsis: host_del [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN] or ID      hostname or ID of the host to delete

  Optional:
    commit=[yes|no]               commit db transaction (no)

  Notes:
    * A host won't be deleted if it has config text records
    * A host won't be deleted if it's configured as a dns or dhcp server
\n
EOM
        ));
    }


    // Find the host (and domain) record from $options['host']
    list($status, $rows, $host) = ona_find_host($options['host']);
    printmsg("DEBUG => host_del() Host: {$host['fqdn']} ({$host['id']})", 3);
    if (!$host['id']) {
        printmsg("DEBUG => Unknown host: {$host['fqdn']}",3);
        $self['error'] = "ERROR => Unknown host: {$host['fqdn']}";
        return(array(2, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('host_del') or !authlvl($host['LVL'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // If "commit" is yes, delete the host
    if ($options['commit'] == 'Y') {
        $text = "";
        $add_to_error = "";
        $add_to_status = 0;

        // SUMMARY:
        //   Don't allow a delete if it is performing server duties
        //   Don't allow a delete if config text entries exist
        //   Delete Interfaces
        //   Delete interface cluster entries
        //   Delete dns records
        //   Delete Infobits
        //   Delete DHCP entries
        //   Delete device record if it is the last host associated with it.
        //
        // IDEA: If it's the last host in a domain (maybe do the same for or a networks & vlans in the interface delete)
        //       It could just print a notice or something.

        // Check that it is the host is not performing server duties
        // FIXME: MP mostly fixed..needs testing
        $serverrow = 0;
        // check ALL the places server_id is used and remove the entry from server_b if it is not used
        list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_server_subnets', array('host_id' => $host['id']));
        if ($rows) $serverrow++;
        list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_failover_groups', array('primary_server_id' => $host['id']));
        if ($rows) $serverrow++;
        list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_failover_groups', array('secondary_server_id' => $host['id']));
        if ($rows) $serverrow++;
        if ($serverrow > 0) {
            printmsg("DEBUG => Host ({$host['fqdn']}) cannot be deleted, it is performing duties as a DHCP server!",3);
            $self['error'] = "ERROR => Host ({$host['fqdn']}) cannot be deleted, it is performing duties as a DHCP server!";
            return(array(5, $self['error'] . "\n"));
        }


        // Check if host is a dns server
        $serverrow = 0;
        list($status, $rows, $srecord) = db_get_record($onadb, 'dns_server_domains', array('host_id' => $host['id']));
        if ($rows) $serverrow++;

        if ($serverrow > 0) {
            printmsg("DEBUG => Host ({$host['fqdn']}) cannot be deleted, it is performing duties as a DNS server!",3);
            $self['error'] = "ERROR => Host ({$host['fqdn']}) cannot be deleted, it is performing duties as a DNS server!";
            return(array(5, $self['error'] . "\n"));
        }

        // Display an error if it has any entries in configurations
        list($status, $rows, $server) = db_get_record($onadb, 'configurations', array('host_id' => $host['id']));
        if ($rows) {
            printmsg("DEBUG => Host ({$host['fqdn']}) cannot be deleted, it has config archives!",3);
            $self['error'] = "ERROR => Host ({$host['fqdn']}) cannot be deleted, it has config archives!";
            return(array(5, $self['error'] . "\n"));
        }


        // Delete interface(s)
        // get list for logging
        $clustcount = 0;
        $dnscount = 0;
        list($status, $rows, $interfaces) = db_get_records($onadb, 'interfaces', array('host_id' => $host['id']));

        // Delete each DNS record associated with this hosts interfaces.
        foreach ($interfaces as $int) {
            // MP: FIXME: I think this is an issue as more than one DNS record could exist for an interface.  need to loop here.
            //while $rows {
            list($status, $rows, $record) = db_get_record($onadb, 'dns', array('interface_id' => $int['id']));
            // Run the module
            if ($rows) list($status, $output) = run_module('dns_record_del', array('name' => $record['id'], 'type' => $record['type'], 'commit' => 'Y', 'delete_by_module' => 'Y'));
            $add_to_error .= $output;
            $add_to_status = $add_to_status + $status;
        }


        // FIXME: MP: Cant delete if one of the interfaces is primary for a cluster
        foreach ($interfaces as $int) {
            list($status, $rows, $records) = db_get_records($onadb, 'interface_clusters', array('interface_id' => $int['id']));
            $clustcount = $clustcount + $rows;
        }

        if ($clustcount) {
            $self['error'] = "ERROR => host_del() An interface on this host is primary for some interface shares, delete the share or move the interface first.";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }



        // Delete messages
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'messages', array('table_name_ref' => 'hosts','table_id_ref' => $host['id']));
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'messages', array('table_name_ref' => 'hosts','table_id_ref' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() message delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }
        // log deletions
        printmsg("INFO => {$rows} Message(s) DELETED from {$host['fqdn']}",0);
        $add_to_error .= "INFO => {$rows} Message(s) DELETED from {$host['fqdn']}\n";



        // FIXME: MP this needs some work.. deleting DNS records is much more complicated than this.!
        // Delete DNS record
//         list($status, $rows, $records) = db_get_records($onadb, 'dns', array('id' => $host['primary_dns_id']));
//         // do the delete
//         list($status, $rows) = db_delete_records($onadb, 'dns', array('id' => $host['primary_dns_id']));
//         if ($status) {
//             $self['error'] = "ERROR => host_del() DNS record delete SQL Query failed: {$self['error']}";
//             printmsg($self['error'],0);
//             return(array(5, $add_to_error . $self['error'] . "\n"));
//         }
//         // log deletions
//         foreach ($records as $record) {
//             printmsg("INFO => DNS record DELETED: {$record['id']} from {$host['fqdn']}",0);
//             $add_to_error .= "INFO => DNS record DELETED: {$record['id']} from {$host['fqdn']}\n";
//         }

        foreach ($interfaces as $record) {
            // Run the module
            list($status, $output) = run_module('interface_del', array('interface' => $record['id'], 'commit' => 'on', 'delete_by_module' => 'Y'));
            $add_to_error .= $output;
            $add_to_status = $add_to_status + $status;
        }


        // do the interface_cluster delete
        list($status, $rows) = db_delete_records($onadb, 'interface_clusters', array('host_id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() interface_cluster delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }
        // log deletions
        printmsg("INFO => {$rows} Shared interface(s) DELETED from {$host['fqdn']}",0);
        $add_to_error .= "INFO => {$rows} Shared interface(s) DELETED from {$host['fqdn']}\n";

        // Delete device record
        // Count how many hosts use this same device
        list($status, $rows, $records) = db_get_records($onadb, 'hosts', array('device_id' => $host['device_id']));
        // if device count is just 1 do the delete
        if ($rows > 1) {
            list($status, $rows) = db_delete_records($onadb, 'devices', array('id' => $host['device_id']));
            if ($status) {
                $self['error'] = "ERROR => host_del() device delete SQL Query failed: {$self['error']}";
                printmsg($self['error'],0);
                return(array(5, $add_to_error . $self['error'] . "\n"));
            }
            // log deletions
            foreach ($records as $record) {
                printmsg("INFO => Device record DELETED: {$record['id']} no remaining hosts",0);
                $add_to_error .= "INFO => Device record DELETED: {$record['id']} no remaining hosts\n";
            }
        }



        // Delete infobit entries
        // get list for logging
/*PK        list($status, $rows, $records) = db_get_records($onadb, 'HOST_INFOBITS_B', array('host_id' => $host['id']));
        $log=array(); $i=0;
        foreach ($records as $record) {
            list($status, $rows, $infobit) = ona_get_host_infobit_record(array('ID' => $record['ID']));
            $log[$i]= "INFO => Infobit DELETED: {$infobit['NAME']} ({$infobit['VALUE']}) from {$host['fqdn']}";
            $i++;
        }
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'HOST_INFOBITS_B', array('host_id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() infobit delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        // log deletions
        foreach($log as $log_msg) {
            printmsg($log_msg,0);
            $add_to_error .= $log_msg . "\n";
        }
*/
        // Delete DHCP options
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'dhcp_option_entries', array('host_id' => $host['id']));
        $log=array(); $i=0;
        foreach ($records as $record) {
            list($status, $rows, $dhcp) = ona_get_dhcp_option_entry_record(array('id' => $record['id']));
            $log[$i]= "INFO => DHCP entry DELETED: {$dhcp['display_name']}={$dhcp['value']} from {$host['fqdn']}";
            $i++;
        }
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'dhcp_option_entries', array('host_id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() DHCP option entry delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        // log deletions
        foreach($log as $log_msg) {
            printmsg($log_msg,0);
            $add_to_error .= $log_msg . "\n";
        }

        // Delete the host
        list($status, $rows) = db_delete_records($onadb, 'hosts', array('id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() host delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }

        // Return the success notice
        if ($add_to_status == 0) $self['error'] = "INFO => Host DELETED: {$host['fqdn']}";
        printmsg($self['error'], 0);
        return(array($add_to_status, $add_to_error . $self['error'] . "\n"));
    }


    //
    // We are just displaying records that would have been deleted
    //

    // SUMMARY:
    //   Display a warning if it has an entry in SERVER_B
    //   Display a warning if it has config text entries
    //   Display Interfaces
    //   Display Aliases
    //   Display Infobits
    //   Display DHCP entries

    // Otherwise just display the host record for the host we would have deleted
    $text = "Record(s) NOT DELETED (see \"commit\" option)\n" .
            "Displaying record(s) that would have been deleted:\n";

    // Display a warning if host is performing server duties
    list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_server_subnets', array('host_id' => $host['id']));
    if ($rows) {
        $text .= "\nWARNING!  This host is a DHCP server for {$rows} subnet(s)\n";
    }
    list($status, $rows, $srecord) = db_get_record($onadb, 'dns_server_domains', array('host_id' => $host['id']));
    if ($rows) {
        $text .= "\nWARNING!  This host is a DNS server for one or more domains!\n";
    }
    list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_failover_groups', array('primary_server_id' => $host['id']));
    if ($rows) {
        $text .= "\nWARNING!  This host is a server that is primary in a DHCP failover group\n";
    }
    list($status, $rows, $srecord) = db_get_record($onadb, 'dhcp_failover_groups', array('secondary_server_id' => $host['id']));
    if ($rows) {
        $text .= "\nWARNING!  This host is a server that is secondary in a DHCP failover group\n";
    }

    // Display a warning if it has any configurations
    list($status, $rows, $server) = db_get_record($onadb, 'configurations', array('host_id' => $host['id']));
    if ($rows)
        $text .= "\nWARNING!  Host can not be deleted, it has config archives!\n";

    if ($rows)
        $text .= "\nWARNING!  Host will NOT be deleted, due to previous warnings!\n";

    // Display the Host's complete record
    list($status, $tmp) = host_display("host={$host['id']}&verbose=N");
    $text .= "\n" . $tmp;

    // Display count of messages
    list($status, $rows, $records) = db_get_records($onadb, 'messages', array('table_name_ref' => 'hosts','table_id_ref' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED MESSAGE RECORDS ({$rows}):\n";

    // Display associated interface(s)
    list($status, $int_rows, $interfaces) = db_get_records($onadb, 'interfaces', array('host_id' => $host['id']));
    // show the dns records associated
    foreach ($interfaces as $record) {
        list($status, $rows, $dnsrec) = db_get_records($onadb, 'dns', array('interface_id' => $record['id']));
        if ($rows) {
            $text .= "\nASSOCIATED DNS RECORDS ({$rows}) ON INTERFACE (" . ip_mangle($record['ip_addr'], 'dotted') . "):\n";
            foreach ($dnsrec as $rec) {
                $text .= "  TYPE: {$rec['type']}, {$rec['name']} -> " . ip_mangle($record['ip_addr'], 'dotted') . "\n";
            }
        }
    }

    if ($int_rows) $text .= "\nASSOCIATED INTERFACE RECORDS ({$int_rows}):\n";
    foreach ($interfaces as $record) {
        $text .= "  " . ip_mangle($record['ip_addr'], 'dotted') . "\n";
    }

    // Display associated interface_clusters(s)
    list($status, $clust_rows, $interfaceclusters) = db_get_records($onadb, 'interface_clusters', array('host_id' => $host['id']));

    if ($clust_rows) $text .= "\nASSOCIATED SHARED INTERFACE RECORDS ({$clust_rows}):\n";
    foreach ($interfaceclusters as $record) {
        list($status, $rows, $int) = ona_get_interface_record(array('id' => $record['interface_id']));
        $text .= "  {$int['ip_addr_text']}\n";
    }


    // Display associated DHCP entries
    list($status, $rows, $records) = db_get_records($onadb, 'dhcp_option_entries', array('host_id' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED DHCP OPTION RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $dhcp) = ona_get_dhcp_option_entry_record(array('id' => $record['id']));
        $text .= "  {$dhcp['display_name']} => {$dhcp['value']}\n";
    }



    return(array(7, $text));
}











///////////////////////////////////////////////////////////////////////
//  Function: host_display (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    host=HOSTNAME[.DOMAIN] or ID
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = host_display('host=test');
///////////////////////////////////////////////////////////////////////
function host_display($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.02';

    printmsg("DEBUG => host_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[verbose] (default is yes)
    $options['verbose'] = sanitize_YN($options['verbose'], 'Y');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['host'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

host_display-v{$version}
Displays a host record from the database

  Synopsis: host_display [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN] or ID      hostname or ID of the host display

  Optional:
    verbose=[yes|no]              display additional info (yes)

\n
EOM

        ));
    }


    // Find the host (and domain) record from $options['host']
    list($status, $rows, $host) = ona_find_host($options['host']);
    printmsg("DEBUG => Host: {$host['fqdn']}", 3);
    if (!$host['id']) {
        printmsg("DEBUG => Unknown host: {$options['host']}",3);
        $self['error'] = "ERROR => Unknown host: {$options['host']}";
        return(array(2, $self['error'] . "\n"));
    }

    // Build text to return
    $text  = "HOST RECORD ({$host['fqdn']})\n";
    $text .= format_array($host);

    // If 'verbose' is enabled, grab some additional info to display
    if ($options['verbose'] == 'Y') {

        // Interface record(s)
        $i = 0;
        do {
            list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
            if ($rows == 0) { break; }
            $i++;
            $text .= "\nASSOCIATED INTERFACE RECORD ({$i} of {$rows})\n";
            $text .= format_array($interface);
        } while ($i < $rows);

        // Device record
        list($status, $rows, $device) = ona_get_device_record(array('id' => $host['device_id']));
        if ($rows >= 1) {
            $text .= "\nASSOCIATED DEVICE RECORD\n";
            $text .= format_array($device);
        }

// FIXME: MP like aliases below, show list of dns records associated

        // Unit record
/*        list($status, $rows, $unit) = ona_get_unit_record(array('UNIT_ID' => $host['UNIT_ID']));
        if ($rows >= 1) {
            $text .= "\nASSOCIATED UNIT RECORD\n";
            $text .= format_array($unit);
        }

        // Alias record(s)
        $i = 0;
        do {
            list($status, $rows, $alias) = ona_get_alias_record(array('host_id' => $host['id']));
            if ($rows == 0) { break; }
            $i++;
            $text .= "\nASSOCIATED ALIAS RECORD ({$i} of {$rows})\n";
            $text .= format_array($alias);
        } while ($i < $rows);*/
    }

    // Return the success notice
    return(array(0, $text));

}












// DON'T put whitespace at the beginning or end of this file!!!
?>

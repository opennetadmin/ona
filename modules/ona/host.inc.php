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
  
  Notes:
    * DOMAIN will default to .albertsons.com if not specified
    * An interface (IP address) must be added to a new host separately!
\n
EOM
        ));
    }
    
    
// FIXME: commented out by Paul K 3/21/07 because we probably won't use units
//    // Find the Unit ID to use
//    list($status, $rows, $unit) = ona_find_unit($options['unit']);
//    if ($status or $rows != 1 or !$unit['UNIT_ID']) {
//        printmsg("DEBUG => The unit specified, {$options['unit']}, does not exist!", 3);
//        return(array(2, "ERROR => The unit specified, {$options['unit']}, does not exist!\n"));
//    }
//    printmsg("DEBUG => Unit selected: {$unit['UNIT_NAME']} Unit number: {$unit['UNIT_NUMBER']}", 3);
    
    
   // Find the Device ID (i.e. Type) to use
   list($status, $rows, $device) = ona_find_device($options['type']);
   if ($status or $rows != 1 or !$device['id']) {
       printmsg("DEBUG => The device type specified, {$options['type']}, does not exist!", 3);
       return(array(3, "ERROR => The device type specified, {$options['type']}, does not exist!\n"));
   }
   printmsg("DEBUG => Device selected: {$device['MODEL_DESCRIPTION']} Device ID: {$device['id']}", 3);
    
    
    // Sanitize "security_level" option
    $options['security_level'] = sanitize_security_level($options['security_level']);
    if ($options['security_level'] == -1) {
        printmsg("DEBUG => Sanitize security level failed either ({$options['security_level']}) is invalid or is higher than user's level!", 3);
        return(array(3, $self['error'] . "\n"));
    }
    
    
    // Determine the real hostname to be used --
    // i.e. add .albertsons.com, or find the part of the name provided
    // that will be used as the "zone" or "domain".  This means testing many
    // zone name's against the DB to see what's valid.
    // 
    list($status, $rows, $host) = ona_find_host($options['host']);
   
    
    // Validate that the DNS name has only valid characters in it
    $host['name'] = sanitize_hostname($host['name']);
    if (!$host['name']) {
        printmsg("DEBUG => Invalid host name ({$host['name']})!", 3);
        $self['error'] = "ERROR => Invalid host name ({$host['name']})!";
        return(array(4, $self['error'] . "\n"));
    }
    // Debugging
    printmsg("DEBUG => Host selected: {$host['name']}.{$host['domain_fqdn']} Zone ID: {$host['domain_id']}", 3);
    
    // Validate that there isn't already any dns record named $host['name'] in the zone $host_zone_id.
    $h_status = $h_rows = 0;
    // does the zone $host_zone_id even exist?
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

    // Get the next ID for the new dns record
    $host['primary_dns_id'] = ona_get_next_id('dns');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('dns') call failed!";
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new dns record: $id", 3);
    
    
    // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
    $options['notes'] = str_replace('\\=','=',$options['notes']);
    $options['notes'] = str_replace('\\&','&',$options['notes']);
    
    // Add the host record
    // FIXME: (PK) Needs to insert to multiple tables for e.g. name and domain_id.
    list($status, $rows) = db_insert_record(
        $onadb, 
        'hosts',
        array(
            'id'                   => $id,
            'primary_dns_id'       => $host['primary_dns_id'],
            'device_id'            => $device['id'],
//            'LVL'                  => $options['security_level'],
            'notes'                => $options['notes']
//            'location_id'          => $unit['UNIT_ID']
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => host_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }
    
    // Add the dns record
    list($status, $rows) = db_insert_record(
        $onadb,
        'dns',
        array(
            'id'                   => $host['primary_dns_id'],
            'type'                 => 'A',
            'ttl'                  => '3600', // FIXME: (PK) pull this from the parent zone?
            'name'                 => $host['name'],
            'domain_id'            => $host['domain_id']
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => host_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }
        
    // Else start an output message
    $text = "INFO => Host ADDED: {$host['name']}.{$host['domain_fqdn']}";
    printmsg($text,0);
    $text .= "\n";
    
    // If we are going to add an interface, call that module now:
    if ($options['ip']) {
        printmsg("DEBUG => host_add() ({$host['name']}.{$host['domain_fqdn']}) calling interface_add() ({$options['ip']})", 3);   
        list($status, $output) = run_module('interface_add', $options);
        if ($status)
            return(array($status, $output));
        $text .= $output;
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
Modify an interface record
  
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
        // Find the Device ID (i.e. Type) to use
        list($status, $rows, $device) = ona_find_device($options['set_type']);
        if ($status or $rows != 1 or !$device['id']) {
            printmsg("DEBUG => The device type specified, {$options['set_type']}, does not exist!",3);
            $self['error'] = "ERROR => The device type specified, {$options['set_type']}, does not exist!";
            return(array(6, $self['error'] . "\n"));
        }
        printmsg("DEBUG => Device selected: {$device['MODEL_DESCRIPTION']} Device ID: {$device['id']}", 3);
        
        // Everything looks ok, add it to $SET if it changed...
        if ($host['device_id'] != $device['id'])
            $SET['device_id'] = $device['id'];
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
            $self['error'] = "ERROR => host_modify() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }
    }
    // Update DNS table if necessary
    if(count($SET_DNS) > 0) {
        list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $host['primary_dns_id']), $SET_DNS);
        if ($status or !$rows) {
            $self['error'] = "ERROR => host_modify() SQL Query failed: " . $self['error'];
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
    * DOMAIN will default to .albertsons.com if not specified
    * A host won't be deleted if it has config text records
    * A host won't be deleted if it's configured as a dns or dhcp server
\n
EOM
        ));
    }
    
    
    // Find the host (and zone) record from $options['host']
    list($status, $rows, $host) = ona_find_host($options['host']);
    printmsg("DEBUG => host_del() Host: {$host['fqdn']}", 3);
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
    
    // If "commit" is yes, delte the host
    if ($options['commit'] == 'Y') {
        $text = "";
        $add_to_error = "";
        
        // SUMMARY:
        //   Don't allow a delete if it has an entry in SERVER_B, UNLESS its id is not used elsewhere
        //   Don't allow a delete if config text entries exist
        //   Delete Interfaces
        //   Delete Aliases
        //   Delete Infobits
        //   Delete DHCP entries
        //  
        // IDEA: If it's the last host in a zone (maybe do the same for or a networks & vlans in the interface delete)
        //       It could just print a notice or something.
        
        // Check that it is the last entry using the ID from SERVER_B
        // (pk) FIXME: FIXME FIXME
        list($status, $rows, $server) = db_get_record($onadb, 'SERVER_B', array('host_id' => $host['id']));
        if ($rows) {
            $serverrow = 0;
            // check ALL the places server_id is used and remove the entry from server_b if it is not used
            list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_SERVER_NETWORKS_B', array('SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_POOL_B', array('SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_ENTRY_B', array('SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            list($status, $rows, $srecord) = db_get_record($onadb, 'ZONE_SERVERS_B', array('SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_FAILOVER_GROUP_B', array('PRIMARY_SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_FAILOVER_GROUP_B', array('SECONDARY_SERVER_ID' => $server['ID']));
            if ($rows) $serverrow++;
            if ($serverrow == 0) {
                list($status, $rows, $records) = db_delete_record($onadb, 'SERVER_B', array('ID' => $server['ID']));
                if ($status) {
                    $self['error'] = "ERROR => host_del() server delete SQL Query failed: {$self['error']}";
                    printmsg($self['error'],0);
                    return(array(5, $self['error'] . "\n"));
                }
            }
            if ($serverrow > 0) {
                printmsg("DEBUG => Host ({$host['fqdn']}) cannot be deleted, it is configured as a server!",3);
                $self['error'] = "ERROR => Host ({$host['fqdn']}) cannot be deleted, it is configured as a server!";
                return(array(5, $self['error'] . "\n"));
            }
        }
        
        // Display an error if it has any entries in CONFIG_TEXT_B
        list($status, $rows, $server) = db_get_record($onadb, 'CONFIG_TEXT_B', array('host_id' => $host['id']));
        if ($rows) {
            printmsg("DEBUG => Host ({$host['fqdn']}) cannot be deleted, it has config archives!",3);
            $self['error'] = "ERROR => Host ({$host['fqdn']}) cannot be deleted, it has config archives!";
            return(array(5, $self['error'] . "\n"));
        }
        
        // Delete interface(s)
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'interfaces', array('host_id' => $host['id']));
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'interfaces', array('host_id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() interface delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0); 
            return(array(5, $self['error'] . "\n"));
        }
        // log deletions
        foreach ($records as $record) {
            printmsg("INFO => Interface DELETED: " . ip_mangle($record['ip_addr'], 'dotted') . " from {$host['fqdn']}",0);
            $add_to_error .= "INFO => Interface DELETED: " . ip_mangle($record['ip_addr'], 'dotted') . " from {$host['fqdn']}\n";
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
        // Delete DHCP parameters
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'DHCP_ENTRY_B', array('host_id' => $host['id']));
        $log=array(); $i=0;    
        foreach ($records as $record) {
            list($status, $rows, $dhcp) = ona_get_dhcp_entry_record(array('ID' => $record['ID']));
            $log[$i]= "INFO => DHCP entry DELETED: {$dhcp['DHCP_DESCRIPTION']}={$dhcp['DHCP_PARAMETER_VALUE']} from {$host['fqdn']}";
            $i++;
        }
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'DHCP_ENTRY_B', array('host_id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() DHCP parameter delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0); 
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        // log deletions
        foreach($log as $log_msg) {
            printmsg($log_msg,0);        
            $add_to_error .= $log_msg . "\n";
        }        
        
        // UPDATE circuit info from cpe_b and set host_id to null
        // This is a temp solution till we have a circuit interface
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'CIRCUIT.CPE_B', array('host_id' => $host['id']));
        // do the delete
        list($status, $rows) = db_update_records($onadb, 'CIRCUIT.CPE_B', array('host_id' => $host['id']), array('host_id' => ''));
        if ($status) {
            $self['error'] = "ERROR => host_del() circuit update SQL Query failed: {$self['error']}";
            printmsg($self['error'],0); 
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        // log deletions
        foreach ($records as $record) {
            printmsg("INFO => CPE name DELETED: {$record['CPE_NAME']} from {$host['fqdn']}",0);
            $add_to_error .= "INFO => CPE name DELETED: {$record['CPE_NAME']} from {$host['fqdn']}\n";
        }
*/
        
        // Delete the host
        list($status, $rows) = db_delete_records($onadb, 'hosts', array('id' => $host['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() host delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0); 
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        
        // Return the success notice
        $self['error'] = "INFO => Host DELETED: {$host['fqdn']}";
        printmsg($self['error'], 0);
        return(array(0, $add_to_error . $self['error'] . "\n"));
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
    
    // Display a warning if it has an entry in SERVER_B
    list($status, $rows, $server) = db_get_record($onadb, 'SERVER_B', array('host_id' => $host['id']));
    if ($rows) {
        $serverrow = 0;
        // check ALL the places server_id is used and remove the entry from server_b if it is not used
        list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_SERVER_NETWORKS_B', array('SERVER_ID' => $server['id']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server for the network: {$srecord['DESCRIPTION']}\n";
            $serverrow++;
        }
        list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_POOL_B', array('SERVER_ID' => $server['ID']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server for a DHCP pool!\n";
            $serverrow++;
        }
        list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_ENTRY_B', array('SERVER_ID' => $server['ID']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server which has a server level DHCP entry!\n";
            $serverrow++;
        }
        list($status, $rows, $srecord) = db_get_record($onadb, 'ZONE_SERVERS_B', array('SERVER_ID' => $server['ID']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server for one or more zones!\n";
            $serverrow++;
        }
        list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_FAILOVER_GROUP_B', array('PRIMARY_SERVER_ID' => $server['ID']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server that is primary in a DHCP failover group\n";
            $serverrow++;
        }
        list($status, $rows, $srecord) = db_get_record($onadb, 'DHCP_FAILOVER_GROUP_B', array('SECONDARY_SERVER_ID' => $server['ID']));
        if ($rows) {
            $text .= "\nWARNING!  This host is a server that is secondary in a DHCP failover group\n";
            $serverrow++;
        }
    }
    
    // Display a warning if it has any entries in CONFIG_TEXT_B
    list($status, $rows, $server) = db_get_record($onadb, 'CONFIG_TEXT_B', array('host_id' => $host['id']));
    if ($rows)
        $text .= "\nWARNING!  Host can not be deleted, it has config archives!\n";
    
    // Display the Host's complete record
    list($status, $tmp) = host_display("host={$host['ID']}&verbose=N");
    $text .= "\n" . $tmp;
    
    // Display associated interface(s)
    list($status, $rows, $records) = db_get_records($onadb, 'interfaces', array('host_id' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED INTERFACE RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        $text .= "  " . ip_mangle($record['ip_addr'], 'dotted') . "\n";
    }
    
    // Display associated infobits
    list($status, $rows, $records) = db_get_records($onadb, 'HOST_INFOBITS_B', array('host_id' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED HOST INFOBIT RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $infobit) = ona_get_host_infobit_record(array('ID' => $record['ID']));
        $text .= "  {$infobit['NAME']} ({$infobit['VALUE']})\n";
    }
    
    // Display associated DHCP entries
    list($status, $rows, $records) = db_get_records($onadb, 'DHCP_ENTRY_B', array('host_id' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED DHCP ENTRY RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $dhcp) = ona_get_dhcp_entry_record(array('ID' => $record['ID']));
        $text .= "  {$dhcp['DHCP_DESCRIPTION']} => {$dhcp['DHCP_PARAMETER_VALUE']}\n";
    }
    
    // Display associated Circuit records
    list($status, $rows, $records) = db_get_records($onadb, 'CIRCUIT.CPE_B', array('host_id' => $host['id']));
    if ($rows) $text .= "\nASSOCIATED CIRCUIT RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        $text .= "  {$record['CPE_NAME']}\n";
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
  
  Notes:
    * DOMAIN will default to .albertsons.com if not specified
\n
EOM

        ));
    }
    
    
    // Find the host (and zone) record from $options['host']
    list($status, $rows, $host) = ona_find_host($options['host']);
    printmsg("DEBUG => Host: {$host['fqdn']}", 3);
    if (!$host['ID']) {
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
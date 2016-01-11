<?php

///////////////////////////////////////////////////////////////////////
//  Function: interface_add (string $options='')
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
//  Example: list($status, $result) = interface_add('');
///////////////////////////////////////////////////////////////////////
function interface_add($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_add({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.11';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_add-v{$version}
Adds a new interface to an existing host record

  Synopsis: interface_add [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN] or ID  hostname or ID new interface is associated with
    ip=ADDRESS                ip address (numeric or dotted)

  Optional:
    mac=ADDRESS               mac address (most formats are ok)
    name=NAME                 interface name (i.e. "FastEthernet0/1.100")
    description=TEXT          brief description of the interface
    natip=ADDRESS             IP of NAT address to add with this new interface
    addptr                    Auto add a PTR record for new IP

  Notes:
    * DOMAIN will default to {$conf['dns_defaultdomain']} if not specified
\n
EOM
        ));
    }

    // clean up what is passed in
    $options['ip'] = trim($options['ip']);

    // Set options[force] to N if it's not set
    $options['force'] = sanitize_YN($options['force'], 'N');

    // Set options[addptr] and options[create_a] to Y if they're not set
    $options['addptr'] = sanitize_YN($options['addptr'], 'Y');

    // Warn about 'name' and 'description' fields exceeding max lengths
    if ($options['force'] == 'N') {
        if(strlen($options['name']) > 255) {
            $self['error'] = "ERROR => 'name' exceeds maximum length of 255 characters.";
            return(array(2, $self['error'] . "\n" .
                "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n"));
        }

        if(strlen($options['description']) > 255) {
            $self['error'] = "ERROR => 'description' exceeds maximum length of 255 characters.";
            return(array(2, $self['error'] . "\n" .
                "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n"));
        }
    }

    // Find the Host they are looking for
    list($status, $rows, $host) = ona_find_host($options['host']);
    if (!$host['id']) {
        printmsg("DEBUG => The host specified, {$options['host']}, does not exist!",3);
        $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Host selected: {$options['host']}", 3);

    // Translate IP address to a number
    $orig_ip= $options['ip'];
    $options['ip'] = ip_mangle($options['ip'], 1);
    if ($options['ip'] == -1) {
        printmsg("DEBUG => Invalid IP address ({$orig_ip})",3);
        $self['error'] = "ERROR => Invalid IP address ({$orig_ip})!";
        return(array(3, $self['error'] . "\n"));
    }

    // Validate that there isn't already another interface with the same IP address
    list($status, $rows, $interface) = ona_get_interface_record("ip_addr = {$options['ip']}");
    if ($rows) {
        printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!",3);
        $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!";
        return(array(4, $self['error'] . "\n" .
                        "INFO => Conflicting interface record ID: {$interface['id']}\n"));
    }

    // Since the IP seems available, let's double check and make sure it's not in a DHCP address pool
    list($status, $rows, $pool) = ona_get_dhcp_pool_record("ip_addr_start <= '{$options['ip']}' AND ip_addr_end >= '{$options['ip']}'");
    if ($status or $rows) {
        printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!",3);
        $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!";
        return(array(5, $self['error'] . "\nINFO => Conflicting DHCP pool record ID: {$pool['id']}\n"));
    }

    // Find the Subnet ID to use from the IP address
    list($status, $rows, $subnet) = ona_find_subnet($options['ip']);
    if ($status or $rows != 1 or !$subnet['id']) {
        printmsg("DEBUG => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!",3);
        $self['error'] = "ERROR => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!";
        return(array(6, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Subnet selected: {$subnet['description']}", 3);

    // Validate that the IP address supplied isn't the base or broadcast of the subnet, as long as it is not /32 or /31
    if ($subnet['ip_mask'] < 4294967294) {
        if ($options['ip'] == $subnet['ip_addr']) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!{$subnet['ip_addr']}",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!";
            return(array(7, $self['error'] . "\n"));
        }
        if ($options['ip'] == ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']) ) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's broadcast address!",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be the subnet broadcast address!";
            return(array(8, $self['error'] . "\n"));
        }
    }


    // Remove any MAC address formatting
    if ($options['mac']) {
        $options['mac'] = trim($options['mac']);
        $orig_mac = $options['mac'];
        $options['mac'] = mac_mangle($options['mac'], 1);
        if ($options['mac'] == -1) {
            printmsg("DEBUG => The MAC address specified ({$orig_mac}) is invalid!",3);
            $self['error'] = "ERROR => The MAC address specified ({$orig_mac}) is invalid!";
            return(array(10, $self['error'] . "\n"));
        }

        // Unless they have opted to allow duplicate mac addresses ...
        if ($options['force'] == 'N') {
            // Validate that there isn't already another interface with the same MAC address on another host
            // Assume duplicate macs on the same host are ok
            list($status, $rows, $interface) = db_get_record($onadb, 'interfaces', "mac_addr LIKE '{$options['mac']}' AND host_id != {$host['id']}");
            if ($status or $rows) {
                printmsg("DEBUG => MAC conflict: That MAC address ({$options['mac']}) is already in use on another host!",3);
                $self['error'] = "WARNING => MAC conflict: That MAC address ({$options['mac']}) is already in use on another host!";
                return(array(11, $self['error'] . "\n" .
                                "NOTICE => You may ignore this warning and add the interface anyway with the \"force=yes\" option.\n" .
                                "INFO => Conflicting interface record ID: {$interface['id']}\n"));
            }
        }
    } else {
        $options['mac'] = '';
    }

    if (!$options['name']) {
        $options['name'] = '';
    }
    // Check permissions
    if (!auth('host_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(12, $self['error'] . "\n"));
    }

    // Get the next ID for the new interface
    $id = ona_get_next_id('interfaces');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('interfaces') call failed!";
        printmsg($self['error'], 0);
        return(array(13, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new interface: $id", 3);

    // Add the interface
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'interfaces',
            array(
                'id'                       => $id,
                'host_id'                  => $host['id'],
                'subnet_id'                => $subnet['id'],
                'ip_addr'                  => $options['ip'],
                'mac_addr'                 => $options['mac'],
                'name'                     => trim($options['name']),
                'description'              => trim($options['description'])
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => interface_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(14, $self['error'] . "\n"));
    }

    // Run the module to add a PTR record if requested
    if ($options['addptr'] == 'Y') {
        $ptropts['name'] = $host['fqdn'];
        $ptropts['ip'] = $options['ip'];
        $ptropts['view'] = $options['view'];
        $ptropts['type'] = 'PTR';
        printmsg("DEBUG => interface_add() calling dns_record_add() for new PTR record: {$options['ip']}", 3);
        list($status, $output) = run_module('dns_record_add', $ptropts);
        if ($status) { return(array($status, $output)); }
        $self['error'] .= $output;
    }

    // if natip is passed, add the nat interface first
    if ($options['natip']) {
        $natint['ip'] = $id;
        $natint['natip'] = $options['natip'];
        printmsg("DEBUG => interface_add() calling nat_add() for new ip: {$options['natip']}", 3);
        list($status, $output) = run_module('nat_add', $natint);
        if ($status) { return(array($status, $output)); }
        $self['error'] .= $output;
    }

    // Return the success notice
    $self['error'] = "INFO => Interface ADDED: " . ip_mangle($options['ip'], 'dotted');
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));
}









///////////////////////////////////////////////////////////////////////
//  Function: interface_modify (string $options='')
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
//  Example: list($status, $result) = interface_modify('');
///////////////////////////////////////////////////////////////////////
function interface_modify($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_modify({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.11';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Set options[use_primary] to N if they're not set
    $options['use_primary'] = sanitize_YN($options['use_primary'], 'N');
    
    // Set options[force] to N if it's not set
    $options['force'] = sanitize_YN($options['force'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['interface'] and !$options['host']) or
       (!$options['set_ip'] and
        !$options['set_mac'] and
        !$options['set_description'] and
        !$options['set_last_response'] and
        !$options['set_name']
       ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_modify-v{$version}
Modify an interface record

  Synopsis: interface_modify [KEY=VALUE] ...

  Required:
    interface=ID or IP or MAC     interface ID or IP address
     or
    host=NAME[.DOMAIN] or ID      find interface by hostname or host_id

    set_ip=IP                     change IP address (numeric or dotted format)
    set_mac=ADDRESS               change the mac address (most formats ok)
    set_name=NAME                 interface name (i.e. "FastEthernet0/1.100")
    set_description=TEXT          description (i.e. "VPN link to building 3")
    set_last_response=DATE        date ip was last seen

  Optional:
    use_primary[=Y]               use the host's primary interface (only applies
                                  when "host" option is used!). NOTE: dcm.pl
                                  requires a value ("Y").
\n
EOM
        ));
    }


    // They provided a interface ID, IP address, interface name, or MAC address
    if ($options['interface']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $interface) = ona_find_interface($options['interface']);
    }

    // If a hostname was provided, do a search based on that
    else if ($options['host']) {
        // Find a host by the user's input
        list($status, $rows, $host) = ona_find_host($options['host']);
        if (!$host['id']) {
            printmsg("DEBUG => Host not found ({$options['host']})!",3);
            $self['error'] = "ERROR => Host not found ({$options['host']})!";
            return(array(2, $self['error'] . "\n"));
        }
        // If we got one, load an associated interface
        // ... or the primary interface, if the use_primary option is present
        if ($options['use_primary'] == 'Y') {
            list($status, $rows, $interface) = ona_get_interface_record(array('id' => $host['primary_interface_id']));
        }
        else {
            list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
            if ($rows > 1) {
                printmsg("DEBUG => Specified host ({$options['host']}) has more than one interface!",3);
                $self['error'] = "ERROR => Specified host ({$options['host']}) has more than one interface!";
                return(array(3, $self['error'] . "\n"));
            }
        }
    }

    // If we didn't get a record then exit
    if (!$interface or !$interface['id']) {
        printmsg("DEBUG => Interface not found ({$options['interface']})!",3);
        $self['error'] = "ERROR => Interface not found ({$options['interface']})!";
        return(array(4, $self['error'] . "\n"));
    }

    // This array will contain the updated info we'll insert into the DB
    $SET = array();

    // Setting an IP address?
    if ($options['set_ip']) {
        $options['set_ip'] = trim($options['set_ip']);
        $orig_ip = $options['set_ip'];
        $options['set_ip'] = ip_mangle($options['set_ip'], 'numeric');
        if ($options['set_ip'] == -1) {
            printmsg("DEBUG => Invalid IP address ({$orig_ip})",3);
            $self['error'] = "ERROR => Invalid IP address ({$orig_ip})";
            return(array(5, $self['error'] . "\n"));
        }

        // Validate that there isn't already another interface with the same IP address
        list($status, $rows, $record) = ona_get_interface_record("ip_addr = {$options['set_ip']}");
        if ($rows and $record['id'] != $interface['id']) {
            printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!",3);
            $self['error'] = "ERROR => IP conflict: specified IP (" . ip_mangle($orig_ip,'dotted') . ") is already in use!";
            return(array(6, $self['error'] . "\nINFO => Conflicting interface record ID: {$record['ID']}\n"));
        }

        // Since the IP seems available, let's double check and make sure it's not in a DHCP address pool
        list($status, $rows, $pool) = ona_get_dhcp_pool_record("ip_addr_start <= '{$options['set_ip']}' AND ip_addr_end >= '{$options['set_ip']}'");
        if ($status or $rows) {
            printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!",3);
            $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!";
            return(array(5, $self['error'] . "\n" .
                            "INFO => Conflicting DHCP pool record ID: {$pool['id']}\n"));
        }

        // Find the Subnet (network) ID to use from the IP address
        list($status, $rows, $subnet) = ona_find_subnet(ip_mangle($options['set_ip'], 'dotted'));
        if ($status or !$rows) {
            printmsg("DEBUG => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!",3);
            $self['error'] = "ERROR => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!";
            return(array(7, $self['error'] . "\n"));
        }

        // Validate that the IP address supplied isn't the base or broadcast of the subnet
        if ((is_ipv4($options['set_ip']) && ($options['set_ip'] == $subnet['ip_addr'])) || (!is_ipv4($options['set_ip']) && (!gmp_cmp(gmp_init($options['set_ip']),gmp_init($subnet['ip_addr'])))) ) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!";
            return(array(8, $self['error'] . "\n"));
        }
        if (is_ipv4($options['set_ip']) && ($options['set_ip'] == ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']) )
            || (!is_ipv4($options['set_ip']) && (!gmp_cmp(gmp_init($options['set_ip']),gmp_add(gmp_init($subnet['ip_addr']),gmp_sub("340282366920938463463374607431768211455", $subnet['ip_mask'])))))
        ) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's broadcast address!",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be the subnet broadcast address!";
            return(array(9, $self['error'] . "\n"));
        }

        // Allow some overrides.
        if ($options['force'] != 'Y') {
            // Search for any existing interfaces on the same subnet
//            list($status, $rows, $record) = ona_get_interface_record(array('subnet_id' => $subnet['id'],
//                                                                            'host_id'    => $interface['host_id']));

            // Check to be sure we don't exceed maximum lengths
            if(strlen($options['name']) > 255) {
                $self['error'] = "ERROR => 'name' exceeds maximum length of 255 characters.";
                return(array(2, $self['error'] . "\n" .
                    "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n"));
            }

            if(strlen($options['description']) > 255) {
                $self['error'] = "ERROR => 'description' exceeds maximum length of 255 characters.";
                return(array(2, $self['error'] . "\n" .
                    "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n"));
            }
        }

        // Make sure we update the ptr record domain if needed.
        // MP: TODO: would it be better to run the dns_modify module vs doing a direct db_update_record???
        $ipflip = ip_mangle($options['set_ip'],'flip');
        $octets = explode(".",$ipflip);
        if (count($octets) > 4) {
            $arpa = '.ip6.arpa';
            $octcount = 31;
        } else {
            $arpa = '.in-addr.arpa';
            $octcount = 3;
        }
        // Find a pointer zone for this record to associate with.
        list($status, $prows, $ptrdomain) = ona_find_domain($ipflip.$arpa);
        if (isset($ptrdomain['id'])) {
            list($status, $rows, $dnsrec) = ona_get_dns_record(array('type' => 'PTR','interface_id' => $interface['id']));

            // If the new ptrdomain does not match an existing ptr records domain then we need to change it.
            if ($rows>0 and $dnsrec['domain_id'] != $ptrdomain['id']) {
                list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $dnsrec['id']), array('domain_id' => $ptrdomain['id'], 'ebegin' => date('Y-m-j G:i:s')));
                if ($status or !$rows) {
                    $self['error'] = "ERROR => interface_modify() PTR record domain update failed: " . $self['error'];
                    printmsg($self['error'], 0);
                    return(array(14, $self['error'] . "\n"));
                }
            }
        }

        // TRIGGER: Since we are changing the IP of an interface that dns records may point to, we need to loop through them all
        if($interface['ip_addr'] != $options['set_ip']) {
            // Get all the DNS records using this interface ID
            list($status, $rows, $records) = db_get_records($onadb, 'dns', array('interface_id' => $interface['id']));
            // Loop them and set their domains for rebuild
            foreach($records as $record) {
                list($status, $rows) = db_update_record($onadb, 'dns_server_domains', array('domain_id' => $record['domain_id']), array('rebuild_flag' => 1));
                if ($status) {
                    $self['error'] = "ERROR => dns_record_add() Unable to update rebuild flags for domain.: {$self['error']}";
                    printmsg($self['error'],0);
                    return(array(7, $self['error'] . "\n"));
                }
            }
        }

        // Check permissions
//         if (!authlvl($subnet['LVL'])) {
//             $self['error'] = "Permission denied!";
//             printmsg($self['error'], 0);
//             return(array(13, $self['error'] . "\n"));
//         }

        // Everything looks ok, add it to $SET
        if($interface['subnet_id'] != $subnet['id'])
            $SET['subnet_id'] = $subnet['id'];
        if($interface['ip_addr'] != $options['set_ip'])
            $SET['ip_addr'] = $options['set_ip'];
    }


    // Setting an MAC address?
    if (array_key_exists('set_mac', $options)) {
        if ($options['set_mac']) {  // allow null mac addresses (to unset one for example)
            $options['set_mac'] = trim($options['set_mac']);
            $orig_mac = $options['set_mac'];
            $options['set_mac'] = mac_mangle($options['set_mac'], 1);
            if ($options['set_mac'] == -1) {
                printmsg("DEBUG => The MAC address specified ({$orig_mac}) is invalid!",3);
                $self['error'] = "ERROR => The MAC address specified ({$orig_mac}) is invalid!";
                return(array(11, $self['error'] . "\n"));
            }

            // Unless they have opted to allow duplicate mac addresses ...
            if ($options['force'] != 'Y') {
                // Validate that there isn't already another interface with the same MAC address on another host
                // Assume duplicate macs on the same host are ok
                list($status, $rows, $record) = db_get_record($onadb, 'interfaces', "mac_addr LIKE '{$options['set_mac']}' AND host_id != {$interface['host_id']}");
                if (($rows and $record['id'] != $interface['id']) or $rows > 1) {
                    printmsg("DEBUG => MAC conflict: That MAC address ({$options['set_mac']}) is already in use on another host!",3);
                    $self['error'] = "ERROR => MAC conflict: That MAC address ({$options['set_mac']}) is already in use on another host!";
                    return(array(12, $self['error'] . "\n" .
                                    "NOTICE => You may ignore this error and update the interface anyway with the \"force=yes\" option.\n" .
                                    "INFO => Conflicting interface record ID: {$record['id']}\n"));
                }
            }
        }
        if($interface['mac_addr'] != $options['set_mac'])
            $SET['mac_addr'] = $options['set_mac'];
    }

    // Check the date formatting etc
    if (isset($options['set_last_response'])) {
        // format the time that was passed in for the database
        $SET['last_response']=date('Y-m-j G-i-s',strtotime($options['set_last_response']));
    }

    // Set options[set_name]?
    if (array_key_exists('set_name', $options) && $interface['name'] != $options['set_name']) {
        $SET['name'] = trim($options['set_name']);
    }

    // Set options[set_description]?
    if (array_key_exists('set_description', $options) && $interface['description'] != $options['set_description']) {
        $SET['description'] = $options['set_description'];
    }

    // Check permissions
    list($status, $rows, $host) = ona_find_host($interface['host_id']);
    if (!auth('interface_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(13, $self['error'] . "\n"));
    }

    // Get the interface record before updating (logging)
    list($status, $rows, $original_interface) = ona_get_interface_record(array('id' => $interface['id']));

    // Update the interface record
    if(count($SET) > 0) {
        list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['id']), $SET);
        if ($status or !$rows) {
            $self['error'] = "ERROR => interface_modify() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(14, $self['error'] . "\n"));
        }
    }

    // Get the interface record after updating (logging)
    list($status, $rows, $new_interface) = ona_get_interface_record(array('id' => $interface['id']));

    list($status, $rows, $new_int) = ona_find_interface($interface['id']);

    // Return the success notice
    $text = format_array($SET);
    $self['error'] = "INFO => Interface UPDATED:{$interface['id']}: {$new_int['ip_addr_text']}";

    $log_msg = "INFO => Interface UPDATED:{$interface['id']}:{$new_int['ip_addr_text']}: ";
    $more="";
    foreach(array_keys($original_interface) as $key) {
        if($original_interface[$key] != $new_interface[$key]) {
            $log_msg .= $more . $key . "[" .$original_interface[$key] . "=>" . $new_interface[$key] . "]";
            $more= ";";
        }
    }

    // only print to logfile if a change has been made to the record
    if($more != '') printmsg($log_msg, 0);

    return(array(0, $self['error'] . "\n{$text}\n"));

}









///////////////////////////////////////////////////////////////////////
//  Function: interface_del (string $options='')
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
//  Example: list($status, $message) = interface_del('host=lnx100');
///////////////////////////////////////////////////////////////////////
function interface_del($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.05';

    printmsg("DEBUG => interface_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['interface'])) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_del-v{$version}
Deletes an interface (ip address) from the database

  Synopsis: interface_del [KEY=VALUE] ...

  Required:
    interface=[ID|IP|MAC]         delete interface by search string

  Optional:
    commit=[yes|no]               commit db transaction (no)

  Notes:
    * If search returns more than one interface, the first will be deleted
    * DOMAIN will default to {$conf['dns_defaultdomain']} if not specified
\n
EOM
        ));
    }


    // Sanitize "options[commit]" (no is the default)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Check permissions
    if (!auth('interface_del') or !auth('host_del')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(13, $self['error'] . "\n"));
    }

    // They provided a interface ID, IP address, interface name, or MAC address
    if ($options['interface']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $interface) = ona_find_interface($options['interface']);
    }

    // If we didn't get a record then exit
    if (!$interface or !$interface['id']) {
        printmsg("DEBUG => Interface not found ({$options['interface']})!",3);
        $self['error'] = "ERROR => Interface not found ({$options['interface']})!";
        return(array(4, $self['error'] . "\n"));
    }

    // Load associated records
    list($status, $rows, $host) = ona_find_host($interface['host_id']);
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));


    // If "commit" is yes, delete the interface
    if ($options['commit'] == 'Y') {

        $add_to_error = '';
        $add_to_status = 0;

        // Check for shared interfaces
        list($status, $clust_rows, $clust) = db_get_records($onadb, 'interface_clusters', array('interface_id' => $interface['id']), '', 0);
        printmsg("DEBUG => total shared host records => {$clust_rows}", 3);
        if ($clust_rows > 0) {
            printmsg("DEBUG => There are {$clust_rows} hosts sharing this interface, remove the shares first.",3);
            $self['error'] = "ERROR => There are {$clust_rows} hosts sharing this interface, remove the shares first.";
            return(array(10, $self['error'] . "\n"));
        }

        // Check if this is the last interface on a host but skip it if its the delete host function calling us
        if (!isset($options['delete_by_module'])) {
            list($status, $total_interfaces, $ints) = db_get_records($onadb, 'interfaces', array('host_id' => $interface['host_id']), '', 0);
            printmsg("DEBUG => total interfaces => {$total_interfaces}", 3);
            if ($total_interfaces == 1) {
                printmsg("DEBUG => You cannot delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).",3);
                $self['error'] = "ERROR => You can not delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).";
                return(array(13, $self['error'] . "\n"));
            }
        }

        printmsg("DEBUG => Deleting interface: ID {$interface['id']}", 3);

        if ($interface['nat_interface_id'] > 0) {
            list($status, $output) = run_module('interface_del', array('interface' => $interface['nat_interface_id'], 'commit' => 'Y', 'delete_by_module' => 'Y'));
            $add_to_status = $add_to_status + $status;
        }

        // Delete any DNS records are associated with the host.
        list($status, $rows, $records) = db_get_records($onadb, 'dns', array('interface_id' => $interface['id']), 'dns_id desc');
        // Loop through all the records and delete them
        // This deletes the primary records last based on sort of dns_id and expects dns_record_del to delete child records
        // but will pick up any PTR records when deleting interfaces with only PTR records.
        if ($rows) {
            foreach($records as $record) {
                $int_dns_deloptions = array('name' => $record['id'], 'type' => $record['type'], 'commit' => 'Y');
                // If delete_by_module is passed in, add it to the dns_record_del option list
                // This allows host/subnet deletes to delete what they need but does not allow you to delete a
                // interface that is used in a primary dns record
                if (isset($options['delete_by_module'])) $int_dns_deloptions['delete_by_module'] = 'Y';
                list($status, $output) = run_module('dns_record_del', $int_dns_deloptions);
                $add_to_error .= $output;
                $add_to_status = $add_to_status + $status;
            }
        }


        // Drop the record
        list($status, $rows) = db_delete_records($onadb, 'interfaces', array('id' => $interface['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => interface_delete() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(5, $self['error']));
        }
        // Build a success notice to return to the user
        $text = "INFO => Interface DELETED: " . ip_mangle($interface['ip_addr'], 'dotted') . " from {$host['fqdn']}";
        printmsg($text, 0);

        // Check to see if there are any other interfaces for the current host_id
        // If there aren't, we need to tell the user to delete the host!
        // since we've disallowed removal of the last interface, this should never happen!!!!!
        if (!isset($options['delete_by_module'])) {
        list($status, $rows, $record) = ona_get_interface_record(array('host_id' => $interface['host_id']));
            if ($rows == 0) {
                printmsg("WARNING => Host {$host['fqdn']} has NO remaining interfaces!", 0);
                $text .= "\n" . "WARNING => Host {$host['fqdn']} has NO remaining interfaces!\n" .
                                "           Delete this host or add an interface to it now!\n";
            }
        }

        // Return the success notice
        return(array($add_to_status, $add_to_error . $text));
    }

    // Otherwise, just display the interface that we will be deleting
    list($status, $displaytext) = interface_display("interface={$interface['id']}&verbose=N");

    $text = "Record(s) NOT DELETED (see \"commit\" option)\n" .
            "Displaying record(s) that would have been deleted:\n";

    // Display records if this is a shared interface
    list($status, $clust_rows, $clust) = db_get_records($onadb, 'interface_clusters', array('interface_id' => $interface['id']));
    if ($clust_rows) {
        $text .= "\nWARNING!  This interface is shared with {$clust_rows} other host(s).\n";
        $text .= "          Please remove all associated hosts first.\n";
    }

    // Display records if this is the last interface
    list($status, $total_interfaces, $ints) = db_get_records($onadb, 'interfaces', array('host_id' => $interface['host_id']), '', 0);
    if ($total_interfaces == 1) {
        $text .= "\nWARNING!  You cannot delete the last interface on a host,\n";
        $text .= "          you must delete the host itself ({$host['fqdn']})\n";
        $text .= "          Or move the interface to another host and delete {$host['fqdn']}.\n";
    }
    $text .= "\n" . $displaytext;
    if ($clust_rows) $text .= "\nASSOCIATED SHARED INTERFACE RECORDS ({$clust_rows}):\n";
    foreach ($clust as $record) {
        list($status, $rows, $clusthost) = ona_get_host_record(array('id' => $record['host_id']));
        $text .= "  {$clusthost['fqdn']}\n";
    }

    // Display DNS records associated with this interface
    list($status, $total_dns, $dns) = db_get_records($onadb, 'dns', array('interface_id' => $interface['id']), '');
    if ($total_dns) $text .= "\nASSOCIATED DNS RECORDS ({$total_dns}):\n";
    foreach ($dns as $rec) {
        $text .= "  TYPE: [ID:{$rec['id']}] {$rec['type']}, {$rec['name']} -> {$interface['ip_addr_text']}\n";
    }

    if ($interface['nat_interface_id'] > 0) {
        printmsg("DEBUG => interface_del() calling interface_del() for external NAT ip: {$options['nat_interface_id']}", 3);
        $natint['interface'] = $interface['nat_interface_id'];
        $natint['commit'] = $options['commit'];
        list($status, $output) = run_module('interface_del', $natint);
       // if ($status) { return(array($status, $output)); }
        $text .= "\nASSOCIATED NAT INTERFACE DELETE:\n\n";
        $text .= $output . "\n";
    }

    return(array(6, $text));

}











///////////////////////////////////////////////////////////////////////
//  Function: interface_display (string $options='')
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
//  Example: list($status, $result) = interface_display('host=lnx100');
///////////////////////////////////////////////////////////////////////
function interface_display($options="") {
    global $conf, $self;

    $text_array = array();

    // Version - UPDATE on every edit!
    $version = '1.03';

    printmsg("DEBUG => interface_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['host'] and !$options['interface'])) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_display-v{$version}
Displays an interface record from the database

  Synopsis: interface_display [KEY=VALUE] ...

  Required:
    interface=[ID|IP|MAC]         display interface by search string
     or
    host=NAME[.DOMAIN] or ID      display interface by hostname or host_id

  Optional:
    verbose=[yes|no]              display additional info (yes)

  Notes:
    * If search returns more than one interface, an error is displayed
    * DOMAIN will default to {$conf['dns_defaultdomain']} if not specified
\n
EOM
        ));
    }

    // Sanitize "options[verbose]" (yes is the default)
    $options['verbose'] = sanitize_YN($options['verbose'], 'Y');

    // They provided a interface ID, IP address, interface name, or MAC address
    if ($options['interface']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $interface) = ona_find_interface($options['interface']);
    }

    // If a hostname was provided, do a search based on that
    else if ($options['host']) {
        // Find a host by the user's input
        list($status, $rows, $host) = ona_find_host($options['host']);
        if (!$host['id']) {
            printmsg("DEBUG => Host not found ({$options['host']})!",3);
            $self['error'] = "ERROR => Host not found ({$options['host']})";
            return(array(2, $self['error'] . "\n"));
        }
        // If we got one, load an associated interface
        list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
    }

    // If we didn't get a record then exit
    if (!$interface['id']) {
        if ($rows > 1)
            $self['error'] = "ERROR => More than one interface matches";
        else
            $self['error'] = "ERROR => Interface not found ({$options['interface']})";
        return(array(4, $self['error'] . "\n"));
    }

    $text_array = $interface;

    // Build text to return
    $text  = "INTERFACE RECORD\n";
    $text .= format_array($interface);

    // If 'verbose' is enabled, grab some additional info to display
    if ($options['verbose'] == 'Y') {

        // Host record
        list($status, $rows, $host) = ona_get_host_record(array('ID' => $interface['host_id']));
        if ($rows >= 1) {
            $text .= "\nASSOCIATED HOST RECORD\n";
            $text .= format_array($host);
        }

        // Subnet record
        list($status, $rows, $subnet) = ona_get_subnet_record(array('ID' => $interface['subnet_id']));
        if ($rows >= 1) {
            $text .= "\nASSOCIATED SUBNET RECORD\n";
            $text .= format_array($subnet);
        }

        // Device record
        list($status, $rows, $device) = ona_get_device_record(array('id' => $host['device_id']));
        if ($rows >= 1) {
            $text .= "\nASSOCIATED DEVICE RECORD\n";
            $text .= format_array($device);
        }

    }

    // change the output format if other than default
    if ($options['format'] == 'json') {
        $text = $text_array;
    }
    if ($options['format'] == 'yaml') {
        $text = $text_array;
    }

    // Return the success notice
    return(array(0, $text));

}











///////////////////////////////////////////////////////////////////////
//  Function: interface_move (string $options='')
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
//  Example: list($status, $result) = interface_move_subnet('');
///////////////////////////////////////////////////////////////////////
function interface_move($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_move({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.04';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
        (!$options['start']) or
        (!$options['new_start'])
       ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_move-v{$version}
  Moves all interface addresses from one subnet to another.
  The initial range of IPs does not have to be consecutive.
  The new range of IPs will be used sequentially.

  Synopsis: interface_move [KEY=VALUE] ...

  IP block to move: (source)
    start=IP                      first IP to move
    [end=IP]                      last IP to move

  New IP block: (destination)
    new_start=IP                  first new IP address
    [new_end=IP]                  last new IP address

  Optional:
    commit=[yes|no]               commit db transaction (no)
\n
EOM
        ));
    }


    // Set options[force] and options[create_a] to N if it's not set
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Find the "start" subnet record by IP address
    list($status, $rows, $old_subnet) = ona_find_subnet($options['start']);
    if (!$old_subnet or !$old_subnet['id']) {
        printmsg("DEBUG => Source start address ({$options['start']}) isn't valid!", 3);
        $self['error'] = "ERROR => Source (start) address specified isn't valid!";
        return(array(2, $self['error'] . "\n"));
    }

    // If they specified an "END" address, make sure it's valid and on the same subnet
    if ($options['end']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $old_subnet_end) = ona_find_subnet($options['end']);

        // If we didn't get a record then exit
        if (!$old_subnet_end or !$old_subnet_end['id']) {
            printmsg("DEBUG => Source end address ({$options['end']}) isn't valid!", 3);
            $self['error'] = "ERROR => Source (end) address specified isn't valid!";
            return(array(3, $self['error'] . "\n"));
        }
        if ($old_subnet_end['id'] != $old_subnet['id']) {
            printmsg("DEBUG => Both source addresses ({$options['start']} and {$options['end']}) must be on the same subnet!", 3);
            $self['error'] = "ERROR => Both the source addresses (start and end) must be on the same subnet!";
            return(array(4, $self['error'] . "\n"));
        }
    }
    // If they didn't give an end, they're moving one host..
    // but to make our lives easier we set the "end" = "start";
    else {
        printmsg("DEBUG => Only moving one host source={$options['start']}!", 3);
        $options['end'] = $options['start'];
    }


    // Find the "end" subnet record by IP address
    list($status, $rows, $new_subnet) = ona_find_subnet($options['new_start']);

    // If we didn't get a record then exit
    if (!$new_subnet or !$new_subnet['id']) {
        printmsg("DEBUG => Destination start address ({$options['new_start']}) isn't valid!", 3);
        $self['error'] = "ERROR => Destination (new_start) address specified isn't valid!";
        return(array(2, $self['error'] . "\n"));
    }
    // Make sure the "old" and "new" subnets are different subnets
    if ($old_subnet['id'] == $new_subnet['id']) {
        printmsg("DEBUG => Both the source IP range ({$options['start']}+) and the destination IP range ({$options['new_start']}+) are on the same subnet!", 3);
        $self['error'] = "ERROR => Both the source IP range and the destination IP range are on the same subnet!";
        return(array(2, $self['error'] . "\n"));
    }

    // If they specified a "new_end" address, make sure it's valid and on the same subnet as the new_start subnet
    if ($options['new_end']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $new_subnet_end) = ona_find_subnet($options['new_end']);

        // If we didn't get a record then exit
        if (!$new_subnet_end or !$new_subnet_end['id']) {
            printmsg("DEBUG => Destination end address ({$options['new_end']}) isn't valid!", 3);
            $self['error'] = "ERROR => Destination (new_end) address specified isn't valid!";
            return(array(3, $self['error'] . "\n"));
        }
        if ($new_subnet_end['id'] != $new_subnet['id']) {
            printmsg("DEBUG => Both destination addresses ({$options['new_start']} and {$options['new_end']}) must be on the same subnet!", 3);
            $self['error'] = "ERROR => Both the destination addresses (new_start and new_end) must be on the same subnet!";
            return(array(4, $self['error'] . "\n"));
        }
    }
    // If they didn't give an end, they're moving one host..
    // but to make our lives easier we set the "end" = "start";
    else {
        printmsg("DEBUG => Only moving one host destination={$options['new_start']}!", 3);
        $options['new_end'] = $options['new_start'];
    }


    // Check permissions at the subnet level
    if (!auth('interface_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(13, $self['error'] . "\n"));
    }


    // An array for all the interface records we'll be moving
    $to_move = array();

    // Message to display if we succeed
    $message = "";

    // Load all the interface records we'll be moving
    $i = 0;
    do {
        // FIXME: this should do a more advanced query someday! (like checking that the ipaddress is >= start and <= end
        list($status, $rows, $interface) = ona_get_interface_record(array('subnet_id' => $old_subnet['id']), 'ip_addr');
        if ($rows == 0) break;
        $i++;
        if ($interface['ip_addr'] >= ip_mangle($options['start'], 'numeric')) {
            if ($interface['ip_addr'] <= ip_mangle($options['end'], 'numeric')) {
                $to_move[$i] = $interface;
            }
        }
    } while ($i < $rows);

    $total_to_move = count($to_move);
    $total_assigned = 0;

    // If there's nothing to do, tell them
    if ($total_to_move == 0) {
        printmsg("DEBUG => There are no interfaces in the source address block!", 3);
        $self['error'] = "ERROR => There are no interfaces in the source address block!";
        return(array(6, $self['error'] . "\n"));
    }

    // Make sure we have a high enough "LVL" to modify the associated hosts
    foreach ($to_move as $interface) {
        // Load the associated host record
        list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
        list($status, $rows, $dns) = ona_get_dns_record(array('id' => $host['primary_dns_id'], 'type' => 'A'));
        // Check permissions at the subnet level
        if (!authlvl($host['LVL'])) {
            $self['error'] = "Permission denied! Can't modify Host: {$host['id']} {$dns['fqdn']}";
            printmsg($self['error'], 0);
            return(array(14, $self['error'] . "\n"));
        }
        // Check to see if the host has any interfaces in the destination subnet
// MP: this is now allowed
//         list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $interface['host_id'], 'subnet_id' => $new_subnet['id']));
//         if ($status or $rows) {
//             printmsg("DEBUG => Source host {$ddns['fqdn']} already has an interface on the destination subnet!",3);
//             $self['error'] = "ERROR => Source host {$dns['fqdn']} (ID {$host['id']}) already has an interface on the destination subnet!";
//             return(array(15, $self['error'] . "\n"));
//         }
    }

    // Get the numeric version of the start/end addresses we are moving interfaces to
    // .. and make sure that the $low_ip and $high_ip are not subnet or broadcast addresses!
    $low_ip  = ip_mangle($options['new_start'], 'numeric');
    $high_ip = ip_mangle($options['new_end'], 'numeric');
    if ($low_ip  == $new_subnet['ip_addr']) { $low_ip++; }
    $num_hosts = 0xffffffff - $new_subnet['ip_mask'];
    if ($high_ip == ($new_subnet['ip_addr'] + $num_hosts)) { $high_ip--; }
    printmsg("INFO => Asked to move {$total_to_move} interfaces to new range: " . ip_mangle($low_ip, 'dotted') . ' - ' . ip_mangle($high_ip, 'dotted'), 0);

    // Loop through each interface we need to move, and find an available address for it.
    $pool_interfering = 0;
    foreach (array_keys($to_move) as $i) {
        while ($low_ip <= $high_ip) {
            list($status, $rows, $interface) = ona_get_interface_record(array('ip_addr' => $low_ip));
            if ($rows == 0 and $status == 0) {
                // Since the IP seems available, let's double check and make sure it's not in a DHCP address pool
                list($status, $rows, $pool) = ona_get_dhcp_pool_record("ip_addr_start < '{$low_ip}' AND ip_addr_end > '{$low_ip}'");
                if ($rows == 0 and $status == 0) {
                    // The IP is available, lets use it!
                    $to_move[$i]['new_ip_address'] = $low_ip;
                    $total_assigned++;
                    $low_ip++;
                    break;
                }
                $pool_interfering = 1;
                printmsg("DEBUG => Couldn't use the DHCP POOL address: " . ip_mangle($low_ip, 'dotted'), 3);
            }
            $low_ip++;
        }
    }

    // If total_assigned != total_to_move, error - not enough free IP addresses in destination subnet!
    if ($total_assigned != $total_to_move) {
        printmsg("DEBUG => The destination IP range doesn't have enough free IP addresses!", 3);
        $self['error'] = "ERROR => The destination IP range doesn't have enough free IP addresses!\n";
    if ($pool_interfering)
        $self['error'] .= "INFO => Some IPs in the destination range were part of a DHCP pool range.\n";
        return(array(6, $self['error']));
    }


    // Display what we would have done if "commit" isn't "yes"
    if ($options['commit'] != "Y") {
        $self['error'] = "Interface(s) NOT MOVED (see \"commit\" option)";
        $text = $self['error'] . "\n" .
                "Displaying {$total_to_move} interface(s) that would have been moved:\n\n";
        foreach ($to_move as $interface) {
            // Get display the hostname we would have moved, as well as it's IP address.
            list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
            list($status, $rows, $dns) = ona_get_dns_record(array('id' => $host['primary_dns_id'], 'type' => 'A'));
            $hostname = strtolower("{$dns['fqdn']}");
            $text .= "  " . ip_mangle($interface['ip_addr'], 'dotted') . " -> " . ip_mangle($interface['new_ip_address'], 'dotted') . "\t({$hostname})\n";
        }
        $text .= "\n";
        return(array(7, $text));
    }

    // Loop through and update each interface's IP_ADDRESS and SUBNET_ID
    $text = "SUCCESS => {$total_to_move} interface(s) moved\n";
    $text .= "Interface(s) moved:\n\n";
    foreach ($to_move as $interface) {
        list($status, $rows) = ona_update_record("interfaces",
                                                  // Where:
                                                  array('id' => $interface['id']),
                                                  // Update:
                                                  array(
                                                        'ip_addr' => $interface['new_ip_address'],
                                                        'subnet_id' => $new_subnet['id'],
                                                       )
                                                 );
        if ($status != 0 or $rows != 1) {
            $self['error'] = "ERROR => Database update failed! {$self['error']}";
            return(array(8, $self['error'] . "\n"));
        }
        // Get display the hostname we would have moved, as well as its IP address.
        list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
        list($status, $rows, $dns) = ona_get_dns_record(array('id' => $host['primary_dns_id'], 'type' => 'A'));
        $hostname = strtolower("{$dns['fqdn']}");
        $text .= "  " . ip_mangle($interface['ip_addr'], 'dotted') . " -> " . ip_mangle($interface['new_ip_address'], 'dotted') . "\t({$hostname})\n";
    }

    // Return the success notice
    return(array(0, $text));
}





///////////////////////////////////////////////////////////////////////
//  Function: interface_move_host (string $options='')
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
//  Example: list($status, $result) = interface_move_host('');
///////////////////////////////////////////////////////////////////////
function interface_move_host($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_move_host({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_move_host-v{$version}
  Move an interface to a new host

  Synopsis: interface_move_host [KEY=VALUE] ...

  Required:
    ip=[address|ID]       the IP address or ID of the interface
    host=[fqdn|ID]        the fqdn or ID of the new host

\n
EOM
        ));
    }


    // Find the Host they are looking for
    list($status, $rows, $host) = ona_find_host($options['host']);
    if (!$host['id']) {
        printmsg("DEBUG => The host specified, {$options['host']}, does not exist!",3);
        $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Host selected: {$options['host']}", 3);


    // Find the interface that is moving
    list($status, $rows, $interface) = ona_find_interface($options['ip']);
    if (!$interface['id']) {
        printmsg("DEBUG => The interface specified, {$options['ip']}, does not exist!",3);
        $self['error'] = "ERROR => The interface specified, {$options['ip']}, does not exist!";
        return(array(3, $self['error'] . "\n"));
    }

    // check if this interface is the primary DNS interface address.
    list($status, $rows, $primaryhost) = ona_get_host_record(array('id' => $interface['host_id']));
    list($status, $rows, $primarydns) = ona_get_dns_record(array('id' => $primaryhost['primary_dns_id']));
    if ($primarydns['interface_id'] == $interface['id']) {
        printmsg("DEBUG => This interface is part of the primary DNS name for {$primaryhost['fqdn']}, please assign a new primary DNS.",3);
        $self['error'] = "ERROR => This interface is part of the primary DNS name for {$primaryhost['fqdn']}, please assign a new primary DNS.";
        return(array(4, $self['error'] . "\n"));
    }

    // if this is the last interface on the host display a message
    // TODO: MP is this best? I would think a lot of people WANT to move the last IP before removing the host
    // it would cut some steps of having to delete/re-add when moving an IP.  maybe allow this?!?
    // ------ Since most hosts use the last interface as a primary dns id then they cant move the last interface.--------

//     list($status, $rows, $int) = db_get_records($onadb, 'interfaces', array('host_id' => $interface['host_id'], '', 0);
//     if ($rows == 1) {
//         printmsg("DEBUG => You cannot delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).",3);
//         $self['error'] = "ERROR => You can not delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).";
//         return(array(5, $self['error'] . "\n"));
//     }
    printmsg("DEBUG => Interface selected: {$options['ip']}", 3);

    // Check that this interface is not associated with this host via an interface_cluster
    list($status, $rows, $int_cluster) = db_get_records($onadb, 'interface_clusters', array('host_id' => $host['id'],'interface_id' => $interface['id']), '', 0);
    printmsg("DEBUG => interface_move_host() New host is clustered with this IP, Deleting cluster record", 3);
    if ($rows == 1) {
        // Delete the interface_cluster if there is one
        list($status, $rows) = db_delete_records($onadb, 'interface_clusters', array('interface_id' => $interface['id'],'host_id' => $host['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => interface_move_host() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(14, $self['error'] . "\n"));
        }
    }

    // If the interface being moved has a NAT IP then the ext interface needs the host_id updated as well
    if ($interface['nat_interface_id'] > 0) {
        printmsg("DEBUG => interface_move_host() Moving interface with NAT IP.", 3);
        list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['nat_interface_id']), array('host_id' => $host['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => interface_move_host() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(15, $self['error'] . "\n"));
        }
    }

    // Update the interface record
    list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['id']), array('host_id' => $host['id']));
    if ($status or !$rows) {
        $self['error'] = "ERROR => interface_move_host() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(16, $self['error'] . "\n"));
    }

    $text = "INFO => Interface " . ip_mangle($interface['ip_addr'], 'dotted') . " moved to {$host['fqdn']}";
    printmsg($text, 0);

    // Return the success notice
    return(array(0, $text. "\n"));
}











///////////////////////////////////////////////////////////////////////
//  Function: interface_share (string $options='')
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
//  Example: list($status, $result) = interface_share('');
///////////////////////////////////////////////////////////////////////
function interface_share($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_share({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_share-v{$version}
  Share an interface with another host.
  An IP address only exists once in the database.  This allows
  you to share that IP with several other hosts which are configured
  to use technologies such as HSRP, CARP, VRRP etc.

  Synopsis: interface_share [KEY=VALUE] ...

  Required:
    ip=[address|ID]       the IP address or ID of the interface
    host=[fqdn|ID]        the fqdn or ID of the host

  Optional:
    name=TEXT             interface name used on host, if different

\n
EOM
        ));
    }


    // Find the Host they are looking for
    list($status, $rows, $host) = ona_find_host($options['host']);
    if (!$host['id']) {
        printmsg("DEBUG => The host specified, {$options['host']}, does not exist!",3);
        $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Host selected: {$options['host']}", 3);


    // Find the interface
    list($status, $rows, $interface) = ona_find_interface($options['ip']);
    if (!$interface['id']) {
        printmsg("DEBUG => The interface specified, {$options['ip']}, does not exist!",3);
        $self['error'] = "ERROR => The interface specified, {$options['ip']}, does not exist!";
        return(array(3, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Interface selected: {$options['ip']}", 3);

    // Check that this interface is not associated with this host via an interface_cluster already
    list($status, $rows, $int_cluster) = db_get_records($onadb, 'interface_clusters', array('host_id' => $host['id'],'interface_id' => $interface['id']), '', 0);
    if ($rows == 1) {
        printmsg("DEBUG => This host is already clustered with that IP ({$host['fqdn']}-{$interface['ip_addr']}).", 3);
        $self['error'] = "ERROR => This host is already clustered with that IP ({$host['fqdn']}-{$interface['ip_addr']}).";
        return(array(13, $self['error'] . "\n"));
    }


    // Add the interface_cluster entry
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'interface_clusters',
            array(
                'host_id'                  => $host['id'],
                'interface_id'             => $interface['id'],
                'name'                     => $options['name']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => interface_share() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(14, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => Interface Shared: " . ip_mangle($interface['ip_addr'], 'dotted') . " to {$host['fqdn']}.";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));




}









///////////////////////////////////////////////////////////////////////
//  Function: interface_share_del (string $options='')
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
//  Example: list($status, $result) = interface_share_del('');
///////////////////////////////////////////////////////////////////////
function interface_share_del($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_share_del({$options['ip']}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_share_del-v{$version}
  Delete a shareed interface from another host.
  An IP address only exists once in the database.  This allows
  you to share that IP with several other hosts which are configured
  to use technologies such as HSRP, CARP, VRRP etc.

  Synopsis: interface_share_del [KEY=VALUE] ...

  Required:
    ip=[address|ID]       the IP address or ID of the interface
    host=[fqdn|ID]        the fqdn or ID of the host

\n
EOM
        ));
    }


    // Find the Host they are looking for
    list($status, $rows, $host) = ona_find_host($options['host']);
    if (!$host['id']) {
        printmsg("DEBUG => The host specified, {$options['host']}, does not exist!",3);
        $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Host selected: {$options['host']}", 3);


    // Find the interface
    list($status, $rows, $interface) = ona_find_interface($options['ip']);
    if (!$interface['id']) {
        printmsg("DEBUG => The interface specified, {$options['ip']}, does not exist!",3);
        $self['error'] = "ERROR => The interface specified, {$options['ip']}, does not exist!";
        return(array(3, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Interface selected: {$options['ip']}", 3);

    // Check that this interface is not associated with this host via an interface_cluster already
    list($status, $rows, $int_cluster) = db_get_records($onadb, 'interface_clusters', array('host_id' => $host['id'],'interface_id' => $interface['id']), '', 0);
    if ($rows == 0) {
        printmsg("DEBUG => Unable to find share ({$host['fqdn']}-{$interface['ip_addr_text']}).", 3);
        $self['error'] = "ERROR => Unable to find share ({$host['fqdn']}-{$interface['ip_addr_text']}).";
        return(array(13, $self['error'] . "\n"));
    }

    // Drop the record
    list($status, $rows) = db_delete_records($onadb, 'interface_clusters', array('host_id' => $host['id'],'interface_id' => $interface['id']));
    if ($status or !$rows) {
        $self['error'] = "ERROR => interface_share_del() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(5, $self['error']));
    }


    // Return the success notice
    $self['error'] = "INFO => Interface Share deleted: {$interface['ip_addr_text']} from {$host['fqdn']}.";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));




}






///////////////////////////////////////////////////////////////////////
//  Function: nat_add (string $options='')
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
//  Example: list($status, $result) = nat_add('');
///////////////////////////////////////////////////////////////////////
function nat_add($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => nat_add({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['natip'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

nat_add-v{$version}
  Add a NAT entry to an existing IP

  Synopsis: nat_add [KEY=VALUE] ...

  Required:
    ip=[address|ID]       the IP address or ID of the existing inside interface
    natip=IP_ADDRESS      the IP address of the external NAT entry

\n
EOM
        ));
    }


    // Find the internal interface
    list($status, $rows, $interface) = ona_find_interface($options['ip']);
    if (!$interface['id']) {
        printmsg("DEBUG => The interface specified, {$options['ip']}, does not exist!",3);
        $self['error'] = "ERROR => The interface specified, {$options['ip']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Interface selected: {$options['ip']}", 3);

    if ($interface['nat_interface_id'] > 0) {
        printmsg("DEBUG => The interface specified, already has a NAT IP!",3);
        $self['error'] = "ERROR => The interface specified, already has a NAT IP!";
        return(array(3, $self['error'] . "\n")); 
    }

    // Add the new external NAT interface to the database
    $newint['host'] = $interface['host_id'];
    $newint['ip'] = $options['natip'];
    $newint['mac'] = '';
    $newint['name'] = '';
    $newint['description'] = 'EXT NAT';
    printmsg("DEBUG => nat_add() calling interface_add() for new ip: {$options['natip']}", 3);
    list($status, $output) = run_module('interface_add', $newint);
    if ($status) { return(array($status, $output)); }
    $self['error'] .= $output;

    // Find the interface_id for the interface we just added
    list($status, $rows, $int) = ona_find_interface($options['natip']);

    // update the existing inside interface with the new nat_interface_id value
    list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['id']), array('nat_interface_id' => $int['id']));
    if ($status or !$rows) {
        $self['error'] = "ERROR => nat_add() SQL Query failed to update nat_interface_id for interface: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(4, $self['error'] . "\n"));
    }



    // Return the success notice
    $self['error'] = "INFO => External NAT entry added: {$interface['ip_addr_text']} => {$int['ip_addr_text']}.";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));

}






///////////////////////////////////////////////////////////////////////
//  Function: nat_del (string $options='')
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
//  Example: list($status, $result) = nat_del('');
///////////////////////////////////////////////////////////////////////
function nat_del($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => nat_del({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['natip'] and $options['ip']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

nat_del-v{$version}
  Delete a NAT entry from an existing IP
  This will delete the NAT IP interface from the subnet as well.

  Synopsis: nat_del [KEY=VALUE] ...

  Required:
    ip=[address|ID]       the IP address or ID of the existing inside interface
    natip=[address|ID]    the IP address or ID of the external NAT entry

  Optional:
    commit=[yes|no]       commit db transaction (no)

\n
EOM
        ));
    }

    // Sanitize "options[commit]" (no is the default)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Find the internal interface
    list($status, $rows, $interface) = ona_find_interface($options['ip']);
    if (!$interface['id']) {
        printmsg("DEBUG => The interface specified, {$options['ip']}, does not exist!",3);
        $self['error'] = "ERROR => The interface specified, {$options['ip']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Interface selected: {$options['ip']}", 3);

    // Find the NAT interface
    list($status, $rows, $natinterface) = ona_find_interface($options['natip']);
    if (!$natinterface['id']) {
        printmsg("DEBUG => The NAT interface specified, {$options['natip']}, does not exist!",3);
        $self['error'] = "ERROR => The NAT interface specified, {$options['natip']}, does not exist!";
        return(array(3, $self['error'] . "\n"));
    }
    printmsg("DEBUG => NAT Interface selected: {$options['natip']}", 3);

    // Check that the two IP addresses are really paired with each other
    if ($interface['nat_interface_id'] != $natinterface['id']) {
        $self['error'] = "ERROR => nat_del() The provided IP addresses are not associated with each other for NAT.";
        printmsg($self['error'], 0);
        return(array(4, $self['error'] . "\n"));
    }

    printmsg("DEBUG => nat_del() calling interface_del() for ip: {$options['natip']}", 3);
    $natint['interface'] = $natinterface['id'];
    $natint['commit'] = $options['commit'];
    list($status, $output) = run_module('interface_del', $natint);
    if ($status) { return(array($status, $output)); }
    $self['error'] .= $output;

    // update the existing inside interface and remove the old nat_interface_id value
    list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['id']), array('nat_interface_id' => '0'));
    if ($status or !$rows) {
        $self['error'] = "ERROR => nat_del() SQL Query failed to update nat_interface_id for interface: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(5, $self['error'] . "\n"));
    }



    // Return the success notice
    $self['error'] = "INFO => External NAT entry deleted: {$natinterface['ip_addr_text']} from {$interface['ip_addr_text']}.";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));

}






?>

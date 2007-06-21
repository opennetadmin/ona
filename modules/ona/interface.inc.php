<?

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
    $version = '1.02';

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

  Notes:
    * DOMAIN will default to FIXME: .albertsons.com if not specified
\n
EOM
        ));
    }


    // Set options[force] to N if it's not set
    $options['force'] = sanitize_YN($options['force'], 'N');

    // Set options[create_ptr] and options[create_a] to Y if they're not set
    $options['create_ptr'] = sanitize_YN($options['create_ptr'], 'Y');

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
    printmsg("DEBUG => Host selected: {$host['fqdn']}", 3);

    // Translate IPv4 address to a number
    $orig_ip= $options['ip'];
    $options['ip'] = ip_mangle($options['ip'], 1);
    if ($options['ip'] == -1) {
        printmsg("DEBUG => Invalid IP address ({$orig_ip})",3);
        $self['error'] = "ERROR => Invalid IP address ({$orig_ip})!";
        return(array(3, $self['error'] . "\n"));
    }

    // Validate that there isn't already another interface with the same IP address
    list($status, $rows, $interface) = ona_get_interface_record(array('ip_addr' => $options['ip']));
    if ($rows) {
        printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!",3);
        $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!";
        return(array(4, $self['error'] . "\n" .
                        "INFO => Conflicting interface record ID: {$interface['id']}\n"));
    }

    // Since the IP seems available, let's double check and make sure it's not in a DHCP address pool
/*    list($status, $rows, $pool) = ona_get_dhcp_pool_record("IP_ADDRESS_START < '{$options['ip']}' AND IP_ADDRESS_END > '{$options['ip']}'");
    if ($status or $rows) {
        printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!",3);
        $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!";
        return(array(5, $self['error'] . "\n" .
                        "INFO => Conflicting DHCP pool record ID: {$pool['DHCP_POOL_ID']}\n"));
    }*/

    // Find the Subnet (network) ID to use from the IP address
    list($status, $rows, $subnet) = ona_find_subnet($options['ip']);
    if ($status or $rows != 1 or !$subnet['id']) {
        printmsg("DEBUG => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!",3);
        $self['error'] = "ERROR => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!";
        return(array(6, $self['error'] . "\n"));
    }
    printmsg("DEBUG => Subnet selected: {$subnet['description']}", 3);

    // Validate that the IP address supplied isn't the base or broadcast of the subnet
    if ($options['ip'] == $subnet['ip_addr']) {
        printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!",3);
        $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!";
        return(array(7, $self['error'] . "\n"));
    }
    if ($options['ip'] == ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']) ) {
        printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's broadcast address!",3);
        $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be the subnet broadcast address!";
        return(array(8, $self['error'] . "\n"));
    }

    // Validate that the specified host doesn't have any other interfaces on the
    // same subnet as the new ip address.  Allow an override though.
    if ($options['force'] == 'N') {
        // Search for any existing interfaces on the same subnet
        list($status, $rows, $interface) = ona_get_interface_record(array('subnet_id' => $subnet['id'],
                                                                           'host_id'    => $host['id']));
        if ($status or $rows) {
            printmsg("DEBUG => The specified host already has another interface on the same subnet as the new IP address (" . ip_mangle($orig_ip,'dotted') . ").",3);
            $self['error'] = "ERROR => The specified host already has another interface on the same subnet as the new IP address (" . ip_mangle($orig_ip,'dotted') . ").";
            return(array(9, $self['error'] . "\n" .
                            "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n" .
                            "INFO => Conflicting interface record ID: {$interface['id']}\n"));
        }
    }

    // Remove any MAC address formatting
    if ($options['mac']) {
        $orig_mac = $options['mac'];
        $options['mac'] = mac_mangle($options['mac'], 1);
        if ($options['mac'] == -1) {
            printmsg("DEBUG => The MAC address specified ({$orig_mac}) is invalid!",3);
            $self['error'] = "ERROR => The MAC address specified ({$orig_mac}) is invalid!";
            return(array(10, $self['error'] . "\n"));
        }

        // Unless they have opted to allow duplicate mac addresses ...
        if ($options['force'] == 'N') {
            // Validate that there isn't already another interface with the same MAC address
            list($status, $rows, $interface) = ona_get_interface_record(array('mac_addr' => $options['mac']));
            if ($status or $rows) {
                printmsg("DEBUG => MAC conflict: That MAC address ({$options['mac']}) is already in use!",3);
                $self['error'] = "ERROR => MAC conflict: That MAC address ({$options['mac']}) is already in use";
                return(array(11, $self['error'] . "\n" .
                                "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n" .
                                "INFO => Conflicting interface record ID: {$interface['id']}\n"));
            }
        }
    }

    // FIXME: what if the host already has another interface with create_ptr enabled?

    // Check permissions
    if (!auth('host_add') or !authlvl($host['LVL']) or !authlvl($subnet['LVL'])) {
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
    // Skip the BOOTP_TYPE and LAST_PING_RESPONSE empty
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
/* FIXME:       'CREATE_REVERSE_DNS_ENTRY' => $options['create_ptr'], */
                'name'                     => $options['name'],
                'description'              => $options['description']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => interface_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(14, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => Interface ADDED: " . ip_mangle($options['ip'], 'dotted') . " on  {$host['fqdn']}";
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
    $version = '1.04';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['interface'] and !$options['host']) or
       (!$options['set_ip'] and
        !$options['set_mac'] and
        !$options['set_description'] and
        !$options['set_create_ptr'] and
        !$options['set_name']
       ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

interface_modify-v{$version}
Modify an interface record

  Synopsis: interface_modify [KEY=VALUE] ...

  Where:
    interface=ID or IP or MAC     interface ID or IP address
     or
    host=NAME[.DOMAIN] or ID      find interface by hostname or host_id

  Update:
    set_ip=IP                     change IP address (numeric or dotted format)
    set_mac=ADDRESS               change the mac address (most formats ok)
    set_name=NAME                 interface name (i.e. "FastEthernet0/1.100")
    set_description=TEXT          description (i.e. "VPN link to building 3")
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
        list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
        if ($rows > 1) {
            printmsg("DEBUG => Specified host ({$options['host']}) has more than one interface!",3);
            $self['error'] = "ERROR => Specified host ({$options['host']}) has more than one interface!";
            return(array(3, $self['error'] . "\n"));
        }
    }

    // If we didn't get a record then exit
    if (!$interface or !$interface['id']) {
        printmsg("DEBUG => Interface not found ({$options['interface']})!",3);
        $self['error'] = "ERROR => Interface not found ({$options['interface']})!";
        return(array(4, $self['error'] . "\n"));
    }


    // Set options[force] to N if it's not set
    $options['force'] = sanitize_YN($options['force'], 'N');

    // This array will contain the updated info we'll insert into the DB
    $SET = array();

    // Setting an IP address?
    if ($options['set_ip']) {
        $orig_ip = $options['set_ip'];
        $options['set_ip'] = ip_mangle($options['set_ip'], 'numeric');
        if ($options['set_ip'] == -1) {
            printmsg("DEBUG => Invalid IP address ({$orig_ip})",3);
            $self['error'] = "ERROR => Invalid IP address ({$orig_ip})";
            return(array(5, $self['error'] . "\n"));
        }
        // Validate that there isn't already another interface with the same IP address
        list($status, $rows, $record) = ona_get_interface_record(array('ip_addr' => $options['set_ip']));
        if ($rows and $record['id'] != $interface['id']) {
            printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") is already in use!",3);
            $self['error'] = "ERROR => IP conflict: specified IP (" . ip_mangle($orig_ip,'dotted') . ") is already in use!";
            return(array(6, $self['error'] . "\nINFO => Conflicting interface record ID: {$record['ID']}\n"));
        }

        // Since the IP seems available, let's double check and make sure it's not in a DHCP address pool
/*        list($status, $rows, $pool) = ona_get_dhcp_pool_record("IP_ADDRESS_START < '{$options['set_ip']}' AND IP_ADDRESS_END > '{$options['set_ip']}'");
        if ($status or $rows) {
            printmsg("DEBUG => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!",3);
            $self['error'] = "ERROR => IP conflict: That IP address (" . ip_mangle($orig_ip,'dotted') . ") falls within a DHCP address pool!";
            return(array(5, $self['error'] . "\n" .
                            "INFO => Conflicting DHCP pool record ID: {$pool['DHCP_POOL_ID']}\n"));
        }*/

        // Find the Subnet (network) ID to use from the IP address
        list($status, $rows, $subnet) = ona_find_subnet(ip_mangle($options['set_ip'], 'dotted'));
        if ($status or !$rows) {
            printmsg("DEBUG => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!",3);
            $self['error'] = "ERROR => That IP address (" . ip_mangle($orig_ip,'dotted') . ") is not inside a defined subnet!";
            return(array(7, $self['error'] . "\n"));
        }

        // Validate that the IP address supplied isn't the base or broadcast of the subnet
        if ($options['set_ip'] == $subnet['ip_addr']) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's base address!";
            return(array(8, $self['error'] . "\n"));
        }
        if ($options['set_ip'] == ((4294967295 - $subnet['ip_mask']) + $subnet['ip_addr']) ) {
            printmsg("DEBUG => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be a subnet's broadcast address!",3);
            $self['error'] = "ERROR => IP address (" . ip_mangle($orig_ip,'dotted') . ") can't be the subnet broadcast address!";
            return(array(9, $self['error'] . "\n"));
        }

        // Validate that the specified host doesn't have any other interfaces on the
        // same subnet as the new ip address.  Allow an override though.
        if ($options['force'] != 'Y') {
            // Search for any existing interfaces on the same subnet
            list($status, $rows, $record) = ona_get_interface_record(array('subnet_id' => $subnet['id'],
                                                                            'host_id'    => $interface['host_id']));
            if (($rows and $record['id'] != $interface['id']) or $rows > 1) {
                printmsg("DEBUG => The specified host already has another interface on the same subnet as the new IP address (" . ip_mangle($orig_ip,'dotted') . ").",3);
                $self['error'] = "ERROR => The specified host already has another interface on the same subnet as the new IP address (" . ip_mangle($orig_ip,'dotted') . ").";
                return(array(10, $self['error'] . "\n" .
                                  "NOTICE => You may ignore this error and add the interface anyway with the \"force=yes\" option.\n" .
                                  "INFO => Conflicting interface record ID: {$record['id']}\n"));
            }

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

        // Check permissions
        if (!authlvl($subnet['LVL'])) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(13, $self['error'] . "\n"));
        }

        // Everything looks ok, add it to $SET
        if($interface['subnet_id'] != $subnet['id'])
            $SET['subnet_id'] = $subnet['id'];
        if($interface['ip_addr'] != $options['set_ip'])
            $SET['ip_addr'] = $options['set_ip'];
    }


    // Setting an MAC address?
    if (array_key_exists('set_mac', $options)) {
        if ($options['set_mac']) {  // allow null mac addresses (to unset one for example)
            $orig_mac = $options['set_mac'];
            $options['set_mac'] = mac_mangle($options['set_mac'], 1);
            if ($options['set_mac'] == -1) {
                printmsg("DEBUG => The MAC address specified ({$orig_mac}) is invalid!",3);
                $self['error'] = "ERROR => The MAC address specified ({$orig_mac}) is invalid!";
                return(array(11, $self['error'] . "\n"));
            }

            // Unless they have opted to allow duplicate mac addresses ...
            if ($options['force'] != 'Y') {
                // Validate that there isn't already another interface with the same MAC address
                list($status, $rows, $record) = ona_get_interface_record(array('mac_addr' => $options['set_mac']));
                if (($rows and $record['id'] != $interface['id']) or $rows > 1) {
                    printmsg("DEBUG => MAC conflict: That MAC address ({$options['set_mac']}) is already in use!",3);
                    $self['error'] = "ERROR => MAC conflict: That MAC address ({$options['set_mac']}) is already in use!";
                    return(array(12, $self['error'] . "\n" .
                                    "NOTICE => You may ignore this error and update the interface anyway with the \"force=yes\" option.\n" .
                                    "INFO => Conflicting interface record ID: {$record['id']}\n"));
                }
            }
        }
        if($interface['mac_addr'] != $options['set_mac'])
            $SET['mac_addr'] = $options['set_mac'];
    }

    // Set options[create_a]?
//    if (array_key_exists('set_create_a', $options)) {
//        $SET['CREATE_DNS_ENTRY'] = sanitize_YN($options['set_create_a'], 'Y');
//    }

    // Set options[create_ptr]?
//    if (array_key_exists('set_create_ptr', $options)) {
//        // FIXME: what if the host already has another interface with create_ptr enabled?
//        $SET['CREATE_REVERSE_DNS_ENTRY'] = sanitize_YN($options['set_create_ptr'], 'Y');
//    }

    // Set options[set_name]?
    if (array_key_exists('set_name', $options) && $interface['name'] != $options['set_name']) {
        $SET['name'] = $options['set_name'];
    }

    // Set options[set_description]?
    if (array_key_exists('set_description', $options) && $interface['description'] != $options['set_description']) {
        $SET['description'] = $options['set_description'];
    }

    // Check permissions
    list($status, $rows, $host) = ona_find_host($interface['host_id']);
    if (!auth('interface_modify') or !authlvl($host['LVL'])) {
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
    $self['error'] = "INFO => Interface UPDATED:{$interface['id']}: ". ip_mangle($new_int['ip_addr'],'dotted');

    $log_msg = "INFO => Interface UPDATED:{$interface['id']}: ";
    $more="";
    foreach(array_keys($original_interface) as $key) {
        if($original_interface[$key] != $new_interface[$key]) {
            $log_msg .= $more . $key . "[" .$original_interface[$key] . "=>" . $new_interface[$key] . "]";
            $more= ";";
        }
    }

    // only print to logfile if a change has been made to the record
    if($more != '') {
        printmsg($self['error'], 0);
        printmsg($log_msg, 0);
    }

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
    $version = '1.03';

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
    * DOMAIN will default to .albertsons.com if not specified
\n
EOM
        ));
    }


    // Sanitize "options[commit]" (no is the default)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // They provided a interface ID, IP address, interface name, or MAC address
    if ($options['interface']) {
        // Find an interface record by something in that interface's record
        list($status, $rows, $interface) = ona_find_interface($options['interface']);
    }

// I've commented out the option to specify a host.  this does not make sense if you are not allowed
// to delete the last interface on a host. and if a host has more than one interface, you will only
// delete a random interface from the host which is dangerous.

    // If a hostname was provided, do a search based on that
 /*   else if ($options['host']) {
        // Find a host by the user's input
        list($status, $rows, $host) = ona_find_host($options['host']);
        if (!$host['id']) {
            $self['error'] = "ERROR => No interfaces found: no such host!";
            return(array(2, $self['error'] . "\n"));
        }
        // If we got one, load an associated interface
        list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $host['id']));
    }*/

    // If we didn't get a record then exit
    if (!$interface or !$interface['id']) {
        printmsg("DEBUG => Interface not found ({$options['interface']})!",3);
        $self['error'] = "ERROR => Interface not found ({$options['interface']})!";
        return(array(4, $self['error'] . "\n"));
    }

    // Load associated records
    list($status, $rows, $host) = ona_find_host($interface['host_id']);
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));

    // Check what DNS records are associated with the host.
    // FIXME: MP for now I'm bailing on the delete.  I wanted to make sure this check was here and have not put a
    // lot of thought into it yet.  I assume we can display something nice to allow them to remove the dns records too
    list($status, $total_dns, $dns) = db_get_records($onadb, 'dns', array('interface_id' => $interface['id']), '', 0);
    printmsg("DEBUG => total DNS records => {$total_dns}", 3);
    if ($total_dns > 0) {
        printmsg("DEBUG => There are {$total_dns} DNS record(s) associated with this interface, you must remove them first.",3);
        $self['error'] = "ERROR => There are {$total_dns} DNS record(s) associated with this interface, you must remove them first.";
        return(array(11, $self['error'] . "\n"));
    }

    // Check if this is the last interface on a host
    list($status, $total_interfaces, $ints) = db_get_records($onadb, 'interfaces', array('host_id' => $interface['host_id']), '', 0);
    printmsg("DEBUG => total interfaces => {$total_interfaces}", 3);
    if ($total_interfaces == 1) {
        printmsg("DEBUG => You cannot delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).",3);
        $self['error'] = "ERROR => You can not delete the last interface on a host, you must delete the host itself ({$host['fqdn']}).";
        return(array(13, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('interface_del') or !authlvl($host['LVL']) or !authlvl($subnet['LVL'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(13, $self['error'] . "\n"));
    }

    // If "commit" is yes, delete the interface
    if ($options['commit'] == 'Y') {
        printmsg("DEBUG => Deleting interface: ID {$interface['id']}", 3);

        // Drop the record
        list($status, $rows) = db_delete_records($onadb, 'interfaces', array('id' => $interface['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => interface_delete() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(5, $self['error']));
        }
        // Build a success notice to return to the user
        $text = "INFO => Interface DELETED: " . ip_mangle($interface['ip_addr'], 'dotted') . "  from {$host['fqdn']}";
        printmsg($text, 0);

        // Check to see if there are any other interfaces for the current host_id
        // If there aren't, we need to tell the user to delete the host!
        // since we've disallowed removal of the last interface, this should never happen!!!!!
        list($status, $rows, $record) = ona_get_interface_record(array('host_id' => $interface['host_id']));
        if ($rows == 0) {
            printmsg("ERROR => Host {$host['fqdn']} has NO remaining interfaces!", 0);
            $text .= "\n" . "WARNING => Host {$host['fqdn']} has NO remaining interfaces!\n" .
                            "           Delete this host or add an interface to it now!\n";
        }

        // Return the success notice
        return(array(0, $text));
    }

    // Otherwise, just display the interface that we will be deleting
    list($status, $text) = interface_display("interface={$interface['id']}&verbose=N");
    $text = "Record(s) NOT DELETED (see \"commit\" option)\n" .
            "Displaying record(s) that would have been deleted:\n\n" .
            $text;
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

    // Version - UPDATE on every edit!
    $version = '1.02';

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
    * DOMAIN will default to .albertsons.com if not specified
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

        // Unit record
// FIXME: (PK) this code isn't implemented yet
//        list($status, $rows, $unit) = ona_get_unit_record(array('location_id' => $host['location_id']));
//        if ($rows >= 1) {
//            $text .= "\nASSOCIATED LOCATION RECORD\n";
//            $text .= format_array($unit);
//        }

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
//  Example: list($status, $result) = interface_move('');
///////////////////////////////////////////////////////////////////////
function interface_move($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => interface_move({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.03';

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
  Move interfaces to a new subnet
  Replacement for the ona_renumber utility.
  Moves all interface addresses from one subnet to another.

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
    if (!auth('interface_modify') or !authlvl($old_subnet['LVL']) or !authlvl($new_subnet['LVL'])) {
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
            $self['error'] = "Permission denied! Can't modify Host: {$host['id']} {$dns['name']}.{$dns['fqdn']}";
            printmsg($self['error'], 0);
            return(array(14, $self['error'] . "\n"));
        }
        // Check to see if the host has any interfaces in the destination subnet
        list($status, $rows, $interface) = ona_get_interface_record(array('host_id' => $interface['host_id'], 'subnet_id' => $new_subnet['id']));
        if ($status or $rows) {
            printmsg("DEBUG => Source host {$dns['name']}.{$ddns['fqdn']} already has an interface on the destination subnet!",3);
            $self['error'] = "ERROR => Source host {$dns['name']}.{$dns['fqdn']} (ID {$host['id']}) already has an interface on the destination subnet!";
            return(array(15, $self['error'] . "\n"));
        }
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
                // FIXME: (PK) we haven't implemented the DHCP pool code yet!
                // list($status, $rows, $pool) = ona_get_dhcp_pool_record("IP_ADDRESS_START < '{$low_ip}' AND IP_ADDRESS_END > '{$low_ip}'");
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
// FIXME: (PK)        if ($pool_interfering)
//            $self['error'] .= "INFO => Some IPs in the destination range were part of a DHCP pool range.\n";
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
            $hostname = strtolower("{$dns['name']}.{$dns['fqdn']}");
            $text .= "  " . ip_mangle($interface['ip_addr'], 'dotted') . " -> " . ip_mangle($interface['new_ip_address'], 'dotted') . "\t({$hostname})\n";
        }
        $text .= "\n";
        return(array(7, $text));
    }

    // Loop through and update each interface's IP_ADDRESS and NETWORK_ID
    // FIXME: (PK) Need to test this section still (4.3.2007):
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
        $hostname = strtolower("{$dns['name']}.{$dns['fqdn']}");
        $text .= "  " . ip_mangle($interface['ip_addr'], 'dotted') . " -> " . ip_mangle($interface['new_ip_address'], 'dotted') . "\t({$hostname})\n";
    }

    // Return the success notice
    return(array(0, $text));
}









?>
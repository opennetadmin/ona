<?php

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);







///////////////////////////////////////////////////////////////////////
//  Function: subnet_display (string $options='')
//
//  Description:
//    Display an existing subnet.
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
//  Example: list($status, $text) = subnet_display('subnet=10.44.0.0');
///////////////////////////////////////////////////////////////////////
function subnet_display($options="") {
    global $conf, $self, $onadb;
    printmsg('DEBUG => subnet_display('.$options.') called', 3);

    $text_array = array();

    // Version - UPDATE on every edit!
    $version = '1.04';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['subnet']) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

subnet_display-v{$version}
Displays an subnet record from the database

  Synopsis: subnet_display [KEY=VALUE] ...

  Required:
    subnet=[ID|IP]               display subnet by search string

  Optional:
    verbose=[yes|no]              display additional info (yes)

  Notes:
    * An error is returned if search string returns more than one subnet
    * IP can be in dotted, numeric, or IPv6 format
\n
EOM
        ));
    }

    // Sanitize "options[verbose]" (yes is the default)
    $options['verbose'] = sanitize_YN($options['verbose'], 'Y');

    // They provided a subnet ID or IP address
    // Find a subnet record
    list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
    if ($status or !$rows) {
        $self['error'] = "ERROR => Subnet not found";
        return(array(2, $self['error'] . "\n"));
    }

    // Gather sizing
    list($percent,$total_used,$size) = get_subnet_usage($subnet['id']);
    $subnet['total_allocated_percent'] = $percent;
    $subnet['total_allocated'] = $total_used;
    $subnet['total_available'] = $size;

    // get subnet type name
    list($status, $rows, $sntype) = ona_get_subnet_type_record(array('id' => $subnet['subnet_type_id']));
    $subnet['subnet_type_name'] = $sntype['display_name'];

    // Convert some data
    $text_array = $subnet;
    $text_array['ip_addr_text'] = ip_mangle($subnet['ip_addr'], 'dotted');
    $text_array['ip_mask_text'] = ip_mangle($subnet['ip_mask'], 'dotted');
    $text_array['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

    // Build text to return
    $text  = "SUBNET RECORD\n";
    $text .= format_array($subnet);

    // If 'verbose' is enabled, grab some additional info to display
    if ($options['verbose'] == 'Y') {

        // Tag records
        list($status, $rows, $tags) = db_get_records($onadb, 'tags', array('type' => 'subnet', 'reference' => $subnet['id']));
        if ($rows) {
            $text .= "\nASSOCIATED TAG RECORDS\n";
            foreach ($tags as $tag) {
                $text_array['tags'][] = $tag['name'];
                $text .= "  {$tag['name']}\n";
            }
        }

        // VLAN record
        list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $subnet['vlan_id']));
        if ($rows) {
            $text_array['vlan'] = $vlan;
            $text .= "\nASSOCIATED VLAN RECORD\n";
            $text .= format_array($vlan);
        }

    }

    // cleanup some un-used junk
    unset($text_array['vlan_id']);

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
//  Function: subnet_add (string $options='')
//
//  Description:
//    Add a new subnet.
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
//  Example: list($status, $result) = subnet_add('');
///////////////////////////////////////////////////////////////////////
function subnet_add($options="") {
    global $conf, $self, $onadb;
    printmsg('DEBUG => subnet_add('.$options.') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.08';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
        !($options['ip'] and
          $options['netmask'] and
          $options['type'] and
          $options['name'])
       ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

subnet_add-v{$version}
Adds a new subnet (subnet) record

  Synopsis: subnet_add [KEY=VALUE] ...

  Required:
    name=TEXT               subnet name (i.e. "LAN-1234")
    ip=ADDRESS              dotted (10.0.0.0), IPv6, or numeric subnet address
    netmask=MASK            dotted (255.0.0.0), CIDR (/8), or numeric netmask
    type=TYPE               subnet type name or id

  Optional:
    vlan=VLAN               vlan name, number
    campus=CAMPUS           vlan campus name or id to help identify vlan
\n
EOM
        ));
    }

    //
    // Define the fields we're inserting
    //
    // This variable will contain the info we'll insert into the DB
    $SET = array();

    // Set vlan_id to 0 initially
    $SET['vlan_id'] = 0;

    // Prepare options[ip] - translate IP address to a number
    $options['ip'] = $ourip = ip_mangle($options['ip'], 'numeric');
    if ($ourip == -1) {
        $self['error'] = "ERROR => The IP address specified is invalid!";
        return(array(2, $self['error'] . "\n"));
    }

    // Prepare options[netmask] - translate IP address to a number
    $options['netmask'] = ip_mangle($options['netmask'], 'numeric');
    if ($options['netmask'] == -1) {
        $self['error'] = "ERROR => The netmask specified is invalid!";
        return(array(3, $self['error'] . "\n"));
    }

    // Validate the netmask is okay
    $cidr = ip_mangle($options['netmask'], 'cidr');
    if ($cidr == -1) {
        $self['error'] = "ERROR => The netmask specified is invalid!";
        return(array(4, $self['error'] . "\n"));
    }

    if(is_ipv4($ourip))  {
       // echo "ipv4";
       $padding = 32;
       $fmt = 'dotted';
       $ip1 = ip_mangle($ourip, 'binary');
       $num_hosts = 0xffffffff - $options['netmask'];
       $last_host = ($options['ip'] + $num_hosts);
    } else {
       // echo "ipv6";
       $padding = 128;
       $fmt = 'ipv6gz';
       $ip1 = ip_mangle($ourip, 'bin128');
       $sub = gmp_sub("340282366920938463463374607431768211455", $options['netmask']);
       $num_hosts = gmp_strval($sub); 
       $last_host = gmp_strval(gmp_add($options['ip'],$num_hosts));
    }

    // Validate that the subnet IP & netmask combo are valid together.
    $ip2 = str_pad(substr($ip1, 0, $cidr), $padding, '0');
    $ip1 = ip_mangle($ip1, $fmt);
    $ip2 = ip_mangle($ip2, $fmt);
    if ($ip1 != $ip2) {
        $self['error'] = "ERROR => Invalid subnet specified - did you mean: {$ip2}/{$cidr}?";
        return(array(5, $self['error'] . "\n"));
    }

    // *** Check to see if the new subnet overlaps any existing ONA subnets *** //
    // I convert the IP address to dotted format when calling ona_find_subnet()
    // because it saves it from doing a few unnecessary sql queries.

    // Look for overlaps like this (where new subnet address starts inside an existing subnet):
    //            [ -- new subnet -- ]
    //    [ -- old subnet --]
    list($status, $rows, $subnet) = ona_find_subnet(ip_mangle($options['ip'], 'dotted'));
    if ($rows != 0) {
        $self['error'] = "ERROR => Subnet address conflict! New subnet starts inside an existing subnet.";
        return(array(6, $self['error'] . "\n" .
                        "ERROR  => Conflicting subnet record ID: {$subnet['id']}\n"));
    }


    // Look for overlaps like this (where the new subnet ends inside an existing subnet):
    //    [ -- new subnet -- ]
    //           [ -- old subnet --]
    // Find last address of our subnet, and see if it's inside of any other subnet:
    list($status, $rows, $subnet) = ona_find_subnet(ip_mangle($last_host, 'dotted'));
    if ($rows != 0) {
        $self['error'] = "ERROR => Subnet address conflict! New subnet ends inside an existing subnet.";
        return(array(7, $self['error'] . "\n" .
                        "ERROR  => Conflicting subnet record ID: {$subnet['id']}\n"));
    }


    // Look for overlaps like this (where the new subnet entirely overlaps an existing subnet):
    //    [ -------- new subnet --------- ]
    //           [ -- old subnet --]
    //
    // Do a cool SQL query to find any subnets whoose start address is >= or <= the
    // new subnet base address.
    $where = "ip_addr >= {$options['ip']} AND ip_addr <= {$last_host}";
    list($status, $rows, $subnet) = ona_get_subnet_record($where);
    if ($rows != 0) {
        $self['error'] = "ERROR => Subnet address conflict! New subnet would encompass an existing subnet.";
        return(array(8, $self['error'] . "\n" .
                        "ERROR  => Conflicting subnet record ID: {$subnet['id']}\n"));
    }

    // The IP/NETMASK look good, set them.
    $SET['ip_addr'] = $options['ip'];
    $SET['ip_mask'] = $options['netmask'];


    // Find the type from $options[type]
    list($status, $rows, $subnet_type) = ona_find_subnet_type($options['type']);
    if ($status or $rows != 1) {
        $self['error'] = "ERROR => Invalid subnet type specified!";
        return(array(10, $self['error'] . "\n"));
    }
    printmsg("Subnet type selected: {$subnet_type['name']} ({$subnet_type['short_name']})", 1);
    $SET['subnet_type_id'] = $subnet_type['id'];



    // Find the VLAN ID from $options[vlan] and $options[campus]
    if ($options['vlan'] or $options['campus']) {
        list($status, $rows, $vlan) = ona_find_vlan($options['vlan'], $options['campus']);
        if ($status or $rows != 1) {
            $self['error'] = "ERROR => The vlan/campus pair specified is invalid!";
            return(array(11, $self['error'] . "\n"));
        }
        printmsg("VLAN selected: {$vlan['name']} in {$vlan['vlan_campus_name']} campus", 1);
        $SET['vlan_id'] = $vlan['id'];
    }

    // Sanitize "name" option
    // We require subnet names to be in upper case and spaces are converted to -'s.
    $options['name'] = trim($options['name']);
    $options['name'] = preg_replace('/\s+/', '-', $options['name']);
    $options['name'] = strtoupper($options['name']);
    // Make sure there's not another subnet with this name
    list($status, $rows, $tmp) = ona_get_subnet_record(array('name' => $options['name']));
    if ($status or $rows) {
        $self['error'] = "ERROR => That name is already used by another subnet!";
        return(array(12, $self['error'] . "\n"));
    }
    $SET['name'] = $options['name'];

    // Check permissions
    if (!auth('subnet_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(14, $self['error'] . "\n"));
    }

    // Get the next ID for the new interface
    $id = ona_get_next_id('subnets');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        return(array(15, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new subnet: " . $id, 1);
    $SET['id'] = $id;

    // Insert the new subnet  record
    list($status, $rows) = db_insert_record(
       $onadb,
       'subnets',
       $SET
    );

    // Report errors
    if ($status or !$rows)
        return(array(16, $self['error'] . "\n"));

    // Return the success notice
    $self['error'] = "INFO => Subnet ADDED: {$ip1}/{$cidr}";
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));
}









///////////////////////////////////////////////////////////////////////
//  Function: subnet_modify (string $options='')
//
//  Description:
//    Modify an existing subnet.
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
//  Example: list($status, $result) = subnet_modify('');
///////////////////////////////////////////////////////////////////////
function subnet_modify($options="") {
    global $conf, $self, $onadb;
    //printmsg('DEBUG => subnet_modify('.implode (";",$options).') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.09';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
        !$options['subnet'] or
        !($options['set_ip'] or
          $options['set_netmask'] or
          $options['set_type'] or
          $options['set_name'] or
          array_key_exists('set_vlan', $options) or
          $options['set_security_level'])
       ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

subnet_modify-v{$version}
Modify a subnet (subnet) record

  Synopsis: subnet_modify [KEY=VALUE] ...

  Where:
    subnet=[ID|IP]           select subnet by search string

  Update:
    set_ip=IP                 change subnet "subnet" address
    set_netmask=MASK          change subnet netmask
    set_name=TEXT      change subnet name (i.e. "LAN-1234")
    set_type=TYPE             change subnet type by name or id
    set_vlan=VLAN             change vlan by name, number
    campus=CAMPUS             vlan campus name or id to help identify vlan
    set_security_level=LEVEL  numeric security level ({$conf['ona_lvl']})

\n
EOM
        ));
    }

    $check_boundaries = 0;

    // Find the subnet record we're modifying
    list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
    if ($status or !$rows) {
        $self['error'] = "ERROR => Subnet not found";
        return(array(2, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('subnet_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(3, $self['error'] . "\n"));
    }

    // Validate the ip address
    if (!$options['set_ip']) {
        $options['set_ip'] = $subnet['ip_addr'];
    }
    else {
        $check_boundaries = 1;
        $options['set_ip'] = $setip = ip_mangle($options['set_ip'], 'numeric');
        // FIXME: what if ip_mangle returns a GMP object?
            if ($options['set_ip'] == -1) {
            $self['error'] = "ERROR => The IP address specified is invalid!";
            return(array(4, $self['error'] . "\n"));
        }
    }

    // Validate the netmask is okay
    if (!$options['set_netmask']) {
        $options['set_netmask'] = $subnet['ip_mask'];
        $cidr = ip_mangle($options['set_netmask'], 'cidr');
    }
    else {
        $check_boundaries = 1;
        $cidr = ip_mangle($options['set_netmask'], 'cidr');
        // FIXME: what if ip_mangle returns a GMP object?
        $options['set_netmask'] = ip_mangle($options['set_netmask'], 'numeric');
        if ($cidr == -1 or $options['set_netmask'] == -1) {
            $self['error'] = "ERROR => The netmask specified is invalid!";
            return(array(5, $self['error'] . "\n"));
        }
    }

    if(is_ipv4($setip))  {
       $padding = 32;
       $fmt = 'dotted';
       $ip1 = ip_mangle($setip, 'binary');
       $num_hosts = 0xffffffff - $options['set_netmask'];
       $first_host=$options['set_ip'] + 1;
       $last_host = ($options['set_ip'] + $num_hosts);
       $str_last_host=$last_host;
       $last_last_host=$last_host -1;
    } else {
       $padding = 128;
       $fmt = 'ipv6gz';
       $ip1 = ip_mangle($setip, 'bin128');
       $first_host=gmp_strval(gmp_add($options['set_ip'] , 1));
       $sub = gmp_sub("340282366920938463463374607431768211455", $options['set_netmask']);
       $last_host = gmp_add($options['set_ip'] , $sub);
       $str_last_host=gmp_strval($last_host);
       $last_last_host=gmp_strval(gmp_sub($last_host ,1));
    }

    // Validate that the subnet IP & netmask combo are valid together.
    $ip2 = str_pad(substr($ip1, 0, $cidr), $padding, '0');
    $ip1 = ip_mangle($ip1, $fmt);
    $ip2 = ip_mangle($ip2, $fmt);
    if ($ip1 != $ip2) {
        $self['error'] = "ERROR => Invalid subnet specified - did you mean: {$ip2}/{$cidr}?";
        return(array(6, $self['error'] . "\n"));
    }

    // If our IP or netmask changed we need to make sure that
    // we won't abandon any host interfaces.
    // We also need to verify that the new boundaries are valid and
    // don't interefere with any other subnets.
    if ($check_boundaries == 1) {

        // *** Check to see if the new subnet overlaps any existing ONA subnets *** //
        // I convert the IP address to dotted format when calling ona_find_subnet()
        // because it saves it from doing a few unnecessary sql queries.

        // Look for overlaps like this (where new subnet address starts inside an existing subnet):
        //            [ -- new subnet -- ]
        //    [ -- old subnet --]
        list($status, $rows, $record) = ona_find_subnet(ip_mangle($options['set_ip'], 'dotted'));
        if ($rows and $record['id'] != $subnet['id']) {
            $self['error'] = "ERROR => Subnet address conflict! New subnet starts inside an existing subnet.";
            return(array(7, $self['error'] . "\n" .
                            "ERROR  => Conflicting subnet record ID: {$record['id']}\n"));
        }


        // Look for overlaps like this (where the new subnet ends inside an existing subnet):
        //    [ -- new subnet -- ]
        //           [ -- old subnet --]
        // Find last address of our subnet, and see if it's inside of any other subnet:
        list($status, $rows, $record) = ona_find_subnet(ip_mangle($str_last_host, 'dotted'));
        if ($rows and $record['id'] != $subnet['id']) {
            $self['error'] = "ERROR => Subnet address conflict! New subnet ends inside an existing subnet.";
            return(array(8, $self['error'] . "\n" .
                            "ERROR  => Conflicting subnet record ID: {$record['id']}\n"));
        }


        // Look for overlaps like this (where the new subnet entirely overlaps an existing subnet):
        //    [ -------- new subnet --------- ]
        //           [ -- old subnet --]
        //
        // Do a cool SQL query to find all subnets whose start address is >= or <= the
        // new subnet base address.
        $where = "ip_addr >= {$options['set_ip']} AND ip_addr <= {$str_last_host}";
        list($status, $rows, $record) = ona_get_subnet_record($where);
        if ( ($rows > 1) or ($rows == 1 and $record['id'] != $subnet['id']) ) {
            $self['error'] = "ERROR => Subnet address conflict! New subnet would encompass an existing subnet.";
            return(array(9, $self['error'] . "\n" .
                            "ERROR  => Conflicting subnet record ID: {$record['id']}\n"));
        }

        // Look for any hosts that are currently in our subnet that would be
        // abandoned if we were to make the proposed changes.
        // Look for hosts on either side of the new subnet boundaries:
        //            [--- new subnet ---]
        //         *      **   *            *   <-- Hosts: the first and last host would be a problem!
        //       [------- old subnet --------]
        //
        $where1 = "subnet_id = {$subnet['id']} AND ip_addr < {$first_host}";
        $where2 = "subnet_id = {$subnet['id']} AND ip_addr > {$last_last_host}";
        list($status, $rows1, $record) = ona_get_interface_record($where1);
        list($status, $rows2, $record) = ona_get_interface_record($where2);
        if ($rows1 or $rows2) {
            $num = $rows1 + $rows2;
            $self['error'] = "ERROR => Changes would abandon {$num} hosts in an unallocated ip space";
            return(array(10, $self['error'] . "\n"));
        }


        // Look for any dhcp pools that are currently in our subnet that would be
        // abandoned if we were to make the proposed changes.
        // Look for existin pools with start/end values outside of new subnet range
        //            [--- new subnet ---]
        //                      [--cur pool--]
        //       [------- old subnet --------]
        //
        $where1 = "subnet_id = {$subnet['id']} AND ip_addr_start < {$options['set_ip']}";
        $where2 = "subnet_id = {$subnet['id']} AND ip_addr_end > {$str_last_host}";
        list($status, $rows1, $record) = ona_get_dhcp_pool_record($where1);
        list($status, $rows2, $record) = ona_get_dhcp_pool_record($where2);
        if ($rows1 or $rows2) {
            $num = $rows1 + $rows2;
            $self['error'] = "ERROR => Changes would abandon a DHCP pool in an unallocated ip space, adjust pool sizes first";
            return(array(10, $self['error'] . "\n"));
        }

    }

    //
    // Define the fields we're updating
    //
    // This variable will contain the updated info we'll insert into the DB
    $SET = array();
    $SET['ip_addr'] = $options['set_ip'];
    $SET['ip_mask'] = $options['set_netmask'];



    // Set options['set_security_level']?
    // Sanitize "security_level" option
    if (array_key_exists('set_security_level', $options)) {
        $options['set_security_level'] = sanitize_security_level($options['set_security_level']);
        if ($options['set_security_level'] == -1)
            return(array(11, $self['error'] . "\n"));
        $SET['lvl'] = $options['set_security_level'];
    }


    // Set options['set_name']?
    if ($options['set_name']) {
        // BUSINESS RULE: We require subnet names to be in upper case and spaces are converted to -'s.
        $options['set_name'] = trim($options['set_name']);
        $options['set_name'] = preg_replace('/\s+/', '-', $options['set_name']);
        $options['set_name'] = strtoupper($options['set_name']);
        // Make sure there's not another subnet with this name
        list($status, $rows, $tmp) = ona_get_subnet_record(array('name' => $options['set_name']));
        if ($status or $rows > 1 or ($rows == 1 and $tmp['id'] != $subnet['id'])) {
            $self['error'] = "ERROR => That name is already used by another subnet!";
            return(array(12, $self['error'] . "\n"));
        }
        $SET['name'] = $options['set_name'];
    }


    // Set options['set_type']?
    if ($options['set_type']) {
        // Find the type from $options[type]
        list($status, $rows, $subnet_type) = ona_find_subnet_type($options['set_type']);
        if ($status or $rows != 1) {
            $self['error'] = "ERROR => Invalid subnet type specified!";
            return(array(13, $self['error'] . "\n"));
        }
        printmsg("Subnet type selected: {$subnet_type['display_name']} ({$subnet_type['short_name']})", 1);
        $SET['subnet_type_id'] = $subnet_type['id'];
    }


    // Set options['set_vlan']?
    if (array_key_exists('set_vlan', $options) or $options['campus']) {
        if (!$options['set_vlan'])
            $SET['vlan_id'] = 0;
        else {
            // Find the VLAN ID from $options[set_vlan] and $options[campus]
            list($status, $rows, $vlan) = ona_find_vlan($options['set_vlan'], $options['campus']);
            if ($status or $rows != 1) {
                $self['error'] = "ERROR => The vlan/campus pair specified is invalid!";
                return(array(15, $self['error'] . "\n"));
            }
            printmsg("VLAN selected: {$vlan['name']} in {$vlan['vlan_campus_name']} campus", 1);
            $SET['vlan_id'] = $vlan['id'];
        }
    }


    // Update the subnet record
    list($status, $rows) = db_update_record($onadb, 'subnets', array('id' => $subnet['id']), $SET);
    if ($status or !$rows)
        return(array(16, $self['error'] . "\n"));

    // Load the updated record for display
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $subnet['id']));

    // Return the (human-readable) success notice
    $text = format_array($SET);
    $self['error'] = "INFO => Subnet UPDATED";
    return(array(0, $self['error'] . ":\n{$text}\n"));
}









///////////////////////////////////////////////////////////////////////
//  Function: subnet_del (string $options='')
//
//  Description:
//    Delete an existing subnet.
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
//  Example: list($status, $result) = subnet_del('host=test');
///////////////////////////////////////////////////////////////////////
function subnet_del($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.06';

    printmsg('DEBUG => subnet_del('.$options.') called', 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['subnet'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

subnet_del-v{$version}
Deletes a subnet (subnet) from the database

  Synopsis: subnet_del [KEY=VALUE] ...

  Required:
    subnet=IP or ID              select subnet by search string

  Optional:
    commit=[yes|no]               commit db transaction (no)
\n
EOM
        ));
    }


    // Find the subnet record we're deleting
    list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
    if ($status or !$rows) {
        $self['error'] = "ERROR => Subnet not found";
        return(array(2, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('subnet_del') or !authlvl($subnet['lvl'])) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(3, $self['error'] . "\n"));
    }


    // If "commit" is yes, delete the subnet
    if ($options['commit'] == 'Y') {
        $text = "";

        // FIXME: (add all this) ... 
        // SUMMARY:
        //   Delete assignments to any DHCP servers
        //   Delete any DHCP pools on the current subnet
        //   Delete any DHCP options associated with this subnet
        //   Delete any interfaces belonging to hosts with more than one interface
        //   Delete any hosts (and all their associated info) that have only one interface
        //   Delete subnet Record
        //   Delete custom attributes
        //
        //   FIXME: display a warning if there are no more subnets that a dhcp server is serving dhcp for?

        // Delete DHCP server assignments
        list($status, $rows) = db_delete_records($onadb, 'dhcp_server_subnets', array('subnet_id' => $subnet['id']));
        if ($status) {
            $self['error'] = "ERROR => DHCP server assignment delete failed: {$self['error']}";
            return(array(5, $self['error'] . "\n"));
        }

        // Delete DHCP pools
        list($status, $rows) = db_delete_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));
        if ($status) {
            $self['error'] = "ERROR => DHCP pool delete failed: {$self['error']}";
            return(array(5, $self['error'] . "\n"));
        }

        // Delete DHCP options
        list($status, $rows) = db_delete_records($onadb, 'dhcp_option_entries', array('subnet_id' => $subnet['id']));
        if ($status) {
            $self['error'] = "ERROR => DHCP parameter delete failed: {$self['error']}";
            return(array(5, $self['error'] . "\n"));
        }

        // Delete tag entries
        list($status, $rows, $records) = db_get_records($onadb, 'tags', array('type' => 'subnet', 'reference' => $subnet['id']));
        $log=array(); $i=0;
        foreach ($records as $record) {
            $log[$i]= "INFO => Tag DELETED: {$record['name']} from {$subnet['name']}";
            $i++;
        }
        //do the delete
        list($status, $rows) = db_delete_records($onadb, 'tags', array('type' => 'subnet', 'reference' => $subnet['id']));
        if ($status) {
            $self['error'] = "ERROR => subnet_del() Tag delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }
        //log deletions
        foreach($log as $log_msg) {
            printmsg($log_msg,0);
            $add_to_error .= $log_msg . "\n";
        }

        // Delete custom attribute entries
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'custom_attributes', array('table_name_ref' => 'subnets', 'table_id_ref' => $subnet['id']));
        $log=array(); $i=0;
        foreach ($records as $record) {
            list($status, $rows, $ca) = ona_get_custom_attribute_record(array('id' => $record['id']));
            $log[$i]= "INFO => Custom Attribute DELETED: {$ca['name']} ({$ca['value']}) from {$subnet['name']}";
            $i++;
        }

        //do the delete
        list($status, $rows) = db_delete_records($onadb, 'custom_attributes', array('table_name_ref' => 'subnets', 'table_id_ref' => $subnet['id']));
        if ($status) {
            $self['error'] = "ERROR => subnet_del() Custom attribute delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }

        //log deletions
        foreach($log as $log_msg) {
            printmsg($log_msg,0);
            //$add_to_error .= $log_msg . "\n";
        }



        // Delete associated host / interface records that need to be deleted
        // BUSINESS RULE: We delete hosts that have only one interface (and it's on this subnet)
        // BUSINESS RULE: We delete interfaces from hosts that have multiple interfaces
        list($status, $rows, $interfaces) = db_get_records($onadb, 'interfaces', array('subnet_id' => $subnet['id']));
        $hosts_to_delete = array();
        $interfaces_to_delete = array();
        foreach ($interfaces as $interface) {
            // Select all  interfaces for the associated host where the subnet ID is not our subnet ID
            $where = "host_id = {$interface['host_id']} AND subnet_id != {$subnet['id']}";
            list($status, $rows, $tmp) = db_get_records($onadb, 'interfaces', $where, '', 0);
            // We'll delete hosts that have only one interface (i.e. no interfaces on any other subnets)
            if ($rows == 0)
                array_push($hosts_to_delete, $interface['host_id']);
            // Otherwise .. we delete this interface since it belongs to a host with interfaces on other subnets
            else
                array_push($interfaces_to_delete, $interface['id']);
        }
        unset($interfaces);

        // make sure we only have one reference for each host and interface
        $interfaces_to_delete = array_unique($interfaces_to_delete);
        $hosts_to_delete = array_unique($hosts_to_delete);

        // Delete interfaces we have selected
        foreach ($interfaces_to_delete as $interface_id) {
            list($status, $output) = run_module('interface_del', array('interface' => $interface_id, 'commit' => 'Y'));
            if ($status) return(array(5, $output));
        }

        // Delete hosts we have selected
        foreach ($hosts_to_delete as $host_id) {
            list($status, $output) = run_module('host_del', array('host' => $host_id, 'commit' => 'Y'));
            if ($status) return(array(5, $output));
        }

        // Delete the subnet
        list($status, $rows) = db_delete_records($onadb, 'subnets', array('id' => $subnet['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => Subnet delete failed: {$self['error']}";
            return(array(5, $self['error'] . "\n"));
        }

        // Return the success notice
        $ip = ip_mangle($subnet['ip_addr'], 'dotted');
        $cidr = ip_mangle($subnet['ip_mask'], 'cidr');
        $self['error'] = "INFO => Subnet DELETED: {$subnet['name']} IP: {$ip}/{$cidr}";
        printmsg($self['error'], 0);
        return(array(0, $self['error'] . "\n"));
    }


    //
    // We are just displaying records that would have been deleted
    //

    // SUMMARY:
    //   Display assignments to any DHCP servers
    //   Display any DHCP pools on the current subnet
    //   Display any DHCP parameters associated with this subnet
    //   Display subnet Record
    //   Display Host records (and all their sub-records)
    //   Display custom attributes 


    // Otherwise just display the host record for the host we would have deleted
    $text = "Record(s) NOT DELETED (see \"commit\" option)\n" .
            "Displaying record(s) that would have been deleted:\n";

    // Display the Subnet's complete record
    list($status, $tmp) = subnet_display("subnet={$subnet['id']}&verbose=N");
    $text .= "\n" . $tmp;



    // Display assignments to any DHCP servers
    list($status, $rows, $records) = db_get_records($onadb, 'dhcp_server_subnets', array('subnet_id' => $subnet['id']));
    if ($rows) $text .= "\nASSOCIATED DHCP SERVER ASSIGNMENT RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        $text .= format_array($record);
    }

    // Display any DHCP pools on the current subnet
    list($status, $rows, $records) = db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));
    if ($rows) $text .= "\nASSOCIATED DHCP POOL RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        $text .= format_array($record);
    }

    // Display associated DHCP entries
    list($status, $rows, $records) = db_get_records($onadb, 'dhcp_option_entries', array('subnet_id' => $subnet['id']));
    if ($rows) $text .= "\nASSOCIATED DHCP ENTRY RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $dhcp) = ona_get_dhcp_option_entry_record(array('id' => $record['id']));
        $text .= "  {$dhcp['display_name']} => {$dhcp['value']}\n";
    }

    // Display associated tags
    list($status, $rows, $records) = db_get_records($onadb, 'tags', array('type' => 'subnet', 'reference' => $subnet['id']));
    if ($rows) $text .= "\nASSOCIATED TAG RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        $text .= "  {$record['name']}\n";
    }

    // Display associated custom attributes
    list($status, $rows, $records) = db_get_records($onadb, 'custom_attributes', array('table_name_ref' => 'subnets', 'table_id_ref' => $subnet['id']));
    if ($rows) $text .= "\nASSOCIATED CUSTOM ATTRIBUTE RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $ca) = ona_get_custom_attribute_record(array('id' => $record['id']));
        $text .= "  {$ca['name']} => {$ca['value']}\n";
    }

    // Display associated host  / interface records that would be deleted
    // BUSINESS RULE: We delete hosts that have only one interface (and it's on this subnet)
    // BUSINESS RULE: We delete interfaces from hosts that have multiple interfaces (including at least one on a different subnet)
    list($status, $rows, $interfaces) = db_get_records($onadb, 'interfaces', array('subnet_id' => $subnet['id']));
    $hosts_to_delete = array();
    $interfaces_to_delete = array();
    foreach ($interfaces as $interface) {
        // Select all  interfaces for the associated host where the subnet ID is not our subnet ID
        $where = "host_id = {$interface['host_id']} AND subnet_id != {$subnet['id']}";
        list($status, $rows, $tmp) = db_get_records($onadb, 'interfaces', $where, '', 0);
        // We'll delete hosts that have only one interface (i.e. no interfaces on any other subnets)
        if ($rows == 0)
            array_push($hosts_to_delete, $interface['host_id']);
        // Otherwise .. we delete this interface since it belongs to a host with interfaces on other subnets
        else
            array_push($interfaces_to_delete, $interface['id']);
    }
    unset($interfaces);

    // make sure we only have one reference for each host and interface
    $interfaces_to_delete = array_unique($interfaces_to_delete);
    $hosts_to_delete = array_unique($hosts_to_delete);

    // Display interfaces we would have deleted
    $rows = count($interfaces_to_delete);
    if ($rows) $text .= "\n----- ASSOCIATED HOST INTERFACE RECORDS ({$rows}) -----\n";
    foreach ($interfaces_to_delete as $interface_id) {
        list($status, $output) = run_module('interface_del', array('interface' => $interface_id), false);
        $output = preg_replace('/^(.*)?\n(.*)?\n/', '', $output);
        $text .= $output;
    }

    // Display hosts we would have deleted
    $rows = count($hosts_to_delete);
    if ($rows) $text .= "\n-----ASSOCIATED HOSTS ({$rows}) -----\n";
    foreach ($hosts_to_delete as $host_id) {
        list($status, $output) = run_module('host_del', array('host' => $host_id), false);
        $output = preg_replace('/^(.*)?\n(.*)?\n/', '', $output);
        $text .= $output;
    }

    return(array(7, $text));
}










///////////////////////////////////////////////////////////////////////
//  Function: subnet_nextip (string $options='')
//
//  Description:
//    Return the next available IP address on a subnet.  Optionally
//    start the search from a starting offset.
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
//  Example: list($status, $result) = subnet_nextip('subnet=test');
///////////////////////////////////////////////////////////////////////
function subnet_nextip($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg('DEBUG => subnet_del('.$options.') called', 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['subnet'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

subnet_del-v{$version}
Return the next available IP address on a subnet.

  Synopsis: subnet_nextip [KEY=VALUE] ...

  Required:
    subnet=IP or ID               select subnet by search string

  Optional:
    offset=NUMBER                 Starting offset to find next available IP
    output=[dotted|numeric]       Return the number as a dotted or numeric value
                                  DEFAULT: numeric
\n
EOM
        ));
    }


    // Find the subnet record we're deleting
    list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
    if ($status or !$rows) {
        $self['error'] = "ERROR => Subnet not found";
        return(array(2, $self['error'] . "\n"));
    }

    // Create a few variables that will be handy later
    $num_ips = 0xffffffff - $subnet['ip_mask'];
    $last_ip = ($subnet['ip_addr'] + $num_ips) - 1;

    // check that offset is a number
    if (isset($options['offset']) and !is_numeric($options['offset'])) {
        $self['error'] = "ERROR => Offset must be a numeric number";
        return(array(3, $self['error'] . "\n"));
    } else {
        $offsetmsg = " beyond offset {$options['offset']}";
    }

    // make sure the offset does not extend beyond the specified subnet
    if ($options['offset'] >= $num_ips - 1) {
        $self['error'] = "ERROR => Offset extends beyond specified subnet boundary";
        return(array(4, $self['error'] . "\n"));
    }

    if (!isset($options['output'])) {
        $options['output'] = '1';
    }

    // check output option is dotted or numeric
    else if ($options['output'] != 'dotted' && $options['output'] != 'numeric') {
        $self['error'] = "ERROR => Output option must be 'dotted' or 'numeric'";
        return(array(5, $self['error'] . "\n"));
    }

    // Find the first number based on our subnet and offset
    $ip = $subnet['ip_addr'] + $options['offset'];

    // Make sure we skip past the subnet IP to the first usable IP
    if ($ip == $subnet['ip_addr']) $ip++;

    // Start looping through our IP addresses until we find an available one
    while ($ip <= $last_ip) {
        // Find out if the ip is used in an interface
        list($status, $rows, $interfaces) = db_get_records($onadb, 'interfaces', array('ip_addr' => $ip));

        // If we find a free address.. check that it is not in a DHCP pool
        if (!$rows) {
            list($status, $rows, $pool) = db_get_record($onadb, 'dhcp_pools', "{$ip} >= ip_addr_start AND {$ip} <= ip_addr_end");
            if ($rows) $ip = $pool['ip_addr_end'];
                else break;
        }
        $ip++;  // increment by one and check again
    }

    // If we checked all the IPs, make sure we are not on the broadcast IP of the subnet
    if ($ip == $last_ip + 1) {
        $self['error'] = "ERROR => No available IP addresses found on subnet{$offsetmsg}";
        return(array(5, $self['error'] . "\n"));
    }

    // return the IP
    return(array(0, ip_mangle($ip,$options['output'])."\n"));


}







?>

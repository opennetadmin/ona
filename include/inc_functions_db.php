<?php
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have ADODB and database settings loaded
require_once($conf['inc_db']);

// Make sure we're connected to the onadb DB
//require_once($conf['inc_db_onadb']);













///////////////////////////////////////////////////////////////////////
//  Function: format_array($array)
//
//  Takes an array and returns a formatted string of the contents
//  of the array for display. Usually used in the ona_xxx_display()
//  functions to display database records.
//
//  Example:
//      $string = format_array($array)
///////////////////////////////////////////////////////////////////////
function format_array($array=array()) {

    $text = '';
    foreach (array_keys($array) as $key) {

        // Make some data look pretty
        if      ($key == 'IP_ADDR')        { $array[$key] = ip_mangle($array[$key]); }
        else if ($key == 'IP_ADDRESS_START')  { $array[$key] = ip_mangle($array[$key]); }
        else if ($key == 'IP_ADDRESS_END')    { $array[$key] = ip_mangle($array[$key]); }
        else if ($key == 'IP_MASK')    { $array[$key] = ip_mangle($array[$key]); }
        else if ($key == 'DATA_LINK_ADDRESS') { $array[$key] = mac_mangle($array[$key]); if ($array[$key] == -1) $array[$key] = ''; }
        else if ($key == 'HOST_ID')           {
            list($host, $zone) = ona_find_host($array[$key]);
            if ($host['ID'])
                $array[$key] = str_pad($array[$key], 20) . strtolower("({$host['FQDN']})");
        }
        else if ($key == 'SERVER_ID')         {
            list($status, $rows, $server) = ona_get_server_record(array('ID' => $array[$key]));
            list($host, $host) = ona_find_host($server['HOST_ID']);
            if ($host['ID'])
                $array[$key] = str_pad($array[$key], 20) . strtolower("({$host['FQDN']})");
        }
        else if ($key == 'subnet_id')        {
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $array[$key]));
            if ($subnet['id'])
                $array[$key] = str_pad($array[$key], 20) . strtoupper("({$subnet['name']})");
        }
        else if ($key == 'DNS_ZONE_ID' or $key == 'PRIMARY_DNS_ZONE_ID') {
            list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $array[$key]));
            $array[$key] = str_pad($array[$key], 20) . strtolower("({$zone['ZONE_NAME']})");
        }

        // Align columns
        if ($array[$key]) { $text .= str_pad("  {$key}", 30) . $array[$key] . "\n"; }
    }

    // Return a nice string :)
    return($text);
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_insert_record(string $table, array $insert)
//
//  See documentation for db_insert_record() in inc_db.php
///////////////////////////////////////////////////////////////////////
function ona_insert_record($table="", $insert="") {
    global $onadb;
    return(db_insert_record($onadb, $table, $insert));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_update_record(string $table, array/string $where, array $insert)
//
//  See documentation for db_update_record() in inc_db.php
///////////////////////////////////////////////////////////////////////
function ona_update_record($table="", $where="", $insert="") {
    global $onadb;
    return(db_update_record($onadb, $table, $where, $insert));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_delete_record(string $table, array/string $where)
//
//  See documentation for db_delete_record() in inc_db.php
///////////////////////////////////////////////////////////////////////
function ona_delete_record($table="", $where="") {
    global $onadb;
    return(db_delete_record($onadb, $table, $where));
}



///////////////////////////////////////////////////////////////////////
//  Function: ona_get_record(array/string $where, string $table, string $order)
//
//  See documentation for db_get_record() in inc_db.php
///////////////////////////////////////////////////////////////////////
function ona_get_record($where="", $table="", $order="") {
    global $onadb;
    return(db_get_record($onadb, $table, $where, $order));
}











///////////////////////////////////////////////////////////////////////
//
//         THIS IS DOCUMENTATION FOR ALL OF THE FOLLOWING
//                ona_get_XXX_record FUNCTIONS
//
//  Function: ona_get_XXXXX_record(array $where)
//
//  Input:
//    $where is an associative array of KEY = VALUE pair(s) used to
//    locate and return the host record you want.
//    This puts you a little close to the actual DB, but it allows
//    a great amount of flexability.
//    Example input: ona_get_host_record(array('ID' => '12354'))
//
//  Output:
//    Returns a three part list:
//      1. Function exit status - 0 on success, non-zero on error.
//         When a non-zero exit status is returned a description of the
//         error will be stored in the global variable $self['error']
//      2. The number of rows ('n') that match values in $where, or
//         0 on no matches.
//      3. An associative array of a record from the DB table where the
//         values in $where match.  When more than one record is
//         returned from the DB, the first record is returned on the
//         first call.  Each subsequent call with the same parameters
//         will cause the function to return the next record.  When 'n'
//         records are found, and the function is called 'n+1' times,
//         it loops and the first record is returned again.
//
//  Example: list($status, $rows, $record) = ona_get_host_record(array('ID' => '12354'));
//
///////////////////////////////////////////////////////////////////////

function ona_get_host_record($array='', $order='') {
    return(ona_get_record($array, 'hosts', $order));
}

function ona_get_alias_record($array='', $order='') {
    return(ona_get_record($array, 'HOST_ALIASES_B', $order));
}

function ona_get_dns_domain_record($array='', $order='') {
    return(ona_get_record($array, 'dns_domains', $order));
}

function ona_get_zone_server_record($array='', $order='') {
    return(ona_get_record($array, 'ZONE_SERVERS_B', $order));
}

function ona_get_block_record($array='', $order='') {
    return(ona_get_record($array, 'blocks', $order));
}

function ona_get_location_record($array='', $order='') {
    return(ona_get_record($array, 'locations', $order));
}

function ona_get_interface_record($array='', $order='') {
    return(ona_get_record($array, 'interfaces', $order));
}

function ona_get_dns_a_record($array='', $order='') {
    return(ona_get_record($array, 'dns_a', $order));
}

function ona_get_config_record($array='', $order='ctime DESC') {
    list($status, $rows, $record) = ona_get_record($array, 'configurations', $order);

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_config_type_record(array('id' => $record['configuration_type_id']));
    $status += $status_tmp;
    $record['config_type_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_config_type_record($array='', $order='') {
    return(ona_get_record($array, 'configuration_types', $order));
}

function ona_get_server_record($array) {
    return(ona_get_record($array, 'SERVER_B'));
}

function ona_get_dhcp_failover_group_record($array) {
    return(ona_get_record($array, 'IP.DHCP_FAILOVER_GROUP_B'));
}

function ona_get_infobit_type_record($array) {
    return(ona_get_record($array, 'INFOBIT_TYPES_B'));
}

function ona_get_infobit_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'INFOBITS_B');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_infobit_type_record(array('ID' => $record['INFOBIT_TYPE_ID']));
    $status += $status_tmp;
    $record['NAME'] = $record_tmp['NAME'];

    return(array($status, $rows, $record));
}

function ona_get_host_infobit_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'HOST_INFOBITS_B');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_infobit_record(array('ID' => $record['INFOBIT_ID']));
    $status += $status_tmp;
    $record['VALUE'] = $record_tmp['VALUE'];
    $record['NAME']  = $record_tmp['NAME'];

    return(array($status, $rows, $record));
}

function ona_get_model_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'models');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_manufacturer_record(
                                                    array('ID' => $record['manufacturer_id'])
                                                );
    $status += $status_tmp;
    $record['MANUFACTURER_NAME'] = $record_tmp['name'];

    // Lets be nice and return a little associated info
  //  list($status_tmp, $rows_tmp, $record_tmp) = ona_get_device_type_record(
    //                                                array('ID' => $record['DEVICE_TYPE_ID'])
      //                                          );
   // $status += $status_tmp;
   // $record['DEVICE_TYPE_DESCRIPTION'] = $record_tmp['DEVICE_TYPE_DESCRIPTION'];

    return(array($status, $rows, $record));
}

function ona_get_manufacturer_record($array) {
    return(ona_get_record($array, 'manufacturers'));
}

function ona_get_device_type_record($array) {
    return(ona_get_record($array, 'DEVICE_TYPES_B'));
}

function ona_get_subnet_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'subnets');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
    $status += $status_tmp;
    $record['subnet_type_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_subnet_type_record($array) {
    return(ona_get_record($array, 'subnet_types'));
}

function ona_get_vlan_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'vlans');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_vlan_campus_record(
                                                    array('id' => $record['vlan_campus_id'])
                                                );
    $status += $status_tmp;
    $record['vlan_campus_name'] = $record_tmp['name'];

    return(array($status, $rows, $record));
}

function ona_get_vlan_campus_record($array) {
    return(ona_get_record($array, 'vlan_campuses'));
}

function ona_get_dhcp_parm_type_record($array) {
    return(ona_get_record($array, 'DHCP_PARAMETER_TYPE_B'));
}

function ona_get_dhcp_entry_record($array) {
    list($status, $rows, $record) = ona_get_record($array, 'DHCP_ENTRY_B');

    // Lets be nice and return a little associated info
    list($status_tmp, $rows_tmp, $record_tmp) = ona_get_dhcp_parm_type_record(array('ID' => $record['DHCP_PARAMETER_TYPE_ID']));
    $status += $status_tmp;
    $record['DHCP_NUMBER'] = $record_tmp['DHCP_NUMBER'];
    $record['DHCP_TAG'] = $record_tmp['DHCP_TAG'];
    $record['DHCP_DESCRIPTION'] = $record_tmp['DHCP_DESCRIPTION'];
    $record['TAG_TYPE'] = $record_tmp['TAG_TYPE'];

    return(array($status, $rows, $record));
}

function ona_get_dhcp_pool_record($array) {
    return(ona_get_record($array, 'DHCP_POOL_B'));
}

function ona_get_dhcp_server_subnet_record($array) {
    return(ona_get_record($array, 'DHCP_SERVER_NETWORKS_B'));
}








///////////////////////////////////////////////////////////////////////
//  Function: ona_get_configtype_deref($id or $string)
//
//  Translates a config type NAME to an ID, or vice versa.
//  Returns 0 on error.
//
///////////////////////////////////////////////////////////////////////
function ona_get_configtype_deref($search='') {
    global $onadb;
    global $self;

    // Debugging
    printmsg("DEBUG => ona_get_configtype_deref($search) called", 3);

    // Return 0 if there was no input
    if (!$search) { return(0); }

    // If $q is numeric
    if (preg_match('/^\d+$/', $search)) {
        // Select the type name
        $q = 'SELECT *
              FROM IP.CONFIG_TYPE_B
              WHERE IP.CONFIG_TYPE_B.CONFIG_TYPE_ID=' . $onadb->qstr($search);
        $rs = $onadb->Execute($q);
        if ($rs === false) {
            printmsg('ERROR => SQL query failed: ' . $onadb->ErrorMsg(), 3);
            return(0);
        }
        if ($rs->RecordCount() >= 1) {
            $row = $rs->FetchRow();
            return($row['CONFIG_TYPE_NAME']);
        }
    }

    // Otherwise lookup ID by NAME
    else {
        // Select the type name
        $q = 'SELECT *
              FROM IP.CONFIG_TYPE_B
              WHERE IP.CONFIG_TYPE_B.CONFIG_TYPE_NAME=' . $onadb->qstr($search);
        $rs = $onadb->Execute($q);
        if ($rs === false) {
            printmsg('ERROR => SQL query failed: ' . $onadb->ErrorMsg(), 3);
            return(0);
        }
        if ($rs->RecordCount() >= 1) {
            $row = $rs->FetchRow();
            return($row['CONFIG_TYPE_ID']);
        }
    }

    // Just in case
    return(0);
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_get_next_id($tablename)
//
//  Returns the next ID for the specified table.  It was decided
//  to create this function instead of using the ADODB GenID() function.
//  We didnt want to have sequence tables cluttering the schema so a single
//  table was created that stored the ID and the tablename it is for.
//
//  Example:
//      $id = ona_get_next_id($tablename);
///////////////////////////////////////////////////////////////////////
function ona_get_next_id($tablename) {
    global $onadb, $self;

    // Debugging
    printmsg("DEBUG => ona_get_next_id() called", 3);



    // Find the sequence value for the specified tablename
    list($status, $rows, $record) = db_get_record($onadb, 'sequences', array('name' => $tablename));


    // Init a new sequence when the tablename is not found
    if (!$rows) {
        list($status, $rows) = db_insert_record($onadb, 'sequences', array('name' => $tablename, 'seq' => 1));
        return(1);
    }
    else {
        // if we did find something increment the sequence in the table
        $seq_inc = $record['seq'] + 1;
        list($status, $rows) = db_update_record($onadb, 'sequences', array('name' => $tablename), array('seq' => $seq_inc));

        if ($status) {
            $self['error'] = 'ERROR => ona_get_next_id() Unable to update sequence value!';
            printmsg($self['error'], 4);
            return(0);
        }

        // If we got an ID, return it.
        if ($record['seq'] > 0) {
            printmsg("DEBUG => ona_get_next_id() Returning ID: " . $record['seq'], 4);
            return($record['seq']);
        }
        // Just in case...
        else {
            $self['error'] = 'ERROR => ona_get_next_id() Something went wrong!';
            printmsg($self['error'], 4);
            return(0);
        }
    }
}









///////////////////////////////////////////////////////////////////////
//  Function: ona_find_host (string $search)
//
//  Input:
//    $search = An FQDN, host ID, IP address (or other unique interface
//              identifier), or any substring that can uniquly identify
//              a host record.
//
//  Output:
//    Returns a two part array: list($host_record, $zone_record);
//
//  Description:
//    If $search is not an FQDN:
//      The requested host record is identified and the associated
//      host and domain (zone) records are returned.
//    If $search is an FQDN:
//      Looks at $fqdn, determines which part of it (if any) is the
//      domain name and which part is the hostname.  Then searches the
//      database for matching hostname and domain name records and
//      returns them.
//      * In the event that the FQDN does not contain a valid hostname
//        or alias name, a fake host record is returned with the
//        "PRIMARY_DNS_NAME" key set to the original host portion of FQDN.
//      * In the event that the FQDN specified is an alias record, the
//        associated host record, and that host record's domain are returned.
//      * In the event that a valid, existing, domain can not be found in
//        the FQDN, the domain "something.com" will be returned.
//        I.E. A valid domain (zone) record will always be returned.
//
//  Example:  list($host, $zone) = ona_find_host('myhost.domain.com');
///////////////////////////////////////////////////////////////////////
function ona_find_host($search="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => ona_find_host({$search}) called", 3);

    // By record ID?
    if (is_numeric($search)) {
        list($status, $rows, $host) = ona_get_host_record(array('ID' => $search));
        if (!$rows)
            list($status, $rows, $record) = ona_get_alias_record(array('ID' => $search));
        if (!$rows)
            list($status, $rows, $record) = db_get_record($onadb, 'SERVER_B', array('ID' => $search));
        if (!$rows)
            list($status, $rows, $record) = db_get_record($onadb, 'HOST_INFOBITS_B', array('ID' => $search));
        if (!$rows)
            list($status, $rows, $record) = db_get_record($onadb, 'configurations', array('ID' => $search));

        if ($rows) {
            if (!$host['id']) {
                list($status, $rows, $host) = ona_get_host_record(array('ID' => $record['host_id']));
            }
            list($status, $rows, $zone) = ona_get_dns_domain_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));

            // This is just for convenience :)
            $host['FQDN'] = $host['PRIMARY_DNS_NAME'] . '.' . $zone['ZONE_NAME'];

            printmsg("DEBUG => ona_find_host({$search}) called, found: {$host['FQDN']}", 3);

            return(array($host, $zone));
        }
    }

    // By Interface?
    list($status, $rows, $interface) = ona_find_interface($search);
    if (!$status and $rows) {
        // Load and return associated info
        list($status, $rows, $host) = ona_get_host_record(array('ID' => $interface['host_id']));
        list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));

        // This is just for convenience :)
        $host['FQDN'] = $host['PRIMARY_DNS_NAME'] . '.' . $zone['ZONE_NAME'];

        return(array($host, $zone));
    }

    //
    // It's an FQDN, do a bunch of stuff!
    //
    $hostname = $zone_name = strtolower($search);
    $zone = array();

    // Find the domain name piece of $search
    // Strip off everything up to the first period.
    while ($zone_name = strstr($zone_name, '.')) {
        // Remove the . from the start of the string
        $zone_name = substr($zone_name, 1);
        printmsg("DEBUG => ona_find_host() Checking for existance of zone: $zone_name", 3);

        // Check to see if $zone_name is valid. If it is, fix the hostname and break;
        list($status, $rows, $zone) = ona_get_zone_record(array('ZONE_NAME' => $zone_name));
        if ($rows) {
            $hostname = str_replace('.' . $zone_name, '', $hostname);
            break;
        }

        // Debugging
        printmsg("DEBUG => ona_find_host() Checking for existance of zone : {$zone_name}.com", 3);

        // Check to see if $hostname has a valid zone_id in the DB. If it is, fix the hostname and zone-name and break;
        list($status, $rows, $zone) = ona_get_zone_record(array('ZONE_NAME' => $zone_name . '.com'));
        if ($rows) {
            $hostname = str_replace('.' . $zone_name, '', $hostname);
            $zone_name = $zone_name . '.com';
            break;
        }
    }

    // If no zone_id yet, use .com
    if (!$zone['ID']) {
        printmsg('DEBUG => ona_find_host() Zone not found, returning zone_id for ".com"', 3);
        list($status, $records, $zone) = ona_get_zone_record(array('ZONE_NAME' => '.com'));
    }

    // Now we need to see if we can locate a valid host or alias record from $hostname
    list($status, $rows, $host) = ona_get_host_record(array('PRIMARY_DNS_NAME' => $hostname, 'PRIMARY_DNS_ZONE_ID' => $zone['ID']));
    if (!$rows) {
       // Check for an alias
       list($status, $rows, $alias) = ona_get_alias_record(array('ALIAS' => $hostname, 'DNS_ZONE_ID' => $zone['ID']));
       // If we got one, we need to load the associated host record
       if ($rows) {
           list($status, $rows, $host) = ona_get_host_record(array('ID' => $alias['HOST_ID']));
       }
    }

    // If we have a valid host record, load it's zone record
    if ($host['ID'])
        list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));

    // Otherwise make a "fake" host record
    else
        $host = array('PRIMARY_DNS_NAME' => $hostname, 'PRIMARY_DNS_ZONE_ID' => $zone['ID']);

    // This is just for convenience :)
    $host['FQDN'] = $host['PRIMARY_DNS_NAME'] . '.' . $zone['ZONE_NAME'];

    // Return
    return(array($host, $zone));
}









///////////////////////////////////////////////////////////////////////
//  Function: ona_find_zone (string $hostname)
//
//  $hostname = The hostname[.domain] you want to find the zone_id,
//              zone_name, and hostname of.
//
//  Looks at $hostname, determines which part of it (if any) is the
//  domain name, determines the real domain name, and returns a three
//  element array of data :
//     ( hostname, zone_name, zone_id )
//  The domain "something.com" is returned if a domain isn't present
//  in the hostname supplied.
//
//  Example:  list($host_name,  $host_zone_name,  $host_zone_id)  = ona_find_zone('myhost.mydomain.com');
///////////////////////////////////////////////////////////////////////
function ona_find_zone($hostname="") {
    $hostname = strtolower($hostname);
    printmsg("DEBUG => ona_find_zone({$hostname}) called", 3);

    // FYI: "zone" means "domain"
    $zone_name = $hostname;
    $zone = array();

    // Find the zone/domain name
    // Strip off everything up to the first period.
    while ($zone_name = strstr($zone_name, '.')) {
        // Remove the . from the start of the string
        $zone_name = substr($zone_name, 1);

        // Debugging
        printmsg("DEBUG => ona_find_zone() Checking for existance of zone : $zone_name", 3);

        // Check to see if $hostname has a vailid zone_id in the DB
        // If it is, exit the while loop.
        list($status, $records, $zone) = ona_get_zone_record(array('ZONE_NAME' => $zone_name));
        if ($status == 0 and $records == 1) {
            // Fix $hostname
            $hostname = str_replace('.' . $zone_name, '', $hostname);
            break;
        }

        // Debugging
        printmsg("DEBUG => ona_find_zone() Checking for existance of zone : {$zone_name}.com", 3);

        // Check to see if $hostname has a valid zone_id in the DB
        // If it is, exit the while loop.
        list($status, $records, $zone) = ona_get_zone_record(array('ZONE_NAME' => $zone_name . '.com'));
        if ($status == 0 and $records == 1) {
            // Fix $hostname
            $hostname = str_replace('.' . $zone_name, '', $hostname);

            // Fix $zone_name
            $zone_name = $zone_name . '.com';

            // Exit the while loop
            break;
        }

    }

    // If no zone_id yet, see if it's a HOST or ALIAS ID
    if (!$zone['ID'] and preg_match('/^\d+$/', $hostname)) {
        list($status, $records, $host) = ona_get_host_record(array('ID' => $hostname));
        if ($status == 0 and $records == 1) {
            $hostname = $host['PRIMARY_DNS_NAME'];
            list($status, $records, $zone) = ona_get_zone_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));
        }
        else {
            list($status, $records, $alias) = ona_get_alias_record(array('ID' => $hostname));
            if ($status == 0 and $records == 1) {
                $hostname = $alias['ALIAS'];
                list($status, $records, $zone) = ona_get_zone_record(array('ID' => $alias['DNS_ZONE_ID']));
            }
        }
    }

    // If no zone_id yet, use .com
    if (!$zone['ID']) {
        printmsg('DEBUG => Zone not found, returning zone_id for ".com"', 3);
        $zone_name = '.com';
        list($status, $records, $zone) = ona_get_zone_record(array('ZONE_NAME' => $zone_name));
    }

    // Return
    return(array($hostname, $zone['ZONE_NAME'], $zone['ID']));
}











///////////////////////////////////////////////////////////////////////
//  Function: ona_find_location(string $search)
//
//  Input:
//    $search = A location ID, location number, name or substring that can
//              uniquly identify a location.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An associative array of a record from the LOCATIONS table
//         where $search matchs.
//
//  Example: list($status, $rows, $location_record) = ona_find_location('fairview');
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or no input
//    2  :: No (unique?) match found
///////////////////////////////////////////////////////////////////////
function ona_find_location($search="") {
    printmsg("DEBUG => ona_find_location({$search}) called", 3);

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // Search for it by Location Reference
        list($status, $rows, $record) = ona_get_location_record(array('reference' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by reference", 2);
            return(array(0, $rows, $record));
        }

        // Search for it by Location ID
        list($status, $rows, $record) = ona_get_location_record(array('id' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by id", 2);
            return(array(0, $rows, $record));
        }

        // Search for it by zip code
        list($status, $rows, $record) = ona_get_location_record(array('zip_code' => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by zip code search", 2);
            return(array(0, $rows, $record));
        }
    }


    // We assume data in the DB is upper cased (most, but not all, of it is)
    // FIXME: do a separate onadb query so we can do case insensitive queries
    $search = strtoupper($search);

    // It's a string - do several sql queries and see if we can get a unique match
    foreach (array('name', 'address', 'city', 'state') as $field) {
        list($status, $rows, $record) = ona_get_location_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_location() found location record by $field search", 2);
            return(array(0, $rows, $record));
        }
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    printmsg("DEBUG => ona_find_location() couldn't find a unique location record with specified search criteria", 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_interface(string $search)
//
//  Input:
//    $search = An interface ID, IP address, or any substring that can
//              uniquly identify an interface.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An associative array of a record from the HOST_NETWORKS_B table
//         where $search matchs.
//
//  Example: list($status, $rows, $location_record) = ona_find_interface('10.44.10.123');
//
//  Exit codes:
//    0  :: No error
//    1  :: Invalid or no input
//    2  :: No (unique?) match found
//    3  :: No such IP address
//    4  :: More than one interface has that IP address
//    5  :: No such MAC address
//    6  :: More than one interface has that MAC address
///////////////////////////////////////////////////////////////////////
function ona_find_interface($search="") {
    printmsg("DEBUG => ona_find_interface({$search}) called", 3);

    // Validate input
    if ($search == "")
        return(array(1, 0, array()));

    // If it's numeric
    if (is_numeric($search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        foreach (array('id', 'host_id', 'ip_addr') as $field) {
            list($status, $rows, $record) = ona_get_interface_record(array($field => $search));
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_interface() found interface record by {$field}", 2);
                return(array(0, $rows, $record));
            }
        }
    }

    // If it's an IP address
    // Save the mac in the format the DB uses (numeric)
    $ip = ip_mangle($search, 1);
    if ($ip != -1) {
        list($status, $rows, $record) = ona_get_interface_record(array('ip_addr' => $ip));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_interface() found record by IP address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            printmsg("DEBUG => No interface has the IP address: $search", 2);
            return(array(3, $rows, array()));
        }
        printmsg("DEBUG => More than one interface has the IP address: $search", 2);
        return(array(4, $rows, array()));
    }


    // If it's a MAC address
    // Save the mac in the format the DB uses (unformatted)
    $mac = mac_mangle($search, 1);
    if ($mac != -1) {

        // Search for it
        list($status, $rows, $record) = ona_get_interface_record(array('mac_addr' => $mac));

        // If we got it, return it
        if (!$status and $rows == 1) {
            printmsg("DEBUG => ona_find_interface() found record by MAC address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            printmsg("ERROR => No interface has the MAC address: $search", 2);
            return(array(5, 0, array()));
        }
        printmsg("DEBUG => ona_find_interface() More than one interface has the MAC address: " . mac_mangle($mac, 1), 0);
        return(array(6, 0, array()));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    printmsg("DEBUG => ona_find_interface() couldn't find a unique interface record with specified search criteria", 1);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_subnet(string $search)
//
//  Input:
//    $search = A subnet ID or IP address that can uniquly identify a subnet.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the NETWORKS_B table where $search matchs.
//
//  Example: list($status, $rows, $subnet) = ona_find_subnet('10.44.10.123');
///////////////////////////////////////////////////////////////////////
function ona_find_subnet($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        foreach (array('id', 'ip_addr') as $field) {
            list($status, $rows, $record) = ona_get_subnet_record(array($field => $search));
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_subnet() found location record by $field", 2);
                return(array(0, $rows, $record));
            }
        }
    }

    // If it's an IP address
    $ip = ip_mangle($search, 1);
    if ($ip != -1) {

        // Do a cool SQL query to find the subnet that the given IP address is on
        // Basically we tell the database we want to find an IP address >= than the base
        // of the subnet, and less than the end of the subnet.
        // Description:
        //   (2^32 - 1) == 4294967295 == a 32bit integer with all 1's.
        //   4294967295 - subnet_mask results in the number of hosts on that subnet.
        //   + the base ip_addr results in the top of the subnet.
        $where = "$ip >= ip_addr AND $ip <= ((4294967295 - ip_mask) + ip_addr)";

        list($status, $rows, $record) = ona_get_subnet_record($where);

        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_subnet() found record by IP address", 2);
            return(array(0, $rows, $record));
        }

        // Otherwise return an error
        if ($rows == 0) {
            $ip = ip_mangle($ip, 2);
            $self['error'] = "NOTICE => IP supplied, $ip, does not belong to any existing subnet!";
            printmsg($self['error'], 2);
            return(array(3, $rows, array()));
        }
        $self['error'] = "NOTICE => IP supplied, $ip, belongs to more than one subnet! Data corruption?";
        printmsg($self['error'], 2);
        return(array(4, $rows, array()));
    }

    // Try the name field
    // We use all upper-case subnet names
    list($status, $rows, $record) = ona_get_subnet_record(array('name' => strtoupper($search)));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_subnet() found subnet record by its name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique subnet record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_device(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a device from
//              the device models table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the DEVICE_MODELS_B table where
//         $search matchs.
//
//  Example: list($status, $rows, $subnet) = ona_find_device('10.44.10.123');
///////////////////////////////////////////////////////////////////////
function ona_find_device($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric
    if (preg_match('/^\d+$/', $search)) {
        // It's a number - do several sql queries and see if we can get a unique match
        foreach (array('ID', 'DEVICE_TYPE_ID', 'MANUFACTURER_ID') as $field) {
            list($status, $rows, $record) = ona_get_model_record(array($field => $search));
            // If we got it, return it
            if ($status == 0 and $rows == 1) {
                printmsg("DEBUG => ona_find_device() found device record by $field", 2);
                return(array(0, $rows, $record));
            }
        }
    }

    // It's a string - do several sql queries and see if we can get a unique match
    list($status, $rows, $record) = ona_get_model_record(array('MODEL_DESCRIPTION' => $search));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_device() found device record by MODEL_DESCRIPTION", 2);
        return(array(0, $rows, $record));
    }


    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique device record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_subnet_type(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a subnet
//              type from the NETWORK_TYPES_B table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the NETWORK_TYPES_B table where
//         $search matchs.
//
//  Example: list($status, $rows, $net_type) = ona_find_subnet_type('VLAN (802.1Q or ISL)');
///////////////////////////////////////////////////////////////////////
function ona_find_subnet_type($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (preg_match('/^\d+$/', $search)) {
        $field = 'id';
        list($status, $rows, $record) = ona_get_subnet_type_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_subnet_type() found device record by $field", 2);
            return(array(0, $rows, $record));
        }
    }

    // It's a string - do several sql queries and see if we can get a unique match
    list($status, $rows, $record) = ona_get_subnet_type_record(array('name' => $search));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_subnet_type() found subnet_type record by its name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique subnet_type record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}





///////////////////////////////////////////////////////////////////////
//  Function: ona_find_infobit(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify an infobit
//              from the INFOBITS_B table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the INFOBITS_B table where
//         $search matchs.
//
//  Example: list($status, $rows, $net_type) = ona_find_infobit('Status (Testing)');
///////////////////////////////////////////////////////////////////////
function ona_find_infobit($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (is_numeric($search)) {
        $field = 'ID';
        list($status, $rows, $record) = ona_get_infobit_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_infobit() found infobit record by $field", 2);
            return(array(0, $rows, $record));
        }
    }

    // Split the infobit description based on the () enclosed infobit type
    list($infobit_type, $infobit_value) = preg_split("/\(|\)/",$search);

    printmsg("DEBUG => ona_find_infobit(): Split is {$infobit_type},{$infobit_value}", 3);


    // It's a string - do several sql queries and see if we can get a unique match
    list($status, $rows, $type) = ona_get_infobit_type_record(array('NAME' => trim($infobit_type)));

    printmsg("DEBUG => ona_find_infobit(): Found {$rows} infobit type record", 3);

    // Find the infobit ID using the type id and value
    list($status, $rows, $record) = ona_get_infobit_record(array('VALUE' => $infobit_value,'INFOBIT_TYPE_ID' => $type['ID']));
    // If we got it, return it
    if ($status == 0 and $rows == 1) {
        printmsg("DEBUG => ona_find_infobit(): Found infobit record by its full name", 2);
        return(array(0, $rows, $record));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique infobit record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_dhcp_parameter_type(string $search)
//
//  Input:
//    $search = A string or ID that can uniquly identify a dhcp parm type
//              from the dhcp_parameter_type_B table in the database.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the DHCP_PARAMETER_TYPE_B table where
//         $search matchs.
//
//  Example: list($status, $rows, $dhcp_type) = ona_find_dhcp_parameter_type('Default gateway(s)');
///////////////////////////////////////////////////////////////////////
function ona_find_dhcp_parameter_type($search="") {
    global $self;

    // Validate input
    if ($search == "") {
        return(array(1, 0, array()));
    }

    // If it's numeric, search by record ID
    if (is_numeric($search)) {
        $field = 'ID';
        list($status, $rows, $record) = ona_get_dhcp_parm_type_record(array($field => $search));
        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_dhcp_parameter_type(): found type record by $field", 2);
            return(array(0, $rows, $record));
        }
    }

    foreach (array('DHCP_DESCRIPTION', 'DHCP_TAG', 'DHCP_NUMBER') as $field) {
        // Do several sql queries and see if we can get a unique match
        list($status, $rows, $record) = ona_get_dhcp_parm_type_record(array($field => $search));

        // If we got it, return it
        if ($status == 0 and $rows == 1) {
            printmsg("DEBUG => ona_find_dhcp_parameter_type(): Found type record -> {$record['DHCP_DESCRIPTION']}", 2);
            return(array(0, $rows, $record));
        }
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique infobit record with specified search criteria";
    printmsg($self['error'], 2);
    return(array(2, 0, array()));
}










///////////////////////////////////////////////////////////////////////
//  Function: ona_find_vlan(string $vlan_search, [string $campus_search])
//
//  Input:
//    $vlan_search =
//        A string or ID that can uniqely identify a vlan record from
//        the VLAN_B table in the database.  Often times a vlan
//        description is 'DEFAULT', in which case you can help narrow
//        down the search by also providing $campus_search.. see below.
//    $campus_search =
//        A string or ID that can uniqely identify a vlan campus record
//        from the VLAN_CAMPUS_B table.  Often times a vlan itself can't
//        be identified by name without a campus name too.
//
//  Output:
//    Returns a three part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//         When a non-zero exit status is returned a textual description
//         of the error will be stored in the global variable $self['error']
//      2. The number of rows that were found - 0 or 1 (0 is returned if
//         a unique match couldn't be found)
//      3. An array of a record from the VLAN_B table where $search matchs.
//
//  Example: list($status, $rows, $vlan) = ona_find_vlan('VLAN (802.1Q or ISL)');
///////////////////////////////////////////////////////////////////////
function ona_find_vlan($vlan_search="", $campus_search="") {
    global $self;
    if (!$vlan_search and !$campus_search) return(array(1, 0, array()));

    // All vlan and vlan campus names should be upper case
    $vlan_search = strtoupper($vlan_search);
    $campus_search = strtoupper($campus_search);

    // If we got a vlan campus search string, let's look for that first.
    if ($campus_search) {
        // Do a few sql queries and see if we can get a unique match
        $search = $campus_search;
        foreach (array('NAME', 'ID') as $field) {
            list($status, $rows, $campus) = ona_get_vlan_campus_record(array($field => $search));
            if (!$status and $rows == 1) {
                printmsg("DEBUG => ona_find_vlan() found vlan campus record by $field", 2);
                break;
            }
            else
                $campus = array();
        }
    }

    // Search for a vlan by ID
    if (is_numeric($vlan_search)) {
        list($status, $rows, $vlan) = ona_get_vlan_record(array('ID' => $vlan_search));
        if (!$status and $rows == 1) {
            printmsg("DEBUG => ona_find_vlan() found vlan record by ID", 2);
            return(array($status, $rows, $vlan));
        }
    }

    // Search for a vlan by NAME, use the campus[ID] if we have one
    $where = array('NAME' => $vlan_search);
    if ($campus['ID']) $where['VLAN_CAMPUS_ID'] = $campus['ID'];
    list($status, $rows, $vlan) = ona_get_vlan_record($where);
    if (!$status and $rows == 1) {
        printmsg("DEBUG => ona_find_vlan() found vlan record by VLAN name", 2);
        return(array($status, $rows, $vlan));
    }

    // We didn't find it - return and error code, 0 matches, and an empty record.
    $self['error'] = "NOTICE => couldn't find a unique vlan record with specified search criteria";
    printmsg($self['error'], 1);
    return(array(2, 0, array()));
}





///////////////////////////////////////////////////////////////////////
//  Function: ona_find_config (array $search)
//
//  Input Options:
//    $search = See functions below for how this function is used.
//              It's an internal function.
//
//  Output:
//    Returns the data returned from ona_get_config_record()
//    Error messages are stored in global $self['error']
//
///////////////////////////////////////////////////////////////////////
function ona_find_config($options=array()) {

    global $self;

    $status = 1;
    $rows = 0;
    $config = array();

    // If the user specified a config text ID
    if ($options['config']) {
        if (!preg_match('/^\d+$/', $options['config'])) {
            $self['error'] = "ERROR => A non-digit config ID was specified!";
            return(array(2, 0, array()));
        }

        list($status, $rows, $config) = ona_get_config_record(array('CONFIG_TEXT_ID' => $options['config']));
    }

    // Otherwise we're selecting a config by hostname and type
    else if ($options['host'] and $options['type']) {
        // Search for the host first
        list($name, $zone_name, $zone_id) = ona_find_zone($options['host']);
        list($status, $rows, $host) = ona_get_host_record(array('PRIMARY_DNS_NAME' => $name,
                                                                 'PRIMARY_DNS_ZONE_ID' => $zone_id));
        // Error if the host doesn't exist
        if (!$host['ID']) {
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(3, 0, array()));
        }

        // Now find the ID of the config type they entered
        $config_type_id = ona_get_configtype_deref($options['type']);
        if ($config_type_id == 0) {
            $self['error'] = "ERROR => The config type specified, {$options['type']}, is invalid!\n" .
                             "INFO => The only valid types are: add_store, IOS_CONFIG, and IOS_VERSION";
            return(array(4, 0, array()));
        }

        // Select the first config record of the specified type and host
        list($status, $rows, $config) = ona_get_config_record(array('HOST_ID' => $host['ID'],
                                                                     'CONFIG_TYPE_ID' => $config_type_id));
    }

    // Return the config record we got
    return(array($status, $rows, $config));

}








// DON'T put whitespace at the beginning or end of this file!!!
?>
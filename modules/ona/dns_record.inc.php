<?
// DON'T put whitespace at the beginning or end of this file!!!





///////////////////////////////////////////////////////////////////////
//  Function: dns_record_add (string $options='')
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
//  Example: list($status, $result) = dns_record_add('host=test&type=&unit=');
///////////////////////////////////////////////////////////////////////
function dns_record_add($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dns_record_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['name'] and $options['type']) ) {
//FIXME: PK:    if ($options['help'] or !($options['host'] and $options['type'] and $options['unit']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dns_record_add-v{$version}
Add a new DNS record

  Synopsis: dns_record_add [KEY=VALUE] ...

  Required:
    name=NAME[.DOMAIN]        hostname for new DNS record
    type=TYPE                 record type (A,CNAME,PTR...)

  Optional:
    notes=NOTES               textual notes
    ip=ADDRESS                ip address (numeric or dotted)
    ttl=NUMBER                time in seconds, defaults to ttl from domain
    pointsto=NAME[.DOMAIN]    hostname that a CNAME points to
    addptr                    auto add a PTR record when adding A records

\n
EOM
        ));
    }


/*
thoughts on the flow of things:

a records:
    check if there is an A record with that name/domain and IP already.
    check that name/domain does not match a CNAME entry
    will not have a dns_id value.. blank it out
    if autoptr is set, create a ptr record too
cname records:
    check that name/domain does not match an A entry
    check that name/domain does not match an CNAME entry
    name/domain and dns_id columns must be unique---< implied by the previous check of no cnames using this name
    do I need interface_id??????, yes its used to assoicate it with the host.  this will come via the A record it points to via a lookup
ptr records:
    will not have a domain_id, blank it out
    must be unique in interface_id column, ie. one PTR per interface/ip




FIXME: do some validation of the different options, pointsto only with cname type etc etc

*/

    // Switch the type setting to uppercase
    $options['type'] = strtoupper($options['type']);

    // Determine the real hostname and domain name to be used --
    // i.e. add .something.com, or find the part of the name provided
    // that will be used as the "domain".  This means testing many
    // domain names against the DB to see what's valid.
    //
    // Find the domain name piece of $search
    list($status, $rows, $domain) = ona_find_domain($options['name']);
    printmsg("DEBUG => ona_find_domain({$options['name']}) returned: {$domain['fqdn']}", 3);

    // Now find what the host part of $search is
    $hostname = str_replace(".{$domain['fqdn']}", '', $options['name']);

    // Validate that the DNS name has only valid characters in it
    $hostname = sanitize_hostname($hostname);
    if (!$hostname) {
        printmsg("DEBUG => Invalid host name ({$options['name']})!", 3);
        $self['error'] = "ERROR => Invalid host name ({$options['name']})!";
        return(array(4, $self['error'] . "\n"));
    }
    // Debugging
    printmsg("DEBUG => Using hostname: {$hostname}.{$domain['fqdn']}, Domain ID: {$domain['id']}", 3);



    // Process A record types
    if ($options['type'] == 'A') {
        // find the IP interface record,
        list($status, $rows, $interface) = ona_find_interface($options['ip']);
        if (!$rows) {
            printmsg("ERROR => dns_record_add() Unable to find IP interface: {$options['ip']}",3);
            $self['error'] = "ERROR => dns_record_add() Unable to find IP interface: {$options['ip']}\n";
            return(array(4, $self['error']));
        }


        // Validate that there isn't already any dns record named $hostname in the domain $domain_id.
        list($d_status, $d_rows, $d_record) = ona_get_dns_record(array('name' => $hostname, 'domain_id' => $domain['id'],'interface_id' => $interface['id'],'type' => 'A'));
        if ($d_status or $d_rows) {
            printmsg("ERROR => Another DNS A record named {$hostname}.{$domain['fqdn']} already exists!",3);
            $self['error'] = "ERROR => Another DNS A record named {$hostname}.{$domain['fqdn']} already exists!";
            return(array(5, $self['error'] . "\n"));
        }

        // Validate that there are no CNAMES already with this fqdn
        list($c_status, $c_rows, $c_record) = ona_get_dns_record(array('name' => $hostname, 'domain_id' => $domain['id'],'type' => 'CNAME'));
        if ($c_rows or $c_status) {
            printmsg("ERROR => Another DNS CNAME record named {$hostname}.{$domain['fqdn']} already exists!",3);
            $self['error'] = "ERROR => Another DNS CNAME record named {$hostname}.{$domain['fqdn']} already exists!";
            return(array(5, $self['error'] . "\n"));
        }


        $add_name = $hostname;
        $add_domainid = $domain['id'];
        $add_interfaceid = $interface['id'];
        // A records should not have parent dns records
        $add_dnsid = '';

        $info_msg = "{$hostname}.{$domain['fqdn']} -> " . ip_mangle($interface['ip_addr'],'dotted');

        // Just to be paranoid, I'm doing the ptr checks here as well if addptr is set
        if ($options['addptr']) {
            // Check that no other PTR records are set up for this IP
            list($status, $rows, $record) = ona_get_dns_record(array('interface_id' => $interface['id'], 'type' => 'PTR'));
            if ($rows) {
                printmsg("ERROR => Another DNS PTR record already exists for this IP interface!",3);
                $self['error'] = "ERROR => Another DNS PTR record already exists for this IP interface!";
                return(array(5, $self['error'] . "\n"));
            }
        }

    }

    // Process PTR record types
    if ($options['type'] == 'PTR') {
        // find the IP interface record,
        list($status, $rows, $interface) = ona_find_interface($options['ip']);
        if (!$rows) {
            printmsg("ERROR => dns_record_add() Unable to find IP interface: {$options['ip']}",3);
            $self['error'] = "ERROR => dns_record_add() Unable to find IP interface: {$options['ip']}\n";
            return(array(4, $self['error']));
        }


        // Check that no other PTR records are set up for this IP
        list($status, $rows, $record) = ona_get_dns_record(array('interface_id' => $interface['id'], 'type' => 'PTR'));
        if ($rows) {
            printmsg("ERROR => Another DNS PTR record already exists for this IP interface!",3);
            $self['error'] = "ERROR => Another DNS PTR record already exists for this IP interface!";
            return(array(5, $self['error'] . "\n"));
        }

        // Find the dns record that it will point to
        list($status, $rows, $arecord) = ona_get_dns_record(array('name' => $hostname, 'domain_id' => $domain['id'],'interface_id' => $interface['id'], 'type' => 'A'));
        if ($status or !$rows) {
            printmsg("ERROR => Unable to find DNS A record to point PTR entry to! Check that the IP you chose is associated with the name you chose.",3);
            $self['error'] = "ERROR => Unable to find DNS A record to point PTR entry to! Check that the IP you chose is associated with the name you chose.";
            return(array(5, $self['error'] . "\n"));
        }


        // PTR records dont need a name set.
        $add_name = '';
        // PTR records should not have domain_ids
        $add_domainid = '';
        $add_interfaceid = $interface['id'];
        $add_dnsid = $arecord['id'];

        $info_msg = ip_mangle($interface['ip_addr'],'flip').".IN-ADDR.ARPA -> {$hostname}.{$domain['fqdn']}";

    }


/*
FIXME: MP
So there is this fun problem with CNAMES.  I can associate them with a single A record
such that if that A record is changed, or gets removed then I can cleanup/update the CNAME entry.

The problem comes when there are multiple A records that use the same name but different IP addresses.
I can only assoicate the CNAME with one of those A records.  This also means I need to provided
the IP address as well when adding a CNAME so I can choose the correct A record.

In a similar (reverse) issue.  If I have those same multiple A records, the assumption is that
they are all the same name and thus "tied" together in that if I was to change the name to something else
all the A records should all change at once.  Currently I'd have to change ALL the A record entries with the same name manually


Its almost like I'd need a dns_record to name many to one type table.  that would be very annoying!


For now, I'm going to keep going forward as is and hope that even though it is allowed, most people will not create such
complex DNS messes for themselves.


*/



    // Process CNAME record types
    if ($options['type'] == 'CNAME') {
        // Determine the host and domain name portions of the pointsto option
        // Find the domain name piece of $search
        list($status, $rows, $pdomain) = ona_find_domain($options['pointsto']);
        printmsg("DEBUG => ona_find_domain({$options['pointsto']}) returned: {$domain['fqdn']} for pointsto.", 3);
    
        // Now find what the host part of $search is
        $phostname = str_replace(".{$domain['fqdn']}", '', $options['pointsto']);
    
        // Validate that the DNS name has only valid characters in it
        $phostname = sanitize_hostname($phostname);
        if (!$phostname) {
            printmsg("DEBUG => Invalid pointsto host name ({$options['pointsto']})!", 3);
            $self['error'] = "ERROR => Invalid pointsto host name ({$options['pointsto']})!";
            return(array(4, $self['error'] . "\n"));
        }
        // Debugging
        printmsg("DEBUG => Using 'pointsto' hostname: {$phostname}.{$pdomain['fqdn']}, Domain ID: {$pdomain['id']}", 3);

        // Validate that the CNAME I'm adding doesnt match an existing A record.
        list($d_status, $d_rows, $d_record) = ona_get_dns_record(array('name' => $hostname, 'domain_id' => $domain['id'],'type' => 'A'));
        if ($d_status or $d_rows) {
            printmsg("ERROR => Another DNS A record named {$hostname}.{$domain['fqdn']} already exists!",3);
            $self['error'] = "ERROR => Another DNS A record named {$hostname}.{$domain['fqdn']} already exists!";
            return(array(5, $self['error'] . "\n"));
        }


        // Validate that there are no CNAMES already with this fqdn
        list($c_status, $c_rows, $c_record) = ona_get_dns_record(array('name' => $hostname, 'domain_id' => $domain['id'],'type' => 'CNAME'));
        if ($c_rows or $c_status) {
            printmsg("ERROR => Another DNS CNAME record named {$hostname}.{$domain['fqdn']} already exists!",3);
            $self['error'] = "ERROR => Another DNS CNAME record named {$hostname}.{$domain['fqdn']} already exists!";
            return(array(5, $self['error'] . "\n"));
        }

        // Find the dns record that it will point to
        list($status, $rows, $pointsto_record) = ona_get_dns_record(array('name' => $phostname, 'domain_id' => $pdomain['id'], 'type' => 'A'));
        if ($status or !$rows) {
            printmsg("ERROR => Unable to find DNS A record to point CNAME entry to!",3);
            $self['error'] = "ERROR => Unable to find DNS A record to point CNAME entry to!";
            return(array(5, $self['error'] . "\n"));
        }



        $add_name = $hostname;
        $add_domainid = $domain['id'];
        $add_interfaceid = $pointsto_record['interface_id'];
        $add_dnsid = $pointsto_record['id'];

        $info_msg = "{$hostname}.{$domain['fqdn']} -> {$phostname}.{$pdomain['fqdn']}";

    }







    //FIXME: MP, will this use its own dns_record_add permission? or use host_add?
    // Check permissions
    if (!auth('host_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new dns record
    $id = ona_get_next_id('dns');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('dns') call failed!";
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new dns record: $id", 3);

    // If a ttl was passed use it, otherwise use what was in the domain minimum
    if ($options['ttl']) { $add_ttl = $options['ttl']; } else { $add_ttl = ''; }

    // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
    $options['notes'] = str_replace('\\=','=',$options['notes']);
    $options['notes'] = str_replace('\\&','&',$options['notes']);

    // Add the dns record
    list($status, $rows) = db_insert_record(
        $onadb,
        'dns',
        array(
            'id'                   => $id,
            'domain_id'            => $add_domainid,
            'interface_id'         => $add_interfaceid,
            'dns_id'               => $add_dnsid,
            'type'                 => $options['type'],
            'ttl'                  => $add_ttl,
            'name'                 => $add_name
//            'notes'                => $options['notes']
       )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => dns_record_add() SQL Query failed adding dns record: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    $text = '';

    // If it is an A record and they have specified to auto add the PTR record for it.
    if ($options['addptr'] and $options['type'] == 'A') {
        printmsg("DEBUG => Auto adding a PTR record for {$options['name']}.", 0);
        // Run dns_record_add as a PTR type
        list($status, $output) = run_module('dns_record_add', array('name' => $options['name'],'ip' => $options['ip'], 'type' => 'PTR'));
        if ($status)
            return(array($status, $output));
        $text .= $output;
    }

    // Else start an output message
    $text .= "INFO => DNS {$options['type']} record ADDED: {$info_msg}";
    printmsg($text,0);
    $text .= "\n";

    // Return the success notice
    return(array(0, $text));
}











///////////////////////////////////////////////////////////////////////
//  Function: dns_record_modify (string $options='')
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
//  Example: list($status, $result) = dns_record_modify('FIXME: blah blah blah');
///////////////////////////////////////////////////////////////////////
function dns_record_modify($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dns_record_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['set_name'] and
        !$options['set_ip'] and
        !$options['set_ttl'] and
        !$options['set_pointsto'] and
        !$options['set_notes']
       ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dns_record_modify-v{$version}
Modify a DNS record

  Synopsis: dns_record_modify [KEY=VALUE] ...

  Where:
    name=NAME[.DOMAIN] or ID  select dns record by name or ID

  Update:
    set_name=NAME[.DOMAIN]      change name and/or domain
    set_ip=ADDRESS              change IP the record points to
    set_ttl=NUMBER              change the TTL value, 0 = use domains TTL value
    set_pointsto=NAME[.DOMAIN]  change where a CNAME points
    set_notes=NOTES             change the textual notes

  Note:
    * You are not allowed to change the type of the DNS record, to do that
      you must delete and re-add the record with the new type.
\n
EOM
        ));
    }

    //
    // Find the dns record we're modifying
    //


    // FIXME: MP Fix this to use a find_dns_record function  ID only for now
    // Find the DNS record from $options['name']
    list($status, $rows, $dns) = ona_get_dns_record(array('id' => $options['name']), '');
    printmsg("DEBUG => dns_record_modify() DNS record: {$dns['name']}", 3);
    if (!$dns['id']) {
        printmsg("DEBUG => Unknown DNS record: {$options['name']}",3);
        $self['error'] = "ERROR => Unknown DNS record: {$options['name']}";
        return(array(2, $self['error'] . "\n"));
    }


    // If we didn't get a record then exit
    if (!$dns['id']) {
        printmsg("DEBUG => DNS record not found ({$options['name']})!",3);
        $self['error'] = "ERROR => DNS record not found ({$options['name']})!";
        return(array(4, $self['error'] . "\n"));
    }



    //
    // Define the records we're updating
    //

    // This variable will contain the updated info we'll insert into the DB
    $SET = array();

    // Set options['set_name']?
    // Validate that the DNS name has only valid characters in it
    if ($options['set_name']) {
        $options['set_name'] = sanitize_hostname($options['set_name']);
        if (!$options['set_name']) {
            printmsg("DEBUG => Invalid host name ({$options['set_name']})!", 3);
            $self['error'] = "ERROR => Invalid host name ({$options['set_name']})!";
            return(array(5, $self['error'] . "\n"));
        }
        // Get the host & domain part
        list($status, $rows, $tmp_host) = ona_find_host($options['set_name']);
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



    // Set options['set_notes'] (it can be a null string!)
    if (array_key_exists('set_notes', $options)) {
        // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
        $options['set_notes'] = str_replace('\\=','=',$options['set_notes']);
        $options['set_notes'] = str_replace('\\&','&',$options['set_notes']);
        // If it changed...
        if ($dns['notes'] != $options['set_notes'])
            $SET['notes'] = $options['set_notes'];
    }

    // Check permissions
    if (!auth('host_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the dns record before updating (logging)
    $original_record = $dns;

    // Update the host record if necessary
    if(count($SET) > 0) {
        list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $dns['id']), $SET);
        if ($status or !$rows) {
            $self['error'] = "ERROR => dns_record_modify() SQL Query failed for dns record: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(8, $self['error'] . "\n"));
        }
    }

    // Get the host record after updating (logging)
    list($status, $rows, $new_record) = ona_get_dns_record(array('id' => $dns['id']));

    // Return the success notice
    $self['error'] = "INFO => DNS record UPDATED:{$dns['id']}: {$new_dns['fqdn']}";

    $log_msg = "INFO => DNS record UPDATED:{$dns['id']}: ";
    $more="";
    foreach(array_keys($dns) as $key) {
        if($dns[$key] != $new_record[$key]) {
            $log_msg .= "{$more}{$key}: {$dns[$key]} => {$new_record[$key]}";
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
//  Function: dns_record_del (string $options='')
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
//  Example: list($status, $result) = dns_record_del('name=test');
///////////////////////////////////////////////////////////////////////
function dns_record_del($options="") {
    global $conf, $self, $onadb;
    printmsg("DEBUG => host_del({$options}) called", 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['name']) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dns_record_del-v{$version}
Deletes a DNS record from the database

  Synopsis: host_del [KEY=VALUE] ...

  Required:
    name=NAME[.DOMAIN] or ID      hostname or ID of the record to delete
    type=TYPE                     record type (A,CNAME,PTR...)

  Optional:
    ip=ADDRESS                    ip address (numeric or dotted)
    commit=[yes|no]               commit db transaction (no)

\n
EOM
        ));
    }
/*
thoughts on the flow of things:

A records:
    remove any CNAMES using this A record
    remove any PTR records using this A record
    test that it is not a primary_dns_id, if it is, it must be reassigned


should make a find_dns_record(s) function.  a find by host option would be good.

need to do a better delete of DNS records when deleting a host.. currently its a problem.

*/

    // FIXME: MP Fix this to use a find_dns_record function  ID only for now
    // Find the DNS record from $options['name']
    list($status, $rows, $dns) = ona_get_dns_record(array('id' => $options['name']), '');
    printmsg("DEBUG => dns_record_del() DNS record: {$dns['name']}", 3);
    if (!$dns['id']) {
        printmsg("DEBUG => Unknown DNS record: {$options['name']}",3);
        $self['error'] = "ERROR => Unknown DNS record: {$options['name']}";
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
        //   Display any associated PTR records for an A record
        //   Display any associated CNAMEs for an A record






        // Delete related PTR records
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'PTR'));
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'PTR'));
        if ($status) {
            $self['error'] = "ERROR => dns_record_del() PTR record delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }
        // log deletions
        printmsg("INFO => {$rows} child PTR record(s) DELETED from {$dns['name']}",0);
        $add_to_error .= "INFO => {$rows} child PTR record(s) DELETED from {$dns['name']}\n";



        // Delete related CNAME records
        // get list for logging
        list($status, $rows, $records) = db_get_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'CNAME'));
        // do the delete
        list($status, $rows) = db_delete_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'CNAME'));
        if ($status) {
            $self['error'] = "ERROR => dns_record_del() CNAME record delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $self['error'] . "\n"));
        }
        // log deletions
        foreach ($records as $record) {
            list($status, $rows, $domain) = ona_get_domain_record(array('id' => $record['domain_id']), '');
            printmsg("INFO => Child CNAME record DELETED: {$record['name']}.{$domain['fqdn']} from {$dns['name']}",0);
            $add_to_error .= "INFO => Child CNAME record DELETED: {$record['name']}.{$domain['fqdn']} from {$host['name']}\n";
        }



        // Delete the DNS record
        list($status, $rows) = db_delete_records($onadb, 'dns', array('id' => $dns['id']));
        if ($status) {
            $self['error'] = "ERROR => host_del() DNS record delete SQL Query failed: {$self['error']}";
            printmsg($self['error'],0);
            return(array(5, $add_to_error . $self['error'] . "\n"));
        }


        // Return the success notice
        $self['error'] = "INFO => DNS record DELETED: {$options['name']}";
        printmsg($self['error'], 0);
        return(array(0, $add_to_error . $self['error'] . "\n"));
    }


    //
    // We are just displaying records that would have been deleted
    //

    // SUMMARY:
    //   Display any associated PTR records for an A record
    //   Display any associated CNAMEs for an A record


    // Otherwise just display the host record for the host we would have deleted
    $text = "Record(s) NOT DELETED (see \"commit\" option)\n" .
            "Displaying record(s) that would have been deleted:\n";

    // Test if it is used as a primary_dns_id
    list($status, $rows, $srecord) = db_get_record($onadb, 'hosts', array('primary_dns_id' => $dns['id']));
    if ($rows) {
        $text .= "\nWARNING!  This host is a DHCP server for {$rows} subnet(s)\n";
    }
    // Display the complete dns record
    list($status, $tmp) = dns_record_display("name={$dns['id']}&verbose=N");
    $text .= "\n" . $tmp;

    // Display count of PTR records
    list($status, $rows, $records) = db_get_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'PTR'));
    if ($rows) $text .= "\nASSOCIATED PTR RECORDS ({$rows}):\n";

    // Display associated CNAME records
    list($status, $rows, $records) = db_get_records($onadb, 'dns', array('dns_id' => $dns['id'], 'type' => 'CNAME'));
    if ($rows) $text .= "\nASSOCIATED CNAME RECORDS ({$rows}):\n";
    foreach ($records as $record) {
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $record['domain_id']), '');
        $text .= "  {$record['name']}.{$domain['fqdn']} -> {$dns['name']}.{$dns['fqdn']}\n";
    }



    return(array(7, $text));
}











///////////////////////////////////////////////////////////////////////
//  Function: dns_record_display (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=HOSTNAME[.DOMAIN] or ID
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = dns_record_display('name=test');
///////////////////////////////////////////////////////////////////////
function dns_record_display($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => dns_record_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[verbose] (default is yes)
    $options['verbose'] = sanitize_YN($options['verbose'], 'Y');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['name'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

dns_record_display-v{$version}
Displays a DNS record from the database

  Synopsis: dns_record_display [KEY=VALUE] ...

  Required:
    name=NAME[.DOMAIN] or ID      hostname or ID of the dns record to display

  Optional:
    verbose=[yes|no]              display additional info (yes)

\n
EOM

        ));
    }

// FIXME: MP This function is not at all working.. fix it up later.

    // Find the DNS record from $options['name']
    list($status, $rows, $record) = ona_get_dns_record(array('id' => $options['name']), '');
    printmsg("DEBUG => dns_record_del() DNS record: {$record['name']}", 3);
    if (!$record['id']) {
        printmsg("DEBUG => Unknown DNS record: {$options['name']}",3);
        $self['error'] = "ERROR => Unknown DNS record: {$options['name']}";
        return(array(2, $self['error'] . "\n"));
    }

    // Build text to return
    $text  = "DNS {$record['type']} RECORD ({$record['name']}.{$record['fqdn']})\n";
    $text .= format_array($record);

    // If 'verbose' is enabled, grab some additional info to display
    if ($options['verbose'] == 'Y') {

        // PTR record(s)
        $i = 0;
        do {
            list($status, $rows, $ptr) = ona_get_dns_record(array('dns_id' => $record['id'],'type' => 'PTR'));
            if ($rows == 0) { break; }
            $i++;
            $text .= "\nASSOCIATED PTR RECORD ({$i} of {$rows})\n";
            $text .= format_array($ptr);
        } while ($i < $rows);

        // CNAME record(s)
        $i = 0;
        do {
            list($status, $rows, $cname) = ona_get_dns_record(array('dns_id' => $record['id'],'type' => 'CNAME'));
            if ($rows == 0) { break; }
            $i++;
            $text .= "\nASSOCIATED CNAME RECORD ({$i} of {$rows})\n";
            $text .= format_array($cname);
        } while ($i < $rows);


// FIXME: MP like aliases below, show list of dns records associated


    }

    // Return the success notice
    return(array(0, $text));

}












// DON'T put whitespace at the beginning or end of this file!!!
?>

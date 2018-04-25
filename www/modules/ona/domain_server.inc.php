<?php
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);



///////////////////////////////////////////////////////////////////////
//  Function: domain_server_add (string $options='')
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
//  Example: list($status, $result) = domain_server_add('domain=&server=host.something.com');
///////////////////////////////////////////////////////////////////////
function domain_server_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.02';

    printmsg("DEBUG => domain_server_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help']
            or
        !($options['domain'] and $options['server'])
        ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

domain_server_add-v{$version}
Assigns an existing domain record to a DNS server

  Synopsis: domain_server_add [KEY=VALUE] ...

  Required:
    domain=NAME or ID               domain name or ID
    server=NAME[.DOMAIN] or ID      server name or ID
    role=master|slave|forward       role of this server for this domain

EOM

        ));
    }


//     if (is_numeric($options['domain'])) {
//         $domainsearch['id'] = $options['domain'];
//     } else {
//         $domainsearch['name'] = strtolower($options['domain']);
//     }

    // Determine the entry itself exists
    list($status, $rows, $domain) = ona_find_domain($options['domain'],0);

    // Test to see that we were able to find the specified record
    if (!$domain['id']) {
        printmsg("DEBUG => Unable to find the domain record using {$options['domain']}!",3);
        $self['error'] = "ERROR => Unable to find the domain record using {$options['domain']}!";
        return(array(4, $self['error']. "\n"));
    }

    printmsg("DEBUG => domain_server_add(): Found domain, {$domain['name']}", 3);

    // Determine the server is valid
    list($status, $rows, $ns_dns) = ona_find_dns_record($options['server'] . "." . $domain['fqdn']);
    list($status, $rows, $interface) = ona_find_interface($ns_dns['interface_id']);

    $host['id'] = $interface['host_id'];

    if (!$host['id']) {
        printmsg("DEBUG => The server ({$options['server']}) does not exist!",3);
        $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }

    // what is the role for this server.
    switch (strtolower($options['role'])) {
        case "forward":
            $role = "forward";
            break;
        case "master":
            $role = "master";
            break;
        case "slave":
            $role = "slave";
            break;
        default:
            $role = "master";
    }



    // Check permissions
    if (!auth('advanced')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(12, $self['error'] . "\n"));
    }


    // Test that this domain isnt already assigned to the server
    list($status, $rows, $domainserver) = ona_get_dns_server_domain_record(array('host_id' => $host['id'],'domain_id' => $domain['id']));
    if ($rows) {
        printmsg("DEBUG => Domain {$domain['name']} already assigned to {$options['server']}",3);
        $self['error'] = "ERROR => Domain {$domain['name']} already assigned to {$options['server']}";
        return(array(11, $self['error'] . "\n"));
    }


    // Get the next ID
    $id = ona_get_next_id('dns_server_domains');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(6, $add_to_error . $self['error'] . "\n"));
    }

    printmsg("DEBUG => domain_server_add(): New DNS server domain ID: $id", 3);

    // Add new record to dns_server_domains
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dns_server_domains',
            array(
                'id'                      => $id,
                'host_id'                 => $host['id'],
                'domain_id'               => $domain['id'],
                'role'                    => $role,
                'rebuild_flag'            => 1

            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => domain_server_add() SQL Query failed:" . $self['error'];
        printmsg($self['error'],0);
        return(array(8, $add_to_error . $self['error'] . "\n"));
    }


    // Test that there are no NS records for this pair already
    // ASSUMPTION: MP this will always be just one record??
    list ($status, $dnsrows, $dnsrec) =
        db_get_record(
            $onadb,
            'dns',
            "domain_id = {$domain['id']} AND type = 'NS' AND interface_id in (select id from interfaces where host_id = {$host['id']})"
        );

    // Auto add the NS record if there were none found already. the user can remove any NS records they dont want afterwards
    if (!$dnsrows) {
        printmsg("DEBUG => Auto adding a NS record for {$options['server']}.", 0);
        // Run dns_record_add as a NS type
        list($status, $output) = run_module('dns_record_add', array('name' => $domain['fqdn'],'pointsto' => $options['server'] . "." . $domain['fqdn'], 'type' => 'NS'));
        if ($status)
            return(array($status, $output));
        $add_to_error .= $output;
    }
    else {
        printmsg("DEBUG => Found existing NS record for {$options['server']}. Skipping the auto add.", 0);
    }

    // Return the success notice
    $self['error'] = "INFO => DNS Domain/Server Pair ADDED: {$domain['name']}/{$options['server']} ";
    printmsg($self['error'],0);
    return(array(0, $add_to_error . $self['error'] . "\n"));


}






///////////////////////////////////////////////////////////////////////
//  Function: domain_server_del (string $options='')
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
//  Example: list($status, $result) = domain_server_del('domain=something.com&server=test-server.something.com');
///////////////////////////////////////////////////////////////////////
function domain_server_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.02';

    printmsg("DEBUG => domain_server_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[commit] (default is yes)
    $options['commit'] = sanitize_YN($options['commit'], 'N');


    // Return the usage summary if we need to
    if ($options['help'] or !($options['domain'] and $options['server']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

domain_server_del-v{$version}
Removes a domain record from a DNS server

  Synopsis: domain_server_del [KEY=VALUE] ...

  Required:
    domain=NAME or ID               domain name or ID
    server=NAME[.DOMAIN] or ID      server name or ID

  Optional:
    commit=[Y|N]                    commit db transaction (no)

EOM

        ));
    }


    if (is_numeric($options['domain'])) {
        $domainsearch['id'] = $options['domain'];
    } else {
        $domainsearch['name'] = strtoupper($options['domain']);
    }

    // Determine the entry itself exists
    list($status, $rows, $domain) = ona_get_domain_record($domainsearch);

    // Test to see that we were able to find the specified record
    if (!$domain['id']) {
        printmsg("DEBUG => Unable to find the domain record using {$options['domain']}!",3);
        $self['error'] = "ERROR => Unable to find the domain record using {$options['domain']}!";
        return(array(4, $self['error']. "\n"));
    }

    printmsg("DEBUG => domain_server_del(): Found domain, {$domain['name']}", 3);

    if ($options['server']) {
        // Determine the server is valid
        list($status, $rows, $host) = ona_find_host($options['server']);

        if (!$host['id']) {
            printmsg("DEBUG => The server ({$options['server']}) does not exist!",3);
            $self['error'] = "ERROR => The server specified, {$options['server']}, does not exist!";
            return(array(2, $self['error'] . "\n"));
        }
    }

    // Test that this domain is even assigned to the server
    list($status, $rows, $domainserver) = ona_get_dns_server_domain_record(array('host_id' => $host['id'],'domain_id' => $domain['id']));
    if (!$rows) {
        printmsg("DEBUG => Unable to find {$domain['name']} on server {$host['fqdn']}",3);
        $self['error'] = "ERROR => Unable to find {$domain['name']} on server {$host['fqdn']}";
        return(array(11, $self['error'] . "\n"));
    }

    // Test that there are no NS records for this pair
    // ASSUMPTION: MP this will always be just one record??
    // depending on how the user has their NS records set up, we may not find anything.
    list ($status, $dnsrows, $dnsrec) =
        db_get_record(
            $onadb,
            'dns',
            "domain_id = {$domain['id']} AND type = 'NS' AND interface_id in (select id from interfaces where host_id = {$host['id']})"
        );

    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced') or !authlvl($host['LVL']) or !authlvl($domain['LVL'])) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }


        // delete record from domain_server_domains
        list($status, $rows) = db_delete_records($onadb, 'dns_server_domains', array('id' => $domainserver['id']));
        if ($status) {
            $self['error'] = "ERROR => domain_server_del() SQL Query failed:" . $self['error'];
            printmsg($self['error'],0);
            return(array(9, $self['error'] . "\n"));
        }

        // Run the module to delete the associated NS record.. Only if we found a dns record for NS
        if ($dnsrec['id']) {
            list($status, $output) = run_module('dns_record_del', array('name' => $dnsrec['id'], 'type' => 'NS', 'commit' => 'Y'));
            if ($status) {
                $self['error'] = "ERROR => domain_server_del() NS record delete failed:" . $output;
                printmsg($self['error'],0);
                return(array(9, $self['error'] . "\n"));
            }
            else {
                // add the output to self error for display
                $add_to_error = $output;
            }
        }

        // Return the success notice
        $self['error'] = "INFO => DNS Domain/Server Pair DELETED: {$domain['name']}/{$host['fqdn']} ";
        printmsg($self['error'],0);
        return(array(0, $add_to_error. $self['error'] . "\n"));
    }

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
    Record(s) NOT DELETED (see "commit" option)
    Displaying record(s) that would have been removed:

    {$domain['name']} from: {$host['fqdn']}


EOL;

    if ($dnsrows) {
        $text .= "    Removing related NS record, if any. Please double check your NS records for this domain.\n";
    }

    return(array(6, $text));


}




// DON'T put whitespace at the beginning or end of this file!!!
?>
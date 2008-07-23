<?php
//
// This script is to be used when upgrading from version 08.05.14.
// It will help to create proper PTR zones for the subnets and DNS records you have
//
//
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__)."/../www/";
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder {$include}!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */

global $conf, $self, $onadb;

// Uncomment the following to get a ton o' debug
//$conf['debug'] = 6;

// Get the PTR records that dont have a domain_id
list($status, $rows, $ptrs) = db_get_records($onadb, 'dns', "domain_id = 0 and type like 'PTR'", '');
echo "Found {$rows} PTR records with no domain.\n";
if (!$rows) echo "  Everything looks OK!\n";

foreach ($ptrs as $ptr) {
    list($status, $rows, $interface) = ona_get_interface_record(array('id' => $ptr['interface_id']));

    // Print an error if it doesnt find an IP
    if (!$interface['ip_addr']) {
        echo "Possible orphan PTR record in dns table at ID: {$ptr['id']}.  You should delete this record manually.\n";
        continue;
    }

    $ipflip = ip_mangle($interface['ip_addr'],'flip');
    $octets = explode(".",$ipflip);
    // Find a pointer domain for this record to associate with.
    list($status, $rows, $ptrdomain) = ona_find_domain($ipflip.".in-addr.arpa",0);

    // CRAPPY security cludge
    $_SESSION['ona']['auth']['user']['username'] = 'PTRFIX';
    $_SESSION['ona']['auth']['perms']['advanced'] = 'Y';
    $_SESSION['ona']['auth']['perms']['host_modify'] = 'Y';

    if (!$ptrdomain['id']) {
        echo "  {$interface['ip_addr_text']}: Unable to find a pointer domain for this IP! Creating the following DNS domain: {$octets[3]}.in-addr.arpa\n";
        list($status, $output) = run_module('domain_add', array('name' => $octets[3].'.in-addr.arpa'));
        if ($status) {
            echo "ERROR => {$output}\n";
            exit($status);
        }
        list($status, $rows, $ptrdomain) = ona_find_domain($ipflip.".in-addr.arpa",0);
    }

    // Found a domain to put them in.
    echo "  Updating PTR for IP {$interface['ip_addr_text']} to domain {$ptrdomain['fqdn']}\n";

    // Change the actual DNS record
    list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $ptr['id']), array('domain_id' => $ptrdomain['id']));
    if ($status or !$rows) {
        echo "ERROR => SQL Query failed updating dns record: " . $self['error'];
        exit(2);
    }


}

exit(0);

?>

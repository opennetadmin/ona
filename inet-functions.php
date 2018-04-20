<?php
//
// This script is to be used when applying the inet[46]_[aton|ntoa] support for BIND-DLZ
// It will update all PTRs, so they can be served directly from the DNS tables
// Will add ONA unsupport DNS type of SOA in order to provide a complete solution
// for BIND DLZ without using views.
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
echo "Checking for SOA meta records:\n";

list($status, $rows, $domains) = db_get_records($onadb, 'domains', 'id > 0');
#print_r ( $domains );
foreach ( $domains as $d ) {
    list($status, $rows, $soa) = db_get_records($onadb, 'dns', "type = 'SOA' and domain_id = '" . $d['id'] . "' ");
    if ( $rows == 0 ) {
        printf ("{%36s} - doesn't have an SOA DNS Record. \n", $d['name'] );
        // Add the dns record
        $dns_id = ona_get_next_id('dns');
        list($status, $rows) = db_insert_record(
            $onadb,
            'dns',
            array(
                'id'                   => $dns_id,
                'domain_id'            => $d['id'],
                'interface_id'         => 0,
                'dns_id'               => 0,
                'type'                 => 'SOA',
                'ttl'                  => 0,
                'name'                 => '',
                'mx_preference'        => '0',
                'txt'                  => '',
                'srv_pri'              => 0,
                'srv_weight'           => 0,
                'srv_port'             => 0,
                'notes'                => '',
                'dns_view_id'          => 0
             )
        );                
        if ($status or !$rows) {
            printf ("{%36s} - SOA DNS Record insert FAILED. \n", $d['name'] );        
        } else {
            printf ("{%36s} - SOA DNS Record created. \n", $d['name'] );
        }
        
    } else {
        printf ("{%36s} - has an SOA DNS Record. \n", $d['name'] );
    }
}

// Get the PTR records that dont have a domain_id
list($status, $rows, $ptrs) = db_get_records($onadb, 'dns', "type = 'PTR'", '');
echo "Found {$rows} PTR with ptr records - will update all of them\n";

foreach ($ptrs as $ptr) {
    list($status, $rows, $interface) = ona_get_interface_record(array('id' => $ptr['interface_id']));

    // Print an error if it doesnt find an IP
    if (!$interface['ip_addr']) {
        echo "Possible orphan PTR record in dns table at ID: {$ptr['id']}.  You should delete this record manually.\n";
        continue;
    }

    $ipflip = ip_mangle($interface['ip_addr'],'flip');
            $octets = explode(".",$ipflip);
            if (count($octets) > 4) {
                $arpa = 'ip6.arpa';
                $octcount = 31;
		#$domain = $arpa;
		$a = array ();
		for ($i=$octcount; $i>$octcount-11; $i--) {
			array_push ($a, $octets[$i]);
		}
		array_push  ($a, $arap );
		$domain = implode (".", $a);
		$_name = '';
		$a = array ();
		for ($i=$octcount-11; $i>=0; $i--) {
			array_push ($a, $octets[$i]);
		}
		$_name  = implode (".", $a);
            } else {
                $arpa = 'in-addr.arpa';
                $octcount = 3;
		$domain = $octets[2] . "." . $octets[3] . "." . $arpa;
		$_name = $octets[0] . "." . $octets[1];
            }
            // Find a pointer zone for this record to associate with.
	   echo " Searching for $domain \n";
       list($status, $prows, $ptrdomain) = ona_find_domain($domain);
	   echo "   => Found for $domain => " . $ptrdomain['id'] . " " . $ptrdomain['name'] ."\n" ;

	    // print_r ( $ptrdomain );
    // CRAPPY security cludge
    $_SESSION['ona']['auth']['user']['username'] = 'PTRFIX';
    $_SESSION['ona']['auth']['perms']['advanced'] = 'Y';
    $_SESSION['ona']['auth']['perms']['host_modify'] = 'Y';

    if (!$ptrdomain['id'] or ($domain != $ptrdomain['name']) ) {
        echo "  {$interface['ip_addr_text']}: Unable to find a pointer domain for this IP! Creating the following DNS domain: {$domain} \n";
        list($status, $output) = run_module('domain_add', array('name' => $domain));
        if ($status) {
            echo "ERROR => {$output}\n";
            exit($status);
        }
        list($status, $rows, $ptrdomain) = ona_find_domain($domain);
    }

    // Found a domain to put them in.
    echo "  Updating PTR for IP {$interface['ip_addr_text']} to $ipflip.in-addr.arpa\n";

    // Change the actual DNS record
    list($status, $rows) = db_update_record($onadb, 'dns', array('id' => $ptr['id']), array( 'name' => $_name, 'domain_id' => $ptrdomain['id']  ));
    // if ($status or !$rows) {
    //    echo "ERROR => SQL Query failed updating dns record: " . $self['error'] . " $status \n" ;
    //    exit(2);
    // }


}

list($status, $rows, $interfaces) = db_get_records($onadb, 'interfaces', "ip_addr is not null", '');
foreach ($interfaces as $interface ) {
    echo "  Adding inet6_atoi field  " . $interface['id'] . "\n";
    list($status, $rows) = db_update_record($onadb, 'interfaces', array('id' => $interface['id']), array ('ip_addr_inet' => inet_format($interface['ip_addr'])  ));

}

exit(0);

?>

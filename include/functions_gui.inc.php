<?

//
// GUI Specific Functions
// Not included for DCM modules
//

// FIXME: This should be done where all the other user's prefs are loaded
// The other half of this is in search_results.inc.php
// if (is_numeric($_SESSION['search_results_per_page'])) $conf['search_results_per_page'] = $_SESSION['search_results_per_page'];

// Register ONA specific functions with xajax
// (set the function names in a variable so they'll be processed later)
// Note that these functions must be already defined!
// $xajax->registerFunction("your_function");






//////////////////////////////////////////////////////////////////////////////
// Calculates the percentage of a subnet that is in "use".
// Returns a three part list:
//    list($percentage_used, $number_used, $number_total)
//////////////////////////////////////////////////////////////////////////////
function get_subnet_usage($subnet_id) {
    global $conf, $self, $onadb;

    list($status, $rows, $subnet) = db_get_record($onadb, 'subnets', array('id' => $subnet_id));
    if ($status or !$rows) { return(0); }
    $subnet['size'] = (0xffffffff - ip_mangle($subnet['ip_mask'], 'numeric')) - 1;

    // Calculate the percentage used (total size - allocated hosts - dhcp pool size)
    list($status, $hosts, $tmp) = db_get_records($onadb, 'interfaces', array('subnet_id' => $subnet['id']), "", 0);
    //list($status, $rows, $pools) = db_get_records($onadb, 'DHCP_POOL_B', array('subnet_id' => $subnet['id']));
    $pool_size = 0;
  //  foreach ($pools as $pool) {
  //      $pool_size += ($pool['IP_ADDRESS_END'] - $pool['ip_addr_start'] + 1);
  //  }
    $total_used = $hosts + $pool_size;
    $percentage = sprintf('%d', ($total_used / $subnet['size']) * 100);
    return(array($percentage, $total_used, $subnet['size']));
}





//////////////////////////////////////////////////////////////////////////////
// Returns the html for a "percentage of subnet used" bar graph
//////////////////////////////////////////////////////////////////////////////
function get_subnet_usage_html($subnet_id, $width=30, $height=8) {
    global $conf, $self, $mysql, $onadb;
    list($usage, $used, $total) = get_subnet_usage($subnet_id);
    $css='';
    if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') != false)
        $css = "font-size: " . ($height - 2) . "px;";
    return(<<<EOL
    <div style="white-space: nowrap; width: 100%; text-align: left; padding-top: 2px; padding-bottom: 2px; vertical-align: middle; font-size: 8px;">
        <div title="{$usage}% used" style="{$css} float: left; width: {$width}px; height: {$height}px; text-align: left; vertical-align: middle; background-color: #ABFFBC; border: 1px solid #000;">
            <div style="{$css} width: {$usage}%; height: {$height}px; vertical-align: middle; background-color: #FF3939;"></div>
        </div>
        <span style="font-size: 8px;">&nbsp;{$used} / {$total}</span>
    </div>
EOL
);
}







// Lookup hostnames and check their host_id is in server_b
function get_server_suggestions($q, $max_results=10) {
    global $onadb, $self, $conf;
    $results = array();

    // wildcard the query before searching
    $q = $q . '%';

    $table = 'hosts';
    $field = 'name'; // FIXME: (PK) name is no longer in hosts table ... its in dns table.
    $where  = "{$field} LIKE " . $onadb->qstr($q) . " AND id IN (SELECT host_id FROM SERVER_B)";
    $order  = "{$field} ASC";

    // Search the db for results
    list ($status, $rows, $records) = db_get_records(
                                        $onadb,
                                        $table,
                                        $where,
                                        $order,
                                        $max_results
                                      );

    // If the query didn't work return the error message
    if ($status) { $results[] = "Internal Error: {$self['error']}"; }

    foreach ($records as $record) {
        list($status, $rows, $domain) = db_get_record($onadb, 'dns', array('id' => $record['domain_id']));
        $results[] = $record[$field].".".$domain['fqdn'];
    }

    // Return the records
    return($results);
}


function get_host_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'dns', 'name', $max_results));
}

function get_host_notes_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'hosts', 'notes', $max_results));
}

function get_alias_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'HOST_ALIASES_B', 'ALIAS', $max_results));
}

function get_domain_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'domains', 'name', $max_results));
}

function get_vlan_campus_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'vlan_campuses', 'name', $max_results));
}

function get_block_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'blocks', 'name', $max_results));
}

function get_subnet_suggestions($q, $max_results=10) {
    $q = strtoupper($q);
    return(get_text_suggestions($q . '%', 'subnets', 'name', $max_results));
}

function get_location_number_suggestions($q, $max_results=10) {
    return(get_text_suggestions($q . '%', 'locations', 'reference', $max_results));
}

function get_mac_suggestions($q, $max_results=10) {
    $formatted = $results = array();

    $q = strtoupper($q);
    //if (preg_match('/[^\%\:\.\-0-9A-F]/', $q)) return(array()); // It's not a mac address ;)
    $q = preg_replace('/[\:\.\-]/', '', $q);  // Discard characters that aren't stored in the db

    $results = get_text_suggestions($q . '%', 'interfaces', 'mac_addr', $max_results);
    foreach($results as $result) $formatted[] = mac_mangle($result, 2);
    return($formatted);
}



function get_ip_suggestions($q, $max_results=10) {
    global $onadb;
    $formatted = $results = array();

    // Complete the (potentially incomplete) ip address
    $ip = ip_complete($q, '0');
    $ip_end = ip_complete($q, '255');

    // Find out if $ip and $ip_end are valid
    $ip = ip_mangle($ip, 'numeric');
    $ip_end = ip_mangle($ip_end, 'numeric');
    if ($ip == -1 or $ip_end == -1) { return(array()); } // It's not valid ip addresses

    // Now use SQL to look for subnet ip records that match
    $table = 'subnets';
    $field = 'ip_addr';
    $where  = "{$field} >= " . $onadb->qstr($ip) . " AND {$field} <= " . $onadb->qstr($ip_end);
    $order  = "{$field} ASC";

    // Search the db for results and put results into $results
    list ($status, $rows, $records) = db_get_records($onadb, $table, $where, $order, $max_results);
    foreach ($records as $record) { $results[] = $record[$field]; }

    // If we need more suggestions, look in the host_subnets table
    $max_results -= count($results);
    if ($max_results) {
        $table = 'interfaces';
        list ($status, $rows, $records) = db_get_records($onadb, $table, $where, $order, $max_results);
        foreach ($records as $record) { $results[] = $record[$field]; }
    }

    // Format the ip's in dotted format
    sort($results);
    foreach($results as $result) { $formatted[] = ip_mangle($result, 'dotted'); }

    unset($results, $result, $records, $record);
    return($formatted);
}






//////////////////////////////////////////////////////////////////////////////
// xajax server for suggest_qsearch()
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_qsearch($q, $el_input, $el_suggest) {
    global $conf, $images;
    $results = array();

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Command intrepreter
    if (strpos($q, '/') === 0) {
        $js .= "suggestions = Array('Enter a command...');";
        $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
        $response->addScript($js);
        return($response->getXML());
    }

    // Search the DB for ip addressees
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_ip_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for hosts
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_host_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for aliases
 //   if (count($results) < $conf['suggest_max_results']) {
 //       $array = get_alias_suggestions($q, $conf['suggest_max_results'] - count($results));
 //       foreach($array as $suggestion) { $results[] = $suggestion; }
 //       $results = array_unique($results);
  //  }

    // Search the DB for subnets
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_subnet_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for hosts (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_host_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for aliases (*)
//    if (count($results) < $conf['suggest_max_results']) {
//        $array = get_alias_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
//        foreach($array as $suggestion) { $results[] = $suggestion; }
//        $results = array_unique($results);
//    }

    // Search the DB for subnets (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_subnet_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for mac addressees
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_mac_suggestions($q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }

    // Search the DB for mac addressees (*)
    if (count($results) < $conf['suggest_max_results']) {
        $array = get_mac_suggestions('%' . $q, $conf['suggest_max_results'] - count($results));
        foreach($array as $suggestion) { $results[] = $suggestion; }
        $results = array_unique($results);
    }


    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_hostname($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_host_suggestions($q);
    $results = array_merge($results, get_host_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
//FIXME: (PK) this function is redundant.  Can we replace it with a call directly
//       to suggest_hostname()?
function suggest_failover_pri_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
//FIXME: (PK) this function is redundant.  Can we replace it with a call directly
//       to suggest_hostname()?
function suggest_failover_sec_hostname($q, $el_input, $el_suggest) {
    return(suggest_hostname($q, $el_input, $el_suggest));
}

//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_server($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_server_suggestions($q);
    $results = array_merge($results, get_server_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    //TODO: potentialy add a search for get_domain_suggestions here

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}

function suggest_pool_server_qf($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}
function suggest_domain_server_name($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}
function suggest_dhcp_server_name($q, $el_input, $el_suggest) {
    return(suggest_server($q, $el_input, $el_suggest));
}



//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_domain($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_domain_suggestions($q);
    $results = array_merge($results, get_domain_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_domain_alias_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_domain_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_set_domain_edit_host($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}
function suggest_domain_server_edit($q, $el_input, $el_suggest) {
    return(suggest_domain($q, $el_input, $el_suggest));
}





//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_notes($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_host_notes_suggestions($q, $conf['suggest_max_results']);
    $results = array_merge($results, get_host_notes_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}






//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_mac($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_mac_suggestions($q);
    $results = array_merge($results, get_mac_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}








//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function that is part of the
// xajax_suggest module.
//////////////////////////////////////////////////////////////////////////////
function suggest_ip($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_ip_suggestions($q);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
// The following are just wrappers around suggest_ip();
function suggest_ip_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_qf($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_ip_subnet_thru_qf($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_block_ip_subnet($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_block_ip_subnet_thru($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}
function suggest_set_ip_edit_interface($q, $el_input, $el_suggest) {
    return(suggest_ip($q, $el_input, $el_suggest));
}




//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_vlan_campus($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_vlan_campus_suggestions($q);
    $results = array_merge($results, get_vlan_campus_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_vlan_edit($q, $el_input, $el_suggest) {
    return(suggest_vlan_campus($q, $el_input, $el_suggest));
}
function suggest_vlan_campus_qf($q, $el_input, $el_suggest) {
    return(suggest_vlan_campus($q, $el_input, $el_suggest));
}






//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_block($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_block_suggestions($q);
    $results = array_merge($results, get_block_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}







//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_subnet($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_subnet_suggestions($q);
    $results = array_merge($results, get_subnet_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
function suggest_set_subnet_edit_interface($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_set_subnet_edit_host($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_subnet_qf($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}
function suggest_dhcp_subnet_name($q, $el_input, $el_suggest) {
    return(suggest_subnet($q, $el_input, $el_suggest));
}


//////////////////////////////////////////////////////////////////////////////
// xajax server
// This function is called by the suggest() function.
//////////////////////////////////////////////////////////////////////////////
function suggest_location_number($q, $el_input, $el_suggest) {
    global $conf;

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    $js = "";

    // Search the DB
    $results = get_location_number_suggestions($q);
    $results = array_merge($results, get_location_number_suggestions('%'.$q, $conf['suggest_max_results'] - count($results)));
    $results = array_unique($results);

    // Build the javascript to return
    $js .= "suggestions = Array(";
    $comma = "";
    foreach ($results as $suggestion) {
        $suggestion = str_replace("'", "\\'", $suggestion);
        $js .= $comma . "'{$suggestion}'";
        if (!$comma) { $comma = ", "; }
    }
    $js .= ");";

    // Tell the browser to execute the javascript in $js by sending an XML response
    $js .= "suggest_display('{$el_input}', '{$el_suggest}');";
    $response->addScript($js);
    return($response->getXML());
}
// Used in all QF (tool-tip based) search boxes
function suggest_location_number_qf($q, $el_input, $el_suggest) {
    return(suggest_location_number($q, $el_input, $el_suggest));
}
// Advanced search subnet tab
function suggest_location_number_subnet($q, $el_input, $el_suggest) {
    return(suggest_location_number($q, $el_input, $el_suggest));
}





?>

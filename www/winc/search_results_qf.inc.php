<?php



//////////////////////////////////////////////////////////////////////////////
// Function:
//     subnet ($window_name, $form_id)
//
// Description:
//     Does a quick subnet search and puts the results in the div specified
//     in $form['content_id'].  At most 250 results will be returned unless
//     overridden in $form['max_results'].
//     When a search result is clicked on, it's value will be put into the
//     input element specified in $form['input_id'] and the quick filter
//     popup window identified by $form['id'] is removed from the dom.
//////////////////////////////////////////////////////////////////////////////
function ws_subnet($window_name, $form='') {
    global $conf, $self, $onadb;
    global $color, $style, $images;
    $html = $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Set max_results and max_usage_bars if we need to
    if (!is_numeric($form['max_results']))
        $form['max_results'] = 250;
    if (!is_numeric($form['max_usage_bars']))
        $form['max_usage_bars'] = 50;




    //
    // *** QF SUBNET SEARCH ***
    //

    // Search results go in here
    $results = array();
    $count = 0;

    // Start building the "where" clause for the sql query to find the subnets to display
    $where = "";
    $and = "";

    // SUBNET TYPE
    if ($form['nettype']) {
        $where .= $and . "subnet_type_id = " . $onadb->qstr($form['nettype']);
        $and = " AND ";
    }

    // SUBNET DESCRIPTION
    if ($form['netdesc']) {
        // This field is always upper case
        $form['netdesc'] = strtoupper($form['netdesc']);
        $where .= $and . "name LIKE " . $onadb->qstr('%'.$form['netdesc'].'%');
        $and = " AND ";
    }

    // IP ADDRESS
    if ($form['ip_subnet']) {
        // Build $ip and $ip_end from $form['ip_subnet'] and $form['ip_subnet_thru']
        $ip = ip_complete($form['ip_subnet'], '0');
        if ($form['ip_subnet_thru']) { $ip_end = ip_complete($form['ip_subnet_thru'], '255'); }
        else { $ip_end = ip_complete($form['ip_subnet'], '255'); }

        // Find out if $ip and $ip_end are valid
        $ip = ip_mangle($ip, 'numeric');
        $ip_end = ip_mangle($ip_end, 'numeric');
        if ($ip != -1 and $ip_end != -1) {
            // Find subnets between the specified ranges
            $where .= "ip_addr >= " . $onadb->qstr($ip) . " AND ip_addr <= " . $onadb->qstr($ip_end);
        }
    }


    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        // Subnet descriptions are always upper case
        $form['filter'] = strtoupper($form['filter']);
        $filter = $and . ' name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'subnets',
            $where . $filter,
            "ip_addr ASC",
            $form['max_results']
        );

    // If there were more than $form['max_results'] find out how many records there really are
    if ($rows >= $form['max_results']) {
        list ($status, $rows, $records) =
            db_get_records(
                $onadb,
                'subnets',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;


    //
    // *** BUTILD RESULTS HTML ***
    //

        $html .= <<<EOL
<table style="cursor: pointer;" width="100%" cellspacing="0" border="0" cellpadding="0">
EOL;
    if ($count > $form['max_results']) {
        $html .= <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Displaying {$form['max_results']} of {$count} results</td></tr>
EOL;
    }

    if ($count > $form['max_usage_bars']) {
        $too_many_bars_message = <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Usage graph only displayed for the first {$form['max_usage_bars']} subnets</td></tr>
EOL;
    }

    $i = 0;
    foreach ($results as $record) {
        $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');

        // Calculate the percentage of the subnet that's used (total size - allocated hosts - dhcp pool size)
        if ($i++ <= $form['max_usage_bars'])
            $usage_html = get_subnet_usage_html($record['id']);
        else if ($i == $form['max_usage_bars'] + 2) {
            $html .= $too_many_bars_message;
            $usage_html = '&nbsp;';
        }
        else $usage_html = '&nbsp;';

        // The onClick javascript is slightly different if we're looking for a subnet, or a free IP address
        if ($form['next_action'] == 'free_ip')
            $onclick = "el('qf_free_ip_subnet_id').value = '{$record['id']}'; xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'free_ip');";
        else
            $onclick = "el('{$form['input_id']}').value = '{$record['name']}'; removeElement('{$form['id']}');";

        $html .= <<<EOL
<tr onMouseOver="this.className='row-highlight';"
    onMouseOut="this.className='row-normal';"
    onClick="{$onclick}">
    <td style="font-size: 10px; padding: 0px 2px;">{$record['ip_addr']}</td>
    <td style="font-size: 10px; padding: 0px 2px;">{$usage_html}</td>
    <td style="font-size: 10px; padding: 0px 2px;">{$record['name']}</td>
</tr>
EOL;
    }

        $html .= <<<EOL
</table>
EOL;

    $js .= <<<EOL
        el('{$form['content_id']}').style.display = 'block';
        /* Reposition the popup if the new content is displayed off the screen */
        wwTT_position('{$form['id']}');
EOL;

if ($count == 1) {
    $html = "";
    $js .= "el('qf_free_ip_subnet_id').value = '{$record['id']}'; xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'free_ip');";
}
    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}















//////////////////////////////////////////////////////////////////////////////
// Function:
//     free_ip ($window_name, $form_id)
//
// Description:
//     Does a quick search for availble IP addresses and puts the results
//     in the div specified in $form['content_id'].  At most 250 results will
//     be returned unless overridden in $form['max_results'].
//     When a search result is clicked on, it's value will be put into the
//     input element specified in $form['input_id'] and the quick filter
//     popup window identified by $form['id'] is removed from the dom.
//////////////////////////////////////////////////////////////////////////////
function ws_free_ip($window_name, $form='') {
    global $conf, $self, $onadb;
    global $color, $style, $images;
    $html = $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Set max_results and max_usage_bars if we need to
    if (!is_numeric($form['max_results']))
        $form['max_results'] = 512;



    //
    // *** QF AVAILBLE IP ADDRESS SEARCH ***
    //


    // Get the specified subnet record by id
    list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $form['subnet_id']));
    if ($status or !$rows) {
        // Send a javascript popup error
        $response = new xajaxResponse();
        $response->addScript("alert('ERROR => Invalid subnet selected!');");
        return($response->getXML());
    }

    // Get a list of interfaces on the selected subnet
    list($status, $rows, $interfaces) =
        db_get_records($onadb, 'interfaces', array('subnet_id' => $subnet['id']));

    // Transform that list into a simple array of ip addresses
    // NOTE: you must use a string as the used_ips array element as integers will get too large
    $used_ips = array();
    foreach ($interfaces as $interface) {
        $used_ips["{$interface['ip_addr']}"] = $interface['host_id'];
    }
    unset($interfaces, $interface);

    // Get a list of dhcp pools on the selected subnet
    list($status, $rows, $pools) =
        db_get_records($onadb, 'dhcp_pools', array('subnet_id' => $subnet['id']));

    // Add DHCP pool addresses into the list of used ips
    foreach ($pools as $pool)
        for ($ip = $pool['ip_addr_start']; $ip <= $pool['ip_addr_end']; $ip++)
            $used_ips["{$ip}"] = $pool['id'];

    // Create a few variables that will be handy later
    if(strlen($subnet['ip_addr']) < 11)  {
       // echo "ipv4";
       $num_ips = 0xffffffff - $subnet['ip_mask'];
       if ($subnet['ip_mask'] < 4294967294) {
          $last_ip = ($subnet['ip_addr'] + $num_ips) - 1;
       } else {
          $last_ip = ($subnet['ip_addr'] + $num_ips);
       }
    } else {
       // echo "ipv6";
       $sub = gmp_sub("340282366920938463463374607431768211455", $subnet['ip_mask']);
       $num_ips = gmp_strval($sub); 
       $last_ip = ($subnet['ip_addr'] + $num_ips) - 1;
    }


    // Search results go in here
    $results = array();
    $allused = count($used_ips);
    $count = gmp_strval(gmp_sub($num_ips, $allused));

    // Create a list of available IP's
    if ($subnet['ip_mask'] = 4294967296) {
      $ip = $subnet['ip_addr'];
    } else {
      $ip = gmp_strval(gmp_add($subnet['ip_addr'],'1')); 
    }
    while ($ip <= $last_ip and count($results) <= $form['max_results']) {
        if (!array_key_exists("{$ip}", $used_ips))
            $results[] = $ip;
        $plusone = gmp_add("1", $ip);
        $ip = gmp_strval($plusone); 
    }


    //
    // *** BUTILD RESULTS HTML ***
    //

    $html .= <<<EOL
<table style="cursor: pointer;" width="100%" cellspacing="0" border="0" cellpadding="0">
EOL;
    if ($count > $form['max_results']) {
    $html .= <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Displaying {$form['max_results']} of {$count} results</td></tr>
EOL;
    }

    if ($count > $form['max_usage_bars']) {
        $too_many_bars_message = <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Usage graph only displayed for the first {$form['max_usage_bars']} subnets</td></tr>
EOL;
    }

    $subnet['name'] = htmlentities($subnet['name'], ENT_QUOTES, $conf['php_charset']);
    foreach ($results as $ip) {
        if (strlen($ip) > 11) {
            $ip = ip_mangle($ip, 'ipv6gz');
        } else {
            $ip = ip_mangle($ip, 'dotted');
        }
        $html .= <<<EOL
<tr onMouseOver="this.className='row-highlight';"
    onMouseOut="this.className='row-normal';"
    onClick="el('{$form['input_id']}').value = '{$ip}'; el('{$form['text_id']}').innerHTML = '{$subnet['name']}'; removeElement('{$form['id']}');">
    <td style="font-size: 10px; padding: 0px 2px;">{$ip}</td>
</tr>
EOL;
    }

        $html .= <<<EOL
</table>
EOL;

    $js .= <<<EOL
        el('{$form['content_id']}').style.display = 'block';
        /* Reposition the popup if the new content is displayed off the screen */
        wwTT_position('{$form['id']}');
EOL;


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}
















//////////////////////////////////////////////////////////////////////////////
// Function:
//     location ($window_name, $form_id)
//
// Description:
//     Does a quick location search and puts the results in the div specified
//     in $form['content_id'].  At most 250 results will be returned unless
//     overridden in $form['max_results'].
//     When a search result is clicked on, it's value will be put into the
//     input element specified in $form['input_id'] and the quick filter
//     popup window identified by $form['id'] is removed from the dom.
//////////////////////////////////////////////////////////////////////////////
function ws_location($window_name, $form='') {
    global $conf, $self, $onadb;
    global $color, $style, $images;
    $html = $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Set max_results and max_usage_bars if we need to
    if (!is_numeric($form['max_results']))
        $form['max_results'] = 250;



    //
    // *** QF LOCATION SEARCH ***
    //

    // Search results go in here
    $results = array();
    $count = 0;

    // Start building the "where" clause for the sql query to find the subnets to display
    $where = "";
    $and = "";

    // LOCATION REFERENCE
    if ($form['reference']) {
        // This field is always upper case
        $form['reference'] = strtoupper($form['reference']);
        $where .= $and . "reference LIKE " . $onadb->qstr('%'.$form['reference'].'%');
        $and = " AND ";
    }

    // LOCATION NAME
    if ($form['name']) {
        // This field is always upper case
        $where .= $and . "name LIKE " . $onadb->qstr('%'.$form['name'].'%');
        $and = " AND ";
    }

    // LOCATION ADDRESS
    if ($form['address']) {
        $where .= $and . "address LIKE " . $onadb->qstr('%'.$form['address'].'%');
        $and = " AND ";
    }

    // CITY
    if ($form['city']) {
        // This field is always upper case
        $form['city'] = strtoupper($form['city']);
        $where .= $and . "city LIKE " . $onadb->qstr('%'.$form['city'].'%');
        $and = " AND ";
    }

    // STATE
    if ($form['state']) {
        // This field is always upper case
        $form['state'] = strtoupper($form['state']);
        $where .= $and . "state = " . $onadb->qstr($form['state']);
        $and = " AND ";
    }

    // ZIP CODE
    if ($form['zip']) {
        $where .= $and . "zip_code = " . $onadb->qstr($form['zip']);
        $and = " AND ";
    }

    // Wild card .. if $while is still empty, add a 'ID > 0' to it so you see everything.
    if ($where == '')
        $where = 'id > 0';


    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        $form['filter'] = strtoupper($form['filter']);
        $filter = $and . ' name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'locations',
            $where . $filter,
            "reference ASC",
            $form['max_results']
        );

    // If there were more than $form['max_results'] find out how many records there really are
    if ($rows >= $form['max_results']) {
        list ($status, $rows, $records) =
            db_get_records(
                $onadb,
                'locations',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;


    //
    // *** BUILD RESULTS HTML ***
    //

        $html .= <<<EOL
<table style="cursor: pointer;" width="100%" cellspacing="0" border="0" cellpadding="0">
EOL;
    if ($count > $form['max_results']) {
        $html .= <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Displaying {$form['max_results']} of {$count} results</td></tr>
EOL;
    }

    foreach ($results as $record) {
        $html .= <<<EOL
<tr onMouseOver="this.className='row-highlight';"
    onMouseOut="this.className='row-normal';"
    onClick="el('{$form['input_id']}').value = '{$record['reference']}'; removeElement('{$form['id']}');"
    title="{$record['name']}">
    <td style="font-size: 10px; padding: 0px 2px;">{$record['reference']}</td>
    <td style="font-size: 10px; padding: 0px 2px;">{$record['address']}</td>
    <td style="font-size: 10px; padding: 0px 2px;">{$record['city']}, {$record['state']}</td>
</tr>
EOL;
    }

        $html .= <<<EOL
</table>
EOL;

    $js .= <<<EOL
        el('{$form['content_id']}').style.display = 'block';
        /* Reposition the popup if the new content is displayed off the screen */
        wwTT_position('{$form['id']}');
EOL;


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}












//////////////////////////////////////////////////////////////////////////////
// Function:
//     vlan ($window_name, $form_id)
//
// Description:
//     Does a quick vlan search and puts the results in the div specified
//     in $form['content_id'].  At most 250 results will be returned unless
//     overridden in $form['max_results'].
//     When a search result is clicked on, it's value will be put into the
//     input element specified in $form['input_id'],  the text in the
//     element specified in $form['text_id'] will be updated, and the quick
//     filter popup window identified by $form['id'] is removed from the dom.
//////////////////////////////////////////////////////////////////////////////
function ws_vlan($window_name, $form='') {
    global $conf, $self, $onadb;
    global $color, $style, $images;
    $html = $js = '';

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Set max_results and max_usage_bars if we need to
    if (!is_numeric($form['max_results']))
        $form['max_results'] = 250;



    //
    // *** QF VLAN SEARCH ***
    //

    // Get the specified VLAN Campus record by name
    // If it isn't exact, don't accept it.
    list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $form['campus']));
    if ($status or !$rows) {
        // Send a javascript popup error
        $response = new xajaxResponse();
        $response->addScript("alert('ERROR => Invalid VLAN campus!');");
        return($response->getXML());
    }


    // Get a list of vlan's in the selected campus
    list($status, $count, $results) =
        db_get_records(
            $onadb,
            'vlans',
            array('vlan_campus_id' => $campus['id']),
            'number',
            $form['max_results']);

    // If there were more than $form['max_results'] find out how many records there really are
    if ($count >= $form['max_results']) {
        list ($status, $count, $tmp) =
            db_get_records(
                $onadb,
                'vlans',
                array('vlan_campus_id' => $campus['id']),
                '',
                0
            );
    }


    //
    // *** BUILD RESULTS HTML ***
    //

        $html .= <<<EOL
<table style="cursor: pointer;" width="100%" cellspacing="0" border="0" cellpadding="0">
    <!-- The "None" option.. they need to be able to de-select a vlan -->
    <tr onMouseOver="this.className='row-highlight';"
        onMouseOut="this.className='row-normal';"
        onClick="el('{$form['input_id']}').value = ''; el('{$form['text_id']}').innerHTML = 'None'; removeElement('{$form['id']}');">
        <td style="font-size: 10px; padding: 0px 2px;">None (i.e. no vlan)</td>
    </tr>
EOL;
    if ($count > $form['max_results']) {
        $html .= <<<EOL
<tr><td style="cursor: default; font-size: 12px; background-color: #FFCCCC; color: 000;" colspan="5" align="center">Displaying {$form['max_results']} of {$count} results</td></tr>
EOL;
    }

    $campus['name'] = htmlentities($campus['name'], ENT_QUOTES, $conf['php_charset']);
    foreach ($results as $record) {
        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }
        $html .= <<<EOL
<tr onMouseOver="this.className='row-highlight';"
    onMouseOut="this.className='row-normal';"
    onClick="el('{$form['input_id']}').value = '{$record['number']}'; el('{$form['text_id']}').innerHTML = '{$campus['name']} / {$record['name']}'; removeElement('{$form['id']}');">
    <td style="font-size: 10px; padding: 0px 2px;">{$campus['name']} / [{$record['number']}] {$record['name']}</td>
</tr>
EOL;
    }

        $html .= <<<EOL
</table>
EOL;

    $js .= <<<EOL
        el('{$form['content_id']}').style.display = 'block';
        /* Reposition the popup if the new content is displayed off the screen */
        wwTT_position('{$form['id']}');
EOL;


    // Insert the new html into the window
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}












?>

<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of hosts based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find the "tab" we're on
    $tab = $_SESSION['ona'][$form['form_id']]['tab'];

    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";

    // If it's not a new query, load the previous query from the session
    // into $form and save the current page and filter in the session.
    // Also find/set the "page" we're viewing
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) {
        //$form = array_merge($form, $_SESSION['ona'][$form['form_id']][$tab]['q']);
        $_SESSION['ona'][$form['form_id']][$tab]['page'] = $page = $form['page'];
        $_SESSION['ona'][$form['form_id']][$tab]['filter'] = $form['filter'];
    }

    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Search results go in here
    $results = array();
    $count = 0;




    //
    // *** ADVANCED HOST SEARCH ***
    //       FIND RESULT SET
    //

    // Start building the "where" clause for the sql query to find the hosts to display
    $where = "";
    $and = "";
    $orderby = "";

    // HOST_ID
    if ($form['host_id']) {
        $where .= $and . "id = " . $onadb->qstr($form['host_id']);
        $and = " AND ";
    }


    // HOSTNAME
    $aliases = 0;
    if ($form['hostname']) {
        $where .= $and . "PRIMARY_DNS_NAME LIKE " . $onadb->qstr('%'.$form['hostname'].'%');
        $and = " AND ";

        // ALIAS NAME
        list($status, $aliases, $tmp) = db_get_records($onadb, 'HOST_ALIASES_B', 'ALIAS LIKE ' . $onadb->qstr('%'.$form['hostname'].'%'), '', 0);
    }


    // ZONE
    if ($form['zone']) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "PRIMARY_DNS_ZONE_ID IN ( SELECT ID " .
                                                "  FROM DNS_ZONES_B " .
                                                "  WHERE ZONE_NAME LIKE " . $onadb->qstr($form['zone'].'%') . " ) ";
        $and = " AND ";
    }

    // ZONE ID
    if ($form['zone_id']) {
        $where .= $and . "PRIMARY_DNS_ZONE_ID = " . $onadb->qstr($form['zone_id']);
        $and = " AND ";
    }

    // SUBNET ID
    if (is_numeric($form['subnet_id'])) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "id IN ( SELECT host_id " .
                         "        FROM interfaces " .
                         "        WHERE subnet_id = " . $form['subnet_id'] . " ) ";
        $and = " AND ";
   // $js .= "alert('Where: " . str_replace("'", '"', $status) . "');";

    }


    // MAC
    if ($form['mac']) {
        // Clean up the mac address
        $form['mac'] = strtoupper($form['mac']);
        $form['mac'] = preg_replace('/[^%0-9A-F]/', '', $form['mac']);

        // We do a sub-select to find interface id's that match
        $where .= $and . "ID IN ( SELECT HOST_ID " .
                         "        FROM HOST_SUBNETS_B " .
                         "        WHERE DATA_LINK_ADDRESS LIKE " . $onadb->qstr('%'.$form['mac'].'%') . " ) ";
        $and = " AND ";

    }


    // IP ADDRESS
    if ($form['ip']) {
        // Build $ip and $ip_end from $form['ip'] and $form['ip_thru']
        $ip = ip_complete($form['ip'], '0');
        if ($form['ip_thru']) { $ip_end = ip_complete($form['ip_thru'], '255'); }
        else { $ip_end = ip_complete($form['ip'], '255'); }

        // Find out if $ip and $ip_end are valid
        $ip = ip_mangle($ip, 'numeric');
        $ip_end = ip_mangle($ip_end, 'numeric');
        if ($ip != -1 and $ip_end != -1) {
            // We do a sub-select to find interface id's between the specified ranges
            $where .= $and . "ID IN ( SELECT HOST_ID " .
                             "        FROM HOST_SUBNETS_B " .
                             "        WHERE IP_ADDRESS >= " . $onadb->qstr($ip) . " AND IP_ADDRESS <= " . $onadb->qstr($ip_end) . " )";
            $and = " AND ";
        }
    }


    // NOTES
    if ($form['notes']) {
        $where .= $and . "NOTES LIKE " . $onadb->qstr('%'.$form['notes'].'%');
        $and = " AND ";
    }




    // DEVICE MODEL
    if ($form['model']) {
        $where .= $and . "DEVICE_MODEL_ID = " . $onadb->qstr($form['model']);
        $and = " AND ";
    }


    // DEVICE TYPE
    if ($form['dev_type']) {
        // Find model_id's that have a device_type_id of $form['type']
        list($status, $rows, $records) =
            db_get_records($onadb,
                           'DEVICE_MODELS_B',
                           array('DEVICE_TYPE_ID' => $form['dev_type'])
                          );
        // If there were results, add each one to the $where clause
        if ($rows > 0) {
            $where .= $and . " ( ";
            $and = " AND ";
            $or = "";
            foreach ($records as $record) {
                $where .= $or . "DEVICE_MODEL_ID = " . $onadb->qstr($record['ID']);
                $or = " OR ";
            }
            $where .= " ) ";
        }
    }


    // DEVICE MANUFACTURER
    if ($form['manufacturer']) {
        // Find model_id's that have a device_type_id of $form['type']
        list($status, $rows, $records) =
            db_get_records($onadb,
                           'DEVICE_MODELS_B',
                           array('MANUFACTURER_ID' => $form['manufacturer'])
                          );
        // If there were results, add each one to the $where clause
        if ($rows > 0) {
            $where .= $and . " ( ";
            $and = " AND ";
            $or = "";
            foreach ($records as $record) {
                $where .= $or . "DEVICE_MODEL_ID = " . $onadb->qstr($record['ID']);
                $or = " OR ";
            }
            $where .= " ) ";
        }
    }


    // LOCATION No.
    if ($form['location']) {
        $where .= $and . "location_id = " . $onadb->qstr($form['location']);
        $and = " AND ";
    }





    // HIJACK!!!
    // If $form['type'] == aliases, jump to that function
    if ($form['type'] == 'aliases') {
        $form['host_where'] = $where;
        return(ws_display_alias_list($window_name, $form));
    }


    // Wild card .. if $while is still empty, add a 'ID > 0' to it so you see everything.
    if ($where == '')
        $where = 'id > 0';


    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {

        // Host names should always be lower case
        $form['filter'] = strtolower($form['filter']);
        $filter = ' AND PRIMARY_DNS_NAME LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'hosts',
            $where . $filter,
            $orderby,
            $conf['search_results_per_page'],
            $offset
        );

    // If we got less than serach_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $records) =
            db_get_records(
                $onadb,
                'hosts',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

   // $js .= "alert('Where: " . str_replace("'", '"', $status) . "');";





    //
    // *** BUILD HTML LIST ***
    //
    $html .= <<<EOL
        <!-- Host Results -->
        <table id="{$form['form_id']}_host_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Interface</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Device Model</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Location</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;
    // Loop and display each record
    foreach($results as $record) {
        // Get additional info about eash host record


        // If a subnet_id was passed use it as part of the search.  Used to display the IP of the subnet you searched
        if (is_numeric($form['subnet_id'])) {
            list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $record['id'], 'subnet_id' => $form['subnet_id']), '');

            // Count how many rows and assign it back to the interfaces variable
            list($status, $rows, $records) = db_get_records($onadb,
                                                            'interfaces',
                                                            'HOST_ID = '. $onadb->qstr($record['id']),
                                                            "",
                                                            0);

            $interfaces = $rows;

        } elseif (is_numeric($ip)) {
            list($status, $interfaces, $interface) = db_get_record($onadb,
                                                            'interfaces',
                                                            'HOST_ID = '. $onadb->qstr($record['id']) .
                                                            ' AND IP_ADDR >= ' . $onadb->qstr($ip) .
                                                            ' AND IP_ADDR <= ' . $onadb->qstr($ip_end),
                                                            "",
                                                            0);

            // Count how many rows and assign it back to the interfaces variable
            list($status, $rows, $records) = db_get_records($onadb,
                                                            'interfaces',
                                                            'HOST_ID = '. $onadb->qstr($record['id']),
                                                            "",
                                                            0);

            $interfaces = $rows;

        }  else {
            // Interface (and find out how many there are)
            list($status, $interfaces, $interface) = ona_get_interface_record(array('HOST_ID' => $record['id']), '');
        }

        if (!$interfaces) {$count -1; continue;}

        $record['IP_ADDR'] = ip_mangle($interface['ip_addr'], 'dotted');
        $interface_style = '';
        if ($interfaces > 1) {
            $interface_style = 'font-weight: bold;';
        }

        // DNS A record
        list($status, $rows, $dnsa) = ona_get_dns_a_record(array('id' => $record['primary_dns_a_id']));
        $record['dns_name'] = $dnsa['name'];

        // Domain Name
        list($status, $rows, $dnsdomain) = ona_get_dns_domain_record(array('id' => $dnsa['dns_domain_id']));
        $record['domain'] = $dnsdomain['ns_fqdn'];

        // Subnet description
        list($status, $rows, $subnet) = ona_get_subnet_record(array('ID' => $interface['subnet_id']));
        $record['SUBNET'] = $subnet['name'];
        $record['IP_MASK'] = ip_mangle($subnet['ip_mask'], 'dotted');
        $record['IP_MASK_CIDR'] = ip_mangle($subnet['ip_mask'], 'cidr');


        // Device Description
        list($status, $rows, $device) = ona_get_model_record(array('ID' => $record['model_id']));
        $record['device'] = "{$device['MANUFACTURER_NAME']}, {$device['model']}";
        $record['device'] = str_replace('Unknown', '?', $record['device']);


        $record['NOTES_SHORT'] = truncate($record['notes'], 40);

        // Get location_number from the location_id
        list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

        $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');";
        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">

                <td class="list-row">
                    <a title="View host. ID: {$record['id']}"
                       class="nav"
                       onClick="{$primary_object_js}"
                    >{$record['dns_name']}</a
                    >.<a title="View zone. ID: {$dnsdomain['id']}"
                         class="zone"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_zone\', \'zone_id=>{$dnsdomain['id']}\', \'display\')');"
                    >{$record['domain']}</a>
                </td>

                <td class="list-row">
                    <a title="View subnet. ID: {$subnet['id']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['SUBNET']}</a>&nbsp;
                </td>

                <td class="list-row" align="left">
                    <span style="{$interface_style}"
EOL;

if ($interfaces > 1) {
        $html .= <<<EOL
                          onMouseOver="wwTT(this, event,
                                            'id', 'tt_host_interface_list_{$record['id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>host_interface_list,id=>tt_host_interface_list_{$record['id']},host_id=>{$record['id']}\');'
                                           );"
EOL;
}
        $html .= <<<EOL
                    >{$record['IP_ADDR']}</span>&nbsp;
                    <span title="{$record['IP_MASK']}">/{$record['IP_MASK_CIDR']}</span>&nbsp;
                </td>

                <td class="list-row">{$record['device']}&nbsp;</td>

                <td class="list-row" align="right">
                    <span onMouseOver="wwTT(this, event,
                                            'id', 'tt_location_{$record['location_id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>location,id=>tt_location_{$record['location_id']},location_id=>{$record['location_id']}\');'
                                           );"
                    >{$location['reference']}</span>&nbsp;
                </td>

                <td class="list-row">
                    <span title="{$record['notes']}">{$record['NOTES_SHORT']}</span>&nbsp;
                </td>

                <!-- ACTION ICONS -->
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_host_{$record['ID']}"
                        ><input type="hidden" name="host_id" value="{$record['ID']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>&nbsp;
EOL;

        if (auth('host_modify',$debug_val)) {
            $html .= <<<EOL

                    <a title="Edit host"
                       class="act"
                       onClick="xajax_window_submit('edit_host', xajax.getFormValues('{$form['form_id']}_list_host_{$record['ID']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('host_del',$debug_val)) {
            $html .= <<<EOL

                    <a title="Delete host"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this host?');
                                if (doit == true)
                                    xajax_window_submit('edit_host', xajax.getFormValues('{$form['form_id']}_list_host_{$record['ID']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
        }
        $html .= <<<EOL
                    &nbsp;
                </td>

            </tr>
EOL;

    }



    if ($count == 0 and $form['subnet_id'] and !$form['filter']) {
        $html .= <<<EOL
     <tr><td colspan="99" align="center" style="color: red;">Please add the gateway host (router) to this subnet</td></tr>
EOL;
    }

    $html .= <<<EOL
    </table>
EOL;

    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);

    $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_host_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
            /* Hack to Make sure the other tables are 100% wide */
            if (el('list_dhcp_leases_filter_form_dhcp_lease_list'))
                el('list_dhcp_leases_filter_form_dhcp_lease_list').style.width = el('list_dhcp_leases_filter_form_table').offsetWidth + 'px';
            if (el('list_hosts_aliases_filter_form_alias_list'))
                el('list_hosts_aliases_filter_form_alias_list').style.width = el('list_hosts_aliases_filter_form_table').offsetWidth + 'px';
EOL;

    // If there was only 1 result, and we're about to display results in the "Search Results" window, display it.
    if ($count == 1 and $aliases == 0 and $form['content_id'] == 'search_results_list' and $form['filter'] == '')
        $js .= $primary_object_js;

    // Add a link to alias results if there were any
    if ($aliases > 0) {
        $phrase1 = 'There are also';
        $phrase2 = 'aliases';
        $phrase3 = 'that match your search query.';
        if ($aliases == 1) {
            $phrase1 = 'There is also';
            $phrase2 = 'alias';
            $phrase3 = 'that matches your search query.';
        }

        $html .= <<<EOL
    <!-- Alias Links -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" style="background-color: #FFFCD5; margin-bottom: 0.2em;">
        <tr>
            <td id="page_links" style="font-weight: bold; " class="padding" align="center">
                {$phrase1}
                <a title="Display aliases"
                   class="nav"
                   onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form['form_id']},type=>aliases', 'change_type');"
                >{$aliases} {$phrase2}</a>
                {$phrase3}
            </td>
        </tr>
    </table>
EOL;
    }

    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}








//////////////////////////////////////////////////////////////////////////////
// Function:
//     change_type (string $window_name, int $page)
//
// Description:
//     This function changes the "type" being displayed.  i.e. this is only
//     used by the list_hosts() file to determine wether to display hosts
//     or aliases.
//
//     $form NEEDS form_id => id && type => [hosts|aliases]
//////////////////////////////////////////////////////////////////////////////
function ws_change_type($window_name, $form) {
    global $conf, $self;

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = '';

    // Basically we're going to create a new hidden input field in the filter form
    // that tells display_list() that we'll be displaying aliases.
    // Then we just have the browser do an xajax callback to update the list being displayed.
    $response->addScript("removeElement('{$form['form_id']}_type');");
    $response->addCreateInput($form['form_id'], "hidden", "type", "{$form['form_id']}_type");
    $js .= "el('{$form['form_id']}_page').value = '1';";
    $js .= "el('{$form['form_id']}_type').value = '{$form['type']}';";
    $js .= "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_list');";

    // Send an XML response to the window
    $response->addScript($js);
    return($response->getXML());
}










//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_alias_list()
//
// Description:
//   Displays A list of aliases based on criteria in $form.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_alias_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3; // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find the "tab" we're on
    $tab = $_SESSION['ona'][$form['form_id']]['tab'];

    // Build js to refresh this list
    $refresh = "xajax_window_submit('{$window_name}', xajax.getFormValues('{$form['form_id']}'), 'display_alias_list');";

    // If it's not a new query, load the previous query from the session
    // into $form and save the current page and filter in the session.
    // Also find/set the "page" we're viewing
    $page = 1;
    if ($form['page'] and is_numeric($form['page'])) {
        $form = array_merge($form, $_SESSION['ona'][$form['form_id']][$tab]['q']);
        $_SESSION['ona'][$form['form_id']][$tab]['page'] = $page = $form['page'];
        $_SESSION['ona'][$form['form_id']][$tab]['filter'] = $form['filter'];
    }

    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Search results go in here
    $results = array();
    $count = 0;




    //
    // *** ALIAS SEARCH ***
    //

    // Start building the "where" clause for the sql query to find the hosts to display
    $where = "";
    $and = "";

    // ALIAS_ID
    if ($form['alias_id']) {
        $where .= $and . "ID = " . $onadb->qstr($form['alias_id']);
        $and = " AND ";
    }


    // HOST_ID
    if ($form['host_id']) {
        $where .= $and . "HOST_ID = " . $onadb->qstr($form['host_id']);
        $and = " AND ";
    }


    // ALIAS NAME
    if ($form['hostname']) {
        $where .= $and . "ALIAS LIKE " . $onadb->qstr('%'.$form['hostname'].'%');
        $and = " AND ";
    }


    // ZONE
    if ($form['zone']) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "DNS_ZONE_ID IN ( SELECT ID " .
                                        "  FROM DNS_ZONES_B " .
                                        "  WHERE ZONE_NAME LIKE " . $onadb->qstr($form['zone'].'%') . " ) ";
        $and = " AND ";
    }

    // ZONE ID
    if ($form['zone_id']) {
        $where .= $and . "DNS_ZONE_ID = " . $onadb->qstr($form['zone_id']);
        $and = " AND ";
    }






    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        // Host names should always be lower case
        $form['filter'] = strtolower($form['filter']);
        $filter = $and . ' ALIAS LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'HOST_ALIASES_B',
            $where . $filter,
            "ALIAS ASC",
            $conf['search_results_per_page'],
            $offset
        );

    // If we got less than serach_results_per_page, add the current offset to it
    // so that if we're on the last page $rows still has the right number in it.
    if ($rows > 0 and $rows < $conf['search_results_per_page']) {
        $rows += ($conf['search_results_per_page'] * ($page - 1));
    }

    // If there were more than $conf['search_results_per_page'] find out how many records there really are
    else if ($rows >= $conf['search_results_per_page']) {
        list ($status, $rows, $records) =
            db_get_records(
                $onadb,
                'HOST_ALIASES_B',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

    //$js .= "alert('Where: " . str_replace("'", '"', $where) . "');";




    // Find out if there were host results
    $hosts = 0;
    if ($form['host_where'] and !$form['no_host_search']) {
        $filter = '';
        if ($form['filter']) {
            // Host names should always be lower case
            $form['filter'] = strtolower($form['filter']);
            $filter = $and . ' PRIMARY_DNS_NAME LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
        }
        list ($status, $hosts, $records) =
            db_get_records(
                $onadb,
                'HOSTS_B',
                $form['host_where'] . $filter,
                "",
                0
            );
    }



    //
    // *** BUILD HTML LIST ***
    //
    $html .= <<<EOL
        <!-- Alias Results -->
        <table id="{$form['form_id']}_alias_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Alias Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Associated Host</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Interface</td>
                <!-- Removed for now (brandon) it makes things too wide!
                <td class="list-header" align="center" style="{$style['borderR']};">Device Model</td>
                -->
                <td class="list-header" align="center" style="{$style['borderR']};">Location</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;
    // Loop and display each record
    foreach($results as $record) {
        // Get additional info about eash alias record //

        // Zone Name
        list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $record['DNS_ZONE_ID']));
        $record['ZONE'] = $zone['ZONE_NAME'];

        // Associated Hostname and zone
        list($status, $rows, $host) = ona_get_host_record(array('ID' => $record['HOST_ID']));
        list($status, $rows, $zone) = ona_get_zone_record(array('ID' => $host['PRIMARY_DNS_ZONE_ID']));
        $record['HOSTNAME'] = $host['PRIMARY_DNS_NAME'];
        $record['HOST_ZONE'] = $zone['ZONE_NAME'];
        $record['HOST_ZONE_ID'] = $zone['ID'];

        // Interface (and find out how many there are)
        list($status, $interfaces, $interface) = ona_get_interface_record(array('HOST_ID' => $record['HOST_ID']), '');
        $record['IP_ADDRESS'] = ip_mangle($interface['IP_ADDRESS'], 'dotted');
        $interface_style = '';
        if ($interfaces > 1) $interface_style = 'font-weight: bold;';

        // Subnet description
        list($status, $rows, $subnet) = ona_get_subnet_record(array('ID' => $interface['SUBNET_ID']));
        $record['SUBNET'] = $subnet['DESCRIPTION'];
        $record['IP_SUBNET_MASK'] = ip_mangle($subnet['IP_SUBNET_MASK'], 'dotted');
        $record['IP_SUBNET_MASK_CIDR'] = ip_mangle($subnet['IP_SUBNET_MASK'], 'cidr');

      // Removed for now (brandon) it makes things too wide!
      /*
        // Device Description
        list($status, $rows, $device) = ona_get_model_record(array('ID' => $host['DEVICE_MODEL_ID']));
        $record['DEVICE'] = "{$device['MANUFACTURER_NAME']}, {$device['MODEL_DESCRIPTION']} ({$device['DEVICE_TYPE_DESCRIPTION']})";
        $record['DEVICE'] = str_replace('Unknown', '?', $record['DEVICE']);
      */

        $record['NOTES'] = $host['NOTES'];
        $record['NOTES_SHORT'] = truncate($record['NOTES'], 40);

        // Get location_number from the location_id
        list($status, $rows, $location) = ona_get_location_record(array('id' => $host['location_id']));

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">

                <td class="list-row">
                    {$record['ALIAS']}.<a title="View zone. ID: {$record['DNS_ZONE_ID']}"
                         class="zone"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_zone\', \'zone_id=>{$record['DNS_ZONE_ID']}\', \'display\')');"
                    >{$record['ZONE']}</a>&nbsp;
                </td>

                <td class="list-row">
                    <a title="View host. ID: {$record['HOST_ID']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['HOST_ID']}\', \'display\')');"
                    >{$record['HOSTNAME']}</a
                    >.<a title="View zone. ID: {$record['HOST_ZONE_ID']}"
                         class="zone"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_zone\', \'zone_id=>{$record['HOST_ZONE_ID']}\', \'display\')');"
                    >{$record['HOST_ZONE']}</a>
                </td>

                <td class="list-row">
                    <a title="View subnet. ID: {$subnet['ID']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['ID']}\', \'display\')');"
                    >{$record['SUBNET']}</a>&nbsp;
                </td>

                <td class="list-row" align="left">
                    <span style="{$interface_style}"
EOL;

if ($interfaces > 1) {
        $html .= <<<EOL
                          onMouseOver="wwTT(this, event,
                                            'id', 'tt_host_interface_list_{$record['HOST_ID']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>host_interface_list,id=>tt_host_interface_list_{$record['HOST_ID']},host_id=>{$record['HOST_ID']}\');'
                                           );"
EOL;
}
         $html .= <<<EOL
                    >{$record['IP_ADDRESS']}</span>&nbsp;
                    <span title="{$record['IP_SUBNET_MASK']}">/{$record['IP_SUBNET_MASK_CIDR']}</span>&nbsp;
                </td>

                <!-- Removed for now (brandon) it makes things too wide!
                <td class="list-row">{$record['DEVICE']}&nbsp;</td>
                -->

                <td class="list-row" align="right">
                    <span onMouseOver="wwTT(this, event,
                                            'id', 'tt_location_{$record['location_id']}',
                                            'type', 'velcro',
                                            'width', 250,
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>location,id=>tt_location_{$record['location_id']},location_id=>{$record['location_id']}\');'
                                           );"
                    >{$location['reference']}</span>&nbsp;
                </td>

                <td class="list-row">
                    <span title="{$record['NOTES']}">{$record['NOTES_SHORT']}</span>&nbsp;
                </td>

                <!-- ACTION ICONS -->
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_alias_{$record['ID']}"
                        ><input type="hidden" name="alias_id" value="{$record['ID']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

        if (auth('alias_modify',$debug_val)) {
            $html .= <<<EOL

                    <a title="Edit alias"
                       class="act"
                       onClick="xajax_window_submit('edit_alias', xajax.getFormValues('{$form['form_id']}_list_alias_{$record['ID']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('alias_del',$debug_val)) {
            $html .= <<<EOL

                    <a title="Delete alias"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this alias?');
                                if (doit == true)
                                    xajax_window_submit('edit_alias', xajax.getFormValues('{$form['form_id']}_list_alias_{$record['ID']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
        }
        $html .= <<<EOL
                    &nbsp;
                </td>

            </tr>
EOL;
    }

    $html .= <<<EOL
    </table>
EOL;

    $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_alias_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
            /* Hack to Make sure the other tables are 100% wide */
            if (el('list_hosts_filter_form_host_list'))
                el('list_hosts_filter_form_host_list').style.width = el('list_hosts_filter_form_table').offsetWidth + 'px';

EOL;

    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);


    // Add a link to host results if there were any
    if ($hosts > 0) {
        $phrase1 = 'There are also';
        $phrase2 = 'hosts';
        $phrase3 = 'that match your search query.';
        if ($aliases == 1) {
            $phrase1 = 'There is also';
            $phrase2 = 'host';
            $phrase3 = 'that matches your search query.';
        }

        $html .= <<<EOL
    <!-- Host Links -->
    <table width="100%" cellspacing="0" border="0" cellpadding="0" style="background-color: #FFFCD5; margin-bottom: 0.2em;">
        <tr>
            <td id="page_links" style="font-weight: bold; " class="padding" align="center">
                {$phrase1}
                <a title="Display {$phrase2}"
                   class="nav"
                   onClick="xajax_window_submit('{$window_name}', 'form_id=>{$form['form_id']},type=>hosts', 'change_type');"
                >{$hosts} {$phrase2}</a>
                {$phrase3}
            </td>
        </tr>
    </table>
EOL;
    }

    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}








?>
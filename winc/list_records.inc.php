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

    // If the user supplied an array in a string, transform it into an array
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
        $form = array_merge($form, (array)$_SESSION['ona'][$form['form_id']][$tab]['q']);
        $_SESSION['ona'][$form['form_id']][$tab]['page'] = $page = $form['page'];
        $_SESSION['ona'][$form['form_id']][$tab]['filter'] = $form['filter'];
    }
    printmsg("DEBUG => Displaying records list page: {$page}", 1);

    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Search results go in here
    $results = array();
    $count = 0;




    //
    // *** ADVANCED RECORD SEARCH ***
    //       FIND RESULT SET
    //

    // Start building the "where" clause for the sql query to find the records to display
    $where = "";
    $and = "";
    $orderby = "";

    // RECORD ID
    if ($form['record_id']) {
        $where .= $and . "id = " . $onadb->qstr($form['record_id']);
        $and = " AND ";
    }

    // INTERFACE ID
    if ($form['interface_id']) {
        $where .= $and . "interface_id = " . $onadb->qstr($form['interface_id']);
        $and = " AND ";
    }

    // HOSTNAME
    $aliases = 0;
    if ($form['hostname']) {
        $where .= $and . "id IN (SELECT id " .
                                "  FROM dns " .
                                "  WHERE name LIKE " . $onadb->qstr('%'.$form['hostname'].'%') ." )";
        $and = " AND ";
    }


    // DOMAIN
    if ($form['domain']) {
        // FIXME: does this clause work correctly?
        printmsg("FIXME: => Does \$form['domain'] work correctly in list_records.inc.php, line 79?", 2);
        // We do a sub-select to find interface id's that match
        //$where .= $and . "domain_id IN ( SELECT id " .
        //                                "  FROM dns " .
        //                                "  WHERE name LIKE " . $onadb->qstr($form['domain'].'%') . " ) ";
        $where .= $and . "name LIKE " . $onadb->qstr($form['domain'].'%') . " )  ";
        $and = " AND ";
    }

    // DOMAIN ID
    if ($form['domain_id']) {
        //$where .= $and . "primary_dns_id IN ( SELECT id " .
        //                                    "  FROM dns " .
        //                                    "  WHERE domain_id = " . $onadb->qstr($form['domain_id']) . " )  ";
        $where .= $and . "domain_id = " . $onadb->qstr($form['domain_id']);
        $orderby .= "name, domain_id";
        $and = " AND ";
    }

    // subnet ID
    if (is_numeric($form['subnet_id'])) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "id IN ( SELECT host_id " .
                                 "  FROM interfaces " .
                                 "  WHERE subnet_id = " . $onadb->qstr($form['subnet_id']) . " ) ";
        $and = " AND ";
    }


    // MAC
    if ($form['mac']) {
        // Clean up the mac address
        $form['mac'] = strtoupper($form['mac']);
        $form['mac'] = preg_replace('/[^%0-9A-F]/', '', $form['mac']);

        // We do a sub-select to find interface id's that match
        $where .= $and . "id IN ( SELECT host_id " .
                         "        FROM interfaces " .
                         "        WHERE mac_addr LIKE " . $onadb->qstr('%'.$form['mac'].'%') . " ) ";
        $and = " AND ";

    }


    // IP ADDRESS
    $ip = $ip_end = '';
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
            $where .= $and . "id IN ( SELECT host_id " .
                             "        FROM interfaces " .
                             "        WHERE ip_addr >= " . $onadb->qstr($ip) . " AND ip_addr <= " . $onadb->qstr($ip_end) . " )";
            $and = " AND ";
        }
    }


    // NOTES
    if ($form['notes']) {
        $where .= $and . "notes LIKE " . $onadb->qstr('%'.$form['notes'].'%');
        $and = " AND ";
    }




    // DEVICE MODEL
    if ($form['model']) {
        $where .= $and . "model_id = " . $onadb->qstr($form['model']);
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
                $where .= $or . "DEVICE_MODEL_ID = " . $onadb->qstr($record['id']);
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
                $where .= $or . "model_id = " . $onadb->qstr($record['id']);
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
        $filter = ' AND name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }


    // If we get a specific host to look for we must do the following
    // 1. get (A) records that match any interface_id associated with the host
    // 2. get CNAMES that point to dns records that are using an interface_id associated with the host
    if ($form['host_id']) {
        list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'dns',
            'interface_id in (select id from interfaces where host_id = '. $onadb->qstr($form['host_id']) .')',
            "interface_id",
            $conf['search_results_per_page'],
            $offset
        );

        // If we got less than search_results_per_page, add the current offset to it
        // so that if we're on the last page $rows still has the right number in it.
        if ($rows > 0 and $rows < $conf['search_results_per_page']) {
            $rows += ($conf['search_results_per_page'] * ($page - 1));
        }


        //FIXME: MP not sure how I'm going to do this.. ..............
        // If there were more than $conf['search_results_per_page'] find out how many records there really are
        else if ($rows >= $conf['search_results_per_page']) {
            list ($status, $rows, $records) =
                db_get_records(
                    $onadb,
                    'dns',
                    $where . $filter,
                    "",
                    0
                );
        }

    } else {
        list ($status, $rows, $results) =
            db_get_records(
                $onadb,
                'dns',
                $where . $filter,
                $orderby,
                $conf['search_results_per_page'],
                $offset
            );


        // If we got less than search_results_per_page, add the current offset to it
        // so that if we're on the last page $rows still has the right number in it.
        if ($rows > 0 and $rows < $conf['search_results_per_page']) {
            $rows += ($conf['search_results_per_page'] * ($page - 1));
        }

        // If there were more than $conf['search_results_per_page'] find out how many records there really are
        else if ($rows >= $conf['search_results_per_page']) {
            list ($status, $rows, $records) =
                db_get_records(
                    $onadb,
                    'dns',
                    $where . $filter,
                    "",
                    0
                );
        }
    }

    $count = $rows;




    //
    // *** BUILD HTML LIST ***
    //
    $html .= <<<EOL
        <!-- Host Results -->
        <table id="{$form['form_id']}_host_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Time to Live</td>
                <!-- <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td> -->
                <td class="list-header" align="center" style="{$style['borderR']};">Data</td>
                <!-- <td class="list-header" align="center" style="{$style['borderR']};">Device Model</td> -->
                <!-- <td class="list-header" align="center" style="{$style['borderR']};">Location</td> -->
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;
    // Loop and display each record
    $last_record = array('name' => $results[0]['name'], 'domain_id' => $results[0]['domain_id']);
    $last_record_count = 0;

    for($i=0; $i<=(count($results)); $i++) {
        $record = $results[$i];
        // Get additional info about each host record


        // Check if we've already seen this record before.
        if ($record['name'] == $last_record['name'] &&
            $record['domain_id'] == $last_record['domain_id']) {
            $last_record_count++;
            continue;
        } else {
            $record = $results[$i-1];

            // Check for interface records (and find out how many there are)
            list($status, $interfaces, $interface) = ona_get_interface_record(array('id' => $record['interface_id']), '');

            // Get the domain name
            list($status, $rows, $domain) = ona_get_domain_record(array('id' => $record['domain_id']));
            $record['domain'] = $domain['fqdn'];

            // Set BOLDING if more than one record is associated with this DNS name
            // FIXME: need to change the name to something better than $interface_style. (PK)
            $interface_style = '';
            if ($last_record_count > 1) { $interface_style = 'font-weight: bold;'; }

            if($interfaces) {
                $record['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');

                // Subnet description
                list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
                $record['subnet'] = $subnet['name'];
                $record['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
                $record['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

                // Create string to be embedded in HTML for display
                $data = <<<EOL
                            >{$record['ip_addr']}</span>&nbsp;
                    <span title="{$record['ip_mask']}">/{$record['ip_mask_cidr']}</span>&nbsp;
EOL;
            } else {
                // Get other DNS records which name this record as parent
                list($status, $rows, $dns_other) = ona_get_host_record(array('id' => $record['dns_id']));

                // Create string to be embedded in HTML for display
                if($rows) {
                    $data = <<<EOL
                    <a title="View host. ID: {$dns_other['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$dns_other['id']}\', \'display\')');"
                    >{$dns_other['name']}</a
                    >.<a title="View domain. ID: {$dns_other['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$dns_other['domain_id']}\', \'display\')');"
                    >{$dns_other['domain_fqdn']}</a></span>&nbsp;
EOL;
                }
            }
        }

        $record['notes_short'] = truncate($record['notes'], 40);

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

//         if ($record['type'] == 'A') {
//             list($status, $rows, $interface) = ona_get_interface_record(array('id' => $record['interface_id']));
//             list($status, $rows, $host) = ona_get_host_record(array('id' => $interface['host_id']));
//         }

        $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');";
        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">

                <td class="list-row">
                    <span title="Record. ID: {$record['id']}"
                       onClick=""
                    >{$record['name']}</span
                    >.<a title="View domain. ID: {$domain['id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$domain['id']}\', \'display\')');"
                    >{$record['domain']}</a>
                </td>

                <td class="list-row">
                    <span title="Record Type. ID: {$record['id']}"
                       onClick=""
                    >{$record['type']}</span>&nbsp;
                </td>


                <td class="list-row">
                    <span title="Time-to-Live. ID: {$record['id']}"
                       onClick=""
                    >{$record['ttl']} seconds</span>&nbsp;
                </td>

<!--                <td class="list-row"> -->
<!--                    <a title="View subnet. ID: {$subnet['id']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['subnet']}</a>&nbsp; -->
<!--                </td> -->

                <td class="list-row" align="left">
                    <span style="{$interface_style}"
EOL;

if ($last_record_count > 1) {
        $html .= <<<EOL
                          onMouseOver="wwTT(this, event,
                                            'id', 'tt_host_interface_list_{$record['id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>host_interface_list,id=>tt_host_interface_list_{$record['id']},host_id=>{$record['id']}\');'
                                           );"
                           >Number of records: {$last_record_count}</span>&nbsp;
EOL;
} else { $html .= $data; }
        $html .= <<<EOL
                </td>

<!--                <td class="list-row">{$record['device']}&nbsp;</td> -->

<!--                <td class="list-row" align="right"> -->
<!--                    <span onMouseOver="wwTT(this, event,
                                            'id', 'tt_location_{$record['location_id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>location,id=>tt_location_{$record['location_id']},location_id=>{$record['location_id']}\');'
                                           );"
                    >{$location['reference']}</span>&nbsp; -->
<!--                </td> -->

                <td class="list-row">
                    <span title="{$record['notes']}">{$record['notes_short']}</span>&nbsp;
                </td>

                <!-- ACTION ICONS -->
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_record_{$record['id']}"
                        ><input type="hidden" name="record_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>&nbsp;
EOL;

        if (auth('record_modify')) {
            $html .= <<<EOL

                    <a title="Edit DNS record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_host_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('record_del')) {
            $html .= <<<EOL

                    <a title="Delete DNS record"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this DNS record?');
                                if (doit == true)
                                    xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>
EOL;
        }
        $html .= <<<EOL
                    &nbsp;
                </td>

            </tr>
EOL;
        // reset the record counter before we go back for the next iteration
        $last_record = array('name' => $record['name'], 'domain_id' => $record['domain_id']);
        $last_record_count = 1;
    }



    $html .= <<<EOL
    </table>
EOL;

    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);

    $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_host_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;


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
        $form = array_merge($form, (array)$_SESSION['ona'][$form['form_id']][$tab]['q']);
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
        $where .= $and . "id = " . $onadb->qstr($form['alias_id']);
        $and = " AND ";
    }


    // HOST ID
    if ($form['host_id']) {
        $where .= $and . "host_id = " . $onadb->qstr($form['host_id']);
        $and = " AND ";
    }


    // ALIAS NAME
    if ($form['hostname']) {
        $where .= $and . "ALIAS LIKE " . $onadb->qstr('%'.$form['hostname'].'%');
        $and = " AND ";
    }


    // DOMAIN
    if ($form['domain']) {
        // We do a sub-select to find interface id's that match
        $where .= $and . "domain_id IN ( SELECT id " .
                                        "  FROM dns " .
                                        "  WHERE name LIKE " . $onadb->qstr($form['domain'].'%') . " ) ";
        $and = " AND ";
    }

    // DOMAIN ID
    if ($form['domain_id']) {
        $where .= $and . "domain_id = " . $onadb->qstr($form['domain_id']);
        $and = " AND ";
    }






    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        // Host names should always be lower case
        $form['filter'] = strtolower($form['filter']);
        $filter = $and . ' alias LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'HOST_ALIASES_B',
            $where . $filter,
            "alias ASC",
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
            $filter = $and . ' name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
        }
        list ($status, $hosts, $records) =
            db_get_records(
                $onadb,
                'hosts',
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

        // Associated Hostname and Domain
        list($status, $rows, $host) = ona_get_host_record(array('id' => $record['host_id']));
        $record['name'] = $host['name'];
        $record['domain_fqdn'] = $host['domain_fqdn'];
        $record['domain_id'] = $host['domain_id'];

        // Interface (and find out how many there are)
        list($status, $interfaces, $interface) = ona_get_interface_record(array('host_id' => $record['host_id']), '');
        $record['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');
        $interface_style = '';
        if ($interfaces > 1) $interface_style = 'font-weight: bold;';

        // Subnet description
        list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
        $record['subnet'] = $subnet['description'];
        $record['IP_subnet_MASK'] = ip_mangle($subnet['IP_subnet_MASK'], 'dotted');
        $record['IP_subnet_MASK_CIDR'] = ip_mangle($subnet['IP_subnet_MASK'], 'cidr');

      // Removed for now (brandon) it makes things too wide!
      /*
        // Device Description
        list($status, $rows, $device) = ona_get_model_record(array('ID' => $host['DEVICE_MODEL_ID']));
        $record['DEVICE'] = "{$device['MANUFACTURER_NAME']}, {$device['MODEL_DESCRIPTION']} ({$device['DEVICE_TYPE_DESCRIPTION']})";
        $record['DEVICE'] = str_replace('Unknown', '?', $record['DEVICE']);
      */ $record['DEVICE'] = "(BZ: removed, too wide!)";

        $record['notes'] = $host['notes'];
        $record['NOTES_SHORT'] = truncate($record['notes'], 40);

        // Get location_number from the location_id
        list($status, $rows, $location) = ona_get_location_record(array('id' => $host['location_id']));

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">

                <td class="list-row">
                    {$record['ALIAS']}.<a title="View domain. ID: {$record['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                    >{$record['domain_fqdn']}</a>&nbsp;
                </td>

                <td class="list-row">
                    <a title="View host. ID: {$record['host_id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['host_id']}\', \'display\')');"
                    >{$record['name']}</a
                    >.<a title="View domain. ID: {$record['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                    >{$record['domain_fqdn']}</a>
                </td>

                <td class="list-row">
                    <a title="View subnet. ID: {$subnet['id']}"
                         class="nav"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['subnet']}</a>&nbsp;
                </td>

                <td class="list-row" align="left">
                    <span style="{$interface_style}"
EOL;

if ($interfaces > 1) {
        $html .= <<<EOL
                          onMouseOver="wwTT(this, event,
                                            'id', 'tt_host_interface_list_{$record['host_id']}',
                                            'type', 'velcro',
                                            'styleClass', 'wwTT_niceTitle',
                                            'direction', 'south',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>host_interface_list,id=>tt_host_interface_list_{$record['host_id']},host_id=>{$record['host_id']}\');'
                                           );"
EOL;
}
         $html .= <<<EOL
                    >{$record['ip_addr']}</span>&nbsp;
                    <span title="{$record['IP_subnet_MASK']}">/{$record['IP_subnet_MASK_CIDR']}</span>&nbsp;
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
                    <span title="{$record['notes']}">{$record['NOTES_SHORT']}</span>&nbsp;
                </td>

                <!-- ACTION ICONS -->
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_alias_{$record['id']}"
                        ><input type="hidden" name="alias_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>
EOL;

        if (auth('alias_modify')) {
            $html .= <<<EOL

                    <a title="Edit alias"
                       class="act"
                       onClick="xajax_window_submit('edit_alias', xajax.getFormValues('{$form['form_id']}_list_alias_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('alias_del')) {
            $html .= <<<EOL

                    <a title="Delete alias"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this alias?');
                                if (doit == true)
                                    xajax_window_submit('edit_alias', xajax.getFormValues('{$form['form_id']}_list_alias_{$record['id']}'), 'delete');"
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

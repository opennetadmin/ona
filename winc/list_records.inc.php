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
        // Get the host record so we know what the primary interface is
        list($status, $rows, $host) = ona_get_host_record(array('id' => $form['host_id']), '');

        list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'dns',
            'interface_id in (select id from interfaces where host_id = '. $onadb->qstr($form['host_id']) .')',
            "type",
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
                    'interface_id in (select id from interfaces where host_id = '. $onadb->qstr($form['host_id']) .') ' . $filter,
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
        <!-- dns record Results -->
        <table id="{$form['form_id']}_dns_record_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['border']}; width: 16px;">&nbsp;</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Time to Live</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Data</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;
    // Loop and display each record
  //  $last_record = array('name' => $results[0]['name'], 'domain_id' => $results[0]['domain_id']);
  //  $last_record_count = 0;

    for($i=1; $i<=(count($results)); $i++) {
        $record = $results[$i];
        // Get additional info about each host record



        $record = $results[$i-1];

        // if the interface is the primary_dns_id for the host then mark it
        $primary_record = '&nbsp;';
        if ($host['primary_dns_id'] == $record['id']) {
            $primary_record = '<img title="Primary DNS record" src="'.$images.'/silk/font_go.png" border="0">';
        }

        // Check for interface records (and find out how many there are)
        list($status, $interfaces, $interface) = ona_get_interface_record(array('id' => $record['interface_id']), '');

        if($interfaces) {
            $record['ip_addr'] = ip_mangle($interface['ip_addr'], 'dotted');

            // Subnet description
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $interface['subnet_id']));
            $record['subnet'] = $subnet['name'];
            $record['ip_mask'] = ip_mangle($subnet['ip_mask'], 'dotted');
            $record['ip_mask_cidr'] = ip_mangle($subnet['ip_mask'], 'cidr');

            // Create string to be embedded in HTML for display
            $data = <<<EOL
                        {$record['ip_addr']}&nbsp;

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
                >{$dns_other['domain_fqdn']}</a>&nbsp;
EOL;
            }
        }



        $record['notes_short'] = truncate($record['notes'], 30);
        // Add a dot to the end of record name for display purposes
        $record['name'] = $record['name'].'.';

        // Process PTR record
        if ($record['type'] == 'PTR') {
            list($status, $rows, $pointsto) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            // get the domain_id from the parent A record
            $record['domain_id'] = $pointsto['domain_id'];
            // Flip the IP address 
            $record['name'] = ip_mangle($record['ip_addr'],'flip').'.IN-ADDR.ARPA';
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$pointsto['name']}</a>.<a title="View domain. ID: {$record['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                    >{$pointsto['fqdn']}</a>.&nbsp;
EOL;
        }

        // Process CNAME record
        if ($record['type'] == 'CNAME') {
            list($status, $rows, $cname) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$cname['name']}</a>.<a title="View domain. ID: {$record['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                    >{$cname['fqdn']}</a>.&nbsp;
EOL;
        }

        // Process NS record
        if ($record['type'] == 'NS') {
            // clear out the $record['domain'] value so it shows properly in the list
            $record['name'] = '';
            list($status, $rows, $ns) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$ns['name']}</a>.<a title="View domain. ID: {$record['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$record['domain_id']}\', \'display\')');"
                    >{$ns['fqdn']}</a>.&nbsp;
EOL;
        }

        // Get the domain name
        $ttl_style = 'title="Time-to-Live"';
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $record['domain_id']));
        // Make record['domain'] have the right name in it
        if ($record['type'] != 'PTR') { $record['domain'] = $domain['fqdn']; }
        // clear out the $record['domain'] value so it shows properly in the list for NS records
        if ($record['type'] == 'NS')  { $record['domain'] = $domain['name'].'.'; }
        // if the ttl is blank, use the one in the domain (minimum)
        if ($record['ttl'] == 0) {
            $record['ttl'] = $domain['minimum'];
            $ttl_style = 'style="font-style: italic;" title="Using TTL from domain"';
        }

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

        $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');";
        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
                <td class="list-row">
                {$primary_record}
                </td>

                <td class="list-row">
                    <span title="Record. ID: {$record['id']}"
                       onClick=""
                    >{$record['name']}</span
                    ><a title="View domain. ID: {$domain['id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$domain['id']}\', \'display\')');"
                    >{$record['domain']}</a>
                </td>

                <td class="list-row">
                    <span
                       onClick=""
                       {$ttl_style}
                    >{$record['ttl']} seconds</span>&nbsp;
                </td>

                <td class="list-row">
                    <span title="Record Type"
                       onClick=""
                    >{$record['type']}</span>&nbsp;
                </td>

                <td class="list-row" align="left">
EOL;

        $html .= $data;
        $html .= <<<EOL
                </td>

<!--                <td class="list-row">{$record['device']}&nbsp;</td> -->

                <td class="list-row">
                    <span title="{$record['notes']}">{$record['notes_short']}</span>&nbsp;
                </td>

                <!-- ACTION ICONS -->
                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_record_{$record['id']}"
                        ><input type="hidden" name="dns_record_id" value="{$record['id']}"
                        ><input type="hidden" name="host_id" value="{$host['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>&nbsp;
EOL;
        if (auth('dns_record_modify')) {
            // If it is an A record but not the primary, display an option to make it primary. and only if we are dealing with a specific host
            if ($record['type'] == 'A' and $host['primary_dns_id'] != $record['id'] and $form['host_id']) {
                $html .= <<<EOL

                    <a title="Make this the primary DNS record"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to make this the primary DNS record for this host?');
                                if (doit == true)
                                    xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'makeprimary');"
                    ><img src="{$images}/silk/font_go.png" border="0"></a>
EOL;
            }
        }

        if (auth('dns_record_modify')) {
            // If it is a PTR, adjust the comment to say you can only delete, not modify.
            if ($record['type'] == 'PTR') {
            $html .= <<<EOL

                    <a title="You can not edit a PTR record directly, you must edit the A record."
                       class="act"
                    ><img src="{$images}/silk/comment.png" border="0"></a>&nbsp;
EOL;
            } 
            // If it is a NS, adjust the comment to say you can only delete, not modify.
            else if ($record['type'] == 'NS') {
            $html .= <<<EOL

                    <a title="You can not edit an NS record directly, you must edit the domain/server association."
                       class="act"
                    ><img src="{$images}/silk/comment.png" border="0"></a>&nbsp;
EOL;
            }
            else {
            $html .= <<<EOL

                    <a title="Edit DNS record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
            }
        }

        if (auth('dns_record_del')) {
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
            el('{$form['form_id']}_dns_record_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;


    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}














?>

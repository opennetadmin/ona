<?php



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

    // enable or disable wildcards
    $wildcard = '%';
    if ($form['nowildcard']) $wildcard = '';

    // RECORD ID
    if ($form['record_id']) {
        $where .= $and . "id = " . $onadb->qstr($form['record_id']);
        $and = " AND ";
    }

    // DNS VIEW ID
    if ($form['dns_view']) {
        if (is_string($form['dns_view'])) list($status, $rows, $dnsview) = ona_get_dns_view_record(array('name' => $form['dns_view']));
        if (is_numeric($form['dns_view'])) list($status, $rows, $dnsview) = ona_get_dns_view_record(array('id' => $form['dns_view']));
        $where .= $and . "dns_view_id = " . $onadb->qstr($dnsview['id']);
        $and = " AND ";
    }

    // INTERFACE ID
    if ($form['interface_id']) {
        $where .= $and . "interface_id = " . $onadb->qstr($form['interface_id']);
        $and = " AND ";
    }

    // DNS RECORD note
    if ($form['notes']) {
        $where .= $and . "notes LIKE " . $onadb->qstr($wildcard.$form['notes'].$wildcard);
        $and = " AND ";
    }

    // DNS RECORD TYPE
    if ($form['dnstype']) {
        $where .= $and . "type = " . $onadb->qstr($form['dnstype']);
        $and = " AND ";
    }

    // HOSTNAME
    if ($form['hostname']) {
        $where .= $and . "id IN (SELECT id " .
                                "  FROM dns " .
                                "  WHERE name LIKE " . $onadb->qstr($wildcard.$form['hostname'].$wildcard) ." )";
        $and = " AND ";
    }


    // DOMAIN
    if ($form['domain']) {
        // FIXME: MP test if this clause works correctly?  Not sure that anything even uses this?
        list($status,$rows,$tmpdomain) = ona_find_domain($form['domain']);
        $where .= $and . "domain_id = " . $onadb->qstr($tmpdomain['id']);
        $orderby .= "name, domain_id";
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
            $where .= $and . "interface_id IN ( SELECT id " .
                             "        FROM interfaces " .
                             "        WHERE ip_addr >= " . $onadb->qstr($ip) . " AND ip_addr <= " . $onadb->qstr($ip_end) . " )";
            $and = " AND ";
        }
    }





    // display a nice message when we dont find all the records
    if ($where == '' and $form['content_id'] == 'search_results_list') {
        $js .= "el('search_results_msg').innerHTML = 'Unable to find DNS records matching your query, showing all records';";
    }

    // Wild card .. if $while is still empty, add a 'ID > 0' to it so you see everything.
    if ($where == '')
        $where = 'id > 0';

    // If we dont have DNS views turned on, limit data to just the default view.
    // Even if there is data associated with other views, ignore it
    if (!$conf['dns_views']) {
        $where .= ' AND dns_view_id = 0';
    }


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
        // If we dont have DNS views turned on, limit data to just the default view.
        // Even if there is data associated with other views, ignore it
        // MP: something strange with this, it should only limit to default view.. sometimes it does not???
        if (!$conf['dns_views']) {
            $hwhere .= 'dns_view_id = 0 AND ';
        }

        // Get the host record so we know what the primary interface is
        list($status, $rows, $host) = ona_get_host_record(array('id' => $form['host_id']), '');

        list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'dns',
            $hwhere.'interface_id in (select id from interfaces where host_id = '. $onadb->qstr($form['host_id']) .') OR interface_id in (select interface_id from interface_clusters where host_id = '. $onadb->qstr($form['host_id']) .')',
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
                    $hwhere.'interface_id in (select id from interfaces where host_id = '. $onadb->qstr($form['host_id']) .') OR interface_id in (select interface_id from interface_clusters where host_id = '. $onadb->qstr($form['host_id']) .')' . $filter,
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

                <td colspan="2" class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Time to Live</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Data</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Effective</td>
EOL;
    if ($conf['dns_views']) {
        $html .= "<td class=\"list-header\" align=\"center\" style=\"{$style['borderR']};\">DNS View</td>";
    }

    $html .= <<<EOL
                <td class="list-header" align="center" style="{$style['borderR']};">Notes</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;
    // Loop and display each record
    for($i=1; $i<=(count($results)); $i++) {
        // Get additional info about each host record
        $record = $results[$i-1];

        $primary_record = '&nbsp;';

        // Check for interface records (and find out how many there are)
        list($status, $interfaces, $interface) = ona_get_interface_record(array('id' => $record['interface_id']), '');

        if($interfaces) {
            // if the interface is the primary_dns_id for the host then mark it
            if ($host['primary_dns_id'] == $record['id']) {
                $primary_record = '<img title="Primary DNS record" src="'.$images.'/silk/font_go.png" border="0">';
            }

            // Get the host record so we know what the primary interface is
            //list($status, $rows, $inthost) = ona_get_host_record(array('id' => $interface['host_id']), '');

            // Make the type correct based on the IP passed in
            if (strlen($interface['ip_addr']) > 11 and $record['type'] == 'A') {
                $record['type'] = 'AAAA';
            }

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
            list($status, $rows, $pdomain)  = ona_get_domain_record(array('id' => $record['domain_id']), '');

            // Flip the IP address
            $record['name'] = ip_mangle($record['ip_addr'],'flip');
            $record['domain'] = $pdomain['name'];

            if ($pdomain['parent_id']) {
                list ($status, $rows, $parent) = ona_get_domain_record(array('id' => $pdomain['parent_id']));
                $parent['name'] = ona_build_domain_name($parent['id']);
                $record['domain'] = $pdomain['name'].'.'.$parent['name'];
                unset($parent['name']);
            }

            // strip down the IP to just the "host" part as it relates to the domain its in
            if (strstr($record['domain'],'in-addr.arpa')) {
                $domain_part = preg_replace("/.in-addr.arpa$/", '', $record['domain']);
            } else {
                $domain_part = preg_replace("/.ip6.arpa$/", '', $record['domain']);
            }
            $record['name'] = preg_replace("/${domain_part}$/", '', $record['name']);

            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$pointsto['name']}</a>.<a title="View domain. ID: {$pointsto['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$pointsto['domain_id']}\', \'display\')');"
                    >{$pointsto['domain_fqdn']}</a>.&nbsp;
EOL;
        }

        // Process CNAME record
        if ($record['type'] == 'CNAME') {
            list($status, $rows, $cname) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$cname['name']}</a>.<a title="View domain. ID: {$cname['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$cname['domain_id']}\', \'display\')');"
                    >{$cname['domain_fqdn']}</a>.&nbsp;
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
                    >{$ns['name']}</a>.<a title="View domain. ID: {$ns['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$ns['domain_id']}\', \'display\')');"
                    >{$ns['domain_fqdn']}</a>.&nbsp;
EOL;
        }

        // Process MX record
        if ($record['type'] == 'MX') {
            // show the preference value next to the type
            $record['type'] = "{$record['type']} ({$record['mx_preference']})";
            list($status, $rows, $mx) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$mx['name']}</a>.<a title="View domain. ID: {$mx['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$mx['domain_id']}\', \'display\')');"
                    >{$mx['domain_fqdn']}</a>.&nbsp;
EOL;
        }

        // Process SRV record
        if ($record['type'] == 'SRV') {
            // show the preference value next to the type
            $record['type'] = "{$record['type']} ({$record['srv_port']})";
            list($status, $rows, $srv) = ona_get_dns_record(array('id' => $record['dns_id']), '');
            $data = <<<EOL
                    <a title="Edit DNS A record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', 'dns_record_id=>{$record['dns_id']}', 'editor');"
                    >{$srv['name']}</a>.<a title="View domain. ID: {$srv['domain_id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$srv['domain_id']}\', \'display\')');"
                    >{$srv['domain_fqdn']}</a>.&nbsp;
EOL;
        }

        // Process TXT record
        if ($record['type'] == 'TXT') {
            // some records will have an interfaceid and dnsid when associated to another dns name
            // some will just be un associated txt records or domain only records.  Determine that here and
            // display appropriately.  This is to ensure associated DNS records match up if the name changes
            if ($record['interface_id'] and $record['dns_id']) {
                list($status, $rows, $txtmain) = ona_get_dns_record(array('id' => $record['dns_id']), '');
                $record['name'] = $txtmain['name'].'.';
            }

            $data = truncate($record['txt'],70);
        }

        // Lastly, Lets see if we have any override data
        if ($record['override_data'] != '') {
            $override_fmt = $record['override_data'];
            // validate 
            if ($record['type'] != 'A') {
                // Add a trailing dot
                $override_fmt = $record['override_data'].'.';
                // Lets set some resolver optons so things are quicker
                putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1');
                // Attempt a name lookup
                $found_ext_dns = gethostbyname($record['override_data']);
                // If the name we looked up is returned, this means we couldnt resolve an IP.
                if ($found_ext_dns != $record['override_data']) {
                    $override_fmt = "<span title='This external reference does not resolve in DNS. This may still be ok for some situations.' style='color: red;'>{$override_fmt}</style>";
                }
            }
            $data = <<<EOL
                    {$override_fmt}&nbsp;
    
EOL;
        }
 
        // Get the domain name and domain ttl
        $ttl_style = 'title="Time-to-Live"';
        list($status, $rows, $domain) = ona_get_domain_record(array('id' => $record['domain_id']));
        // Make record['domain'] have the right name in it
        if ($record['type'] != 'PTR') { $record['domain'] = $domain['fqdn']; }
        // clear out the $record['domain'] value so it shows properly in the list for NS records
        if ($record['type'] == 'NS')  { $record['domain'] = $domain['fqdn']; }
        // if the ttl is blank, use the one in the domain (minimum)
        if ($record['ttl'] == 0) {
            $record['ttl'] = $domain['default_ttl'];
            $ttl_style = 'style="font-style: italic;" title="Using TTL from domain"';
        }

        // format the ebegin using the configured date format
        $ebegin = '';
        // If it is in the future, print the time
        if (strtotime($record['ebegin']) > time()) $ebegin = '<span title="Active in DNS on: '.$record['ebegin'].'">' . date($conf['date_format'],strtotime($record['ebegin'])) . '</span>';
        // If it is 0 then show as disabled
        if (strtotime($record['ebegin']) < 0) {
            $ebegin = <<<EOL
                <span
                    style="background-color:#FFFF99;"
                    title="Disabled: Won't build in DNS"
                    onClick="var doit=confirm('Are you sure you want to enable this DNS record?');
                                if (doit == true)
                                    xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'enablerecord');"
                >Disabled</span>
EOL;
        }

        // If we get this far and the name we have built has a leading . in it then remove the dot.
        $record['name'] = preg_replace("/^\./", '', $record['name']);

        // Get the name of the view and the description
        if ($conf['dns_views']) {
            list($status, $rows, $dnsview) = ona_get_dns_view_record(array('id' => $record['dns_view_id']));
            $record['view_name'] = $dnsview['name'];
            $record['view_desc'] = $dnsview['description'];
        }

        // Escape data for display in html
        foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf['php_charset']); }

        //$primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_host\', \'host_id=>{$record['id']}\', \'display\')');";
        $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight';" onMouseOut="this.className='row-normal';">
                <td class="list-row" style="padding-right: 2px; padding-left: 4px;" width="16px">
                {$primary_record}
                </td>

                <td class="list-row">
                    <span title="Record. ID: {$record['id']}"
                       onClick=""
                    >{$record['name']}</span
                    ><a title="View domain. ID: {$domain['id']}"
                         class="domain"
                         onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_domain\', \'domain_id=>{$domain['id']}\', \'display\')');"
                    >{$record['domain']}.</a>
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
        // Put the data in!
        $html .= $data;

        $html .= <<<EOL
                </td>

                <td class="list-row" align="center">
                    {$ebegin}&nbsp;
                </td>
EOL;

        // Display the view we are part of
        if ($conf['dns_views']) {
        $html .= <<<EOL
                <td class="list-row" align="center" title="{$record['view_desc']}">
                    {$record['view_name']}&nbsp;
                </td>
EOL;
        }

        $html .= <<<EOL
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
            if (($record['type'] == 'A' or $record['type'] == 'AAAA') and $host['primary_dns_id'] != $record['id'] and $form['host_id']) {
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


        // display a view host button
        if (!$record['override_data']) {
          $html .= <<<EOL
                <a title="View associated host record: {$interface['host_id']}"
                   class="act"
                   onClick="xajax_window_submit('display_host', 'host_id=>{$interface['host_id']}', 'display');"
                ><img src="{$images}/silk/computer_go.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('dns_record_modify')) {
            $html .= <<<EOL

                    <a title="Edit DNS record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('dns_record_del')) {
            $html .= <<<EOL

                    <a title="Delete DNS record"
                       class="act"
                       onClick="xajax_window_submit('edit_record', xajax.getFormValues('{$form['form_id']}_list_record_{$record['id']}'), 'delete');"
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

    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}














?>

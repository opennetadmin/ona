<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of subnets assigned to DHCP server based on search criteria.
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
    //       FIND RESULT SET
    //

    // Start building the "where" clause for the sql query to find the subnets to display
    $where = "";
    $and = "";


    // SERVER ID
    if ($form['server_id']) {
        $where .= $and . 'id IN (SELECT subnet_id
                                FROM   dhcp_server_subnets
                                WHERE  host_id = '. $onadb->qstr($form['server_id']).'
                                UNION
                                SELECT subnet_id
                                FROM dhcp_pools
                                WHERE dhcp_failover_group_id IN (SELECT id
                                                                FROM dhcp_failover_groups
                                                                WHERE primary_server_id = '. $onadb->qstr($form['server_id']) .'
                                                                OR secondary_server_id = '. $onadb->qstr($form['server_id']) .'))';


        $and = " AND ";
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
            'ip_addr',
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
                'subnets',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;


    //
    // *** BUILD HTML LIST ***
    //
    $html .= <<<EOL
    <!-- Subnet Results -->
    <table id="{$form['form_id']}_dhcp_server_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Usage</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>
EOL;

    // Loop and display each record
    foreach($results as $record) {
        // Get additional info about eash subnet record //

        // Convert IP and Netmask to a presentable format
        $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
        $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
        $record['ip_mask_cidr'] = ip_mangle($record['ip_mask'], 'cidr');

        list($status, $rows, $type) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
        $record['type'] = $type['display_name'];

        // Calculate the percentage of the subnet that's used (total size - allocated hosts - dhcp pool size)
        $usage_html = get_subnet_usage_html($record['id']);

        // Escape data for display in html
        foreach(array_keys($record) as $key) {
            $record[$key] = htmlentities($record[$key], ENT_QUOTES);
        }

        $primary_object_js = "xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$record['id']}\', \'display\')');";
        $html .= <<<EOL
        <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

            <td class="list-row">
                <a title="View subnet. ID: {$record['id']}"
                   class="nav"
                   onClick="{$primary_object_js}"
                >{$record['name']}</a>
            </td>

            <td class="list-row" align="left">
                {$record['ip_addr']} <span title="{$record['ip_mask']}">/{$record['ip_mask_cidr']}</span>&nbsp;
            </td>

            <td class="list-row" align="center" style="vertical-align: middle;">
                {$usage_html}
            </td>

            <td class="list-row" align="right">
                {$record['type']}&nbsp;
            </td>

            <td class="list-row" align="right">
                <form id="{$form['form_id']}_list_dhcp_server_{$record['id']}"
                    ><input type="hidden" name="subnet_id" value="{$record['id']}"
                    ><input type="hidden" name="server_id" value="{$form['server_id']}"
                    ><input type="hidden" name="js" value="{$refresh}"
                ></form>

                <a title="Display subnet map"
                   class="act"
                   onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_block_map\', \'ip_block_start=>{$record['ip_addr']}\', \'display\');');"
                ><img src="{$images}/silk/shape_align_left.png" border="0"></a>&nbsp;
EOL;
        if (auth('subnet_modify',$debug_val)) {
            $html .= <<<EOL

                <a title="Edit subnet"
                   class="act"
                   onClick="xajax_window_submit('edit_subnet', xajax.getFormValues('{$form['form_id']}_list_dhcp_server_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }
        // check if the subnet listed is from a failover group or an actual dhcp server subnet assignment
        list($dhcpsubnetstatus, $dhcpsubnetrows, $dhcpserver) = ona_get_dhcp_server_subnet_record(array('subnet_id' => $record['id'],'host_id' => $form['server_id']));
        if (auth('subnet_del',$debug_val) && $dhcpsubnetrows == 1) {
            $html .= <<<EOL

                <a title="Remove subnet association with server."
                   class="act"
                   onClick="var doit=confirm('Are you sure you want to remove this subnet from this DHCP server?');
                        if (doit == true)
                            xajax_window_submit('edit_dhcp_server', xajax.getFormValues('{$form['form_id']}_list_dhcp_server_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/page_delete.png" border="0"></a>&nbsp;
EOL;
        }
        else {
            $html .= <<<EOL
                <span title="You must change the failover group assignment on the pool to remove this entry."><img src="{$images}/silk/comment.png" border="0"></span>&nbsp;
EOL;
        }
        $html .= <<<EOL

            </td>

        </tr>
EOL;
    }

    $html .= <<<EOL
    </table>
EOL;

    // Build page links if there are any
    $html .= get_page_links($page, $conf['search_results_per_page'], $count, $window_name, $form['form_id']);

    // If there was only 1 result, and we're about to display results in the "Search Results" window, display it.
    if ($count == 1 and $form['content_id'] == 'search_results_list' and $form['filter'] == '')
        $js .= $primary_object_js;

    // Insert the new html into the content div specified
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $response->addAssign("{$form['form_id']}_{$tab}_count",  "innerHTML", "({$count})");
    $response->addAssign($form['content_id'], "innerHTML", $html);
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}









?>
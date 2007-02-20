<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of subnets based on search criteria.
//   Input:  An array from xajaxGetFormValues() from a quick filter form.
//////////////////////////////////////////////////////////////////////////////
function ws_display_list($window_name, $form='') {
    global $conf, $self, $onadb;
    global $images, $color, $style;
    $html = '';
    $js = '';
    $debug_val = 3;  // used in the auth() calls to supress logging

    // If the user supplied an array in a string, build the array and store it in $form
    //$form = parse_options_string($form);

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


     //   $js .= "alert('Where: " . str_replace("'", '"', $form['subnet_id']) . "');";


    //
    // *** ADVANCED SUBNET SEARCH ***
    //       FIND RESULT SET
    //

    // Start building the "where" clause for the sql query to find the subnets to display
    $where = "";
    $and = "";

    // SUBNET ID
    if ($form['subnet_id']) {
        $where .= $and . "id = " . $form['subnet_id'];
        $and = " AND ";
    //$js .= "alert('Where: " . str_replace("'", '"', $where) . "');";
    }

    // VLAN ID
    if ($form['vlan_id']) {
        $where .= $and . "vlan_id = " . $onadb->qstr($form['vlan_id']);
        $and = " AND ";
    }

    // SUBNET TYPE
    if ($form['nettype']) {
        $where .= $and . "subnet_type_id = " . $onadb->qstr($form['nettype']);
        $and = " AND ";
    }

    // SUBNET TYPE
    if ($form['server_id']) {
        $where .= $and . "id IN (SELECT SUBNET_ID FROM DHCP_SERVER_SUBNETS_B WHERE SERVER_ID = " . $onadb->qstr($form['server_id']) . ')';
        $and = " AND ";
    }

    // SUBNET NAME
    if ($form['subnetname']) {
        // This field is always upper case
        $form['subnetname'] = strtoupper($form['subnetname']);
        $where .= $and . "name LIKE " . $form['subnetname'];
//        $where .= $and . "name LIKE " . $onadb->qstr('%'.$form['subnetname'].'%');
        $and = " AND ";
        //$js .= "alert('Where: " . str_replace("'", '"', $where) . "');";

    }

    // IP ADDRESS
    if ($form['ip_subnet']) {
        // Build $ip and $ip_end from $form['ip_subnet'] and $form['ip_subnet_thru']
        $ip = ip_complete($form['ip_subnet'], '0');
        if ($form['ip_subnet_thru']) {
            $ip = ip_complete($form['ip_subnet'], '0');
            $ip_end = ip_complete($form['ip_subnet_thru'], '255');

            // Find out if $ip and $ip_end are valid
            $ip = ip_mangle($ip, 'numeric');
            $ip_end = ip_mangle($ip_end, 'numeric');
            if ($ip != -1 and $ip_end != -1) {
                // Find subnets between the specified ranges
                $where .= $and . " ip_addr >= " . $ip . " AND ip_addr <= " . $ip_end;
                $and = " AND ";
            }
        }
        else {
            list($status, $rows, $record) = ona_find_subnet($ip);
            if($rows) {
                $where .= $and . " id = " . $record['id'];
                $and = " AND ";
            }
       }
    }

//     // UNIT NUMBER
//     if ($form['unit']) {
//         // Look up the Unit ID from the unit number
//         list($status, $rows, $unit) = ona_get_unit_record(array('UNIT_NUMBER' => $form['unit']));
//         if ($rows) {
//             $where .= $and . "UNIT_ID = " . $onadb->qstr($unit['UNIT_ID']);
//             $and = " AND ";
//         }
//     }


    // Wild card .. if $while is still empty, add a 'ID > 0' to it so you see everything.
    if ($where == '')
        $where = 'id > 0';



    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {

        // Subnet namess are always upper case
        $form['filter'] = strtoupper($form['filter']);
        $filter = ' AND name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }

    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'subnets',
            $where . $filter,
            "ip_addr",
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

 //   $js .= "alert('Where: " . str_replace("'", '"', $where) . "');";
 //   $js .= "alert('Where: " . $form['vlan'] . "');";






    //
    // *** BUILD HTML LIST ***
    //
    $html .= <<<EOL
    <!-- Subnet Results -->
    <table id="{$form['form_id']}_subnet_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

        <!-- Table Header -->
        <tr>
            <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Usage</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Type</td>
            <td class="list-header" align="center" style="{$style['borderR']};">Lvl</td>
            <td class="list-header" align="center">&nbsp;</td>
        </tr>
EOL;

    // Loop and display each record
    foreach($results as $record) {
        // Get additional info about eash subnet record //

        // Convert IP and Netmask to a presentable format
        $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
        $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
        $record['IP_SUBNET_MASK_CIDR'] = ip_mangle($record['ip_mask'], 'cidr');

        list($status, $rows, $type) = ona_get_subnet_type_record(array('id' => $record['subnet_type_id']));
        $record['TYPE'] = $type['name'];

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
                {$record['ip_addr']} <span title="{$record['ip_mask']}">/{$record['IP_SUBNET_MASK_CIDR']}</span>&nbsp;
            </td>

            <td class="list-row" align="center" style="vertical-align: middle;">
                {$usage_html}
            </td>

            <td class="list-row" align="right">
                {$record['TYPE']}&nbsp;
            </td>

            <td class="list-row" align="right">
                {$record['LVL']}&nbsp;
            </td>

            <td class="list-row" align="right">
                <form id="{$form['form_id']}_list_subnet_{$record['id']}"
                    ><input type="hidden" name="subnet_id" value="{$record['id']}"
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
                   onClick="xajax_window_submit('edit_subnet', xajax.getFormValues('{$form['form_id']}_list_subnet_{$record['id']}'), 'editor');"
                ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
    }

    if (auth('subnet_del',$debug_val)) {
        $html .= <<<EOL

                <a title="Delete subnet"
                   class="act"
                   onClick="var doit=confirm('Are you sure you want to delete this subnet?');
                            if (doit == true)
                                xajax_window_submit('edit_subnet', xajax.getFormValues('{$form['form_id']}_list_subnet_{$record['id']}'), 'delete');"
                ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
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

    $js .= <<<EOL
        /* Make sure this table is 100% wide */
        el('{$form['form_id']}_subnet_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;

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
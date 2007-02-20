<?



//////////////////////////////////////////////////////////////////////////////
// Function: ws_display_list()
//
// Description:
//   Displays A list of interfaces based on search criteria.
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
       // $form = array_merge($form, $_SESSION['ona'][$form['form_id']][$tab]['q']);
        $_SESSION['ona'][$form['form_id']][$tab]['page'] = $page = $form['page'];
        $_SESSION['ona'][$form['form_id']][$tab]['filter'] = $form['filter'];
    }

    // Calculate the SQL query offset (based on the page being displayed)
    $offset = ($conf['search_results_per_page'] * ($page - 1));
    if ($offset == 0) { $offset = -1; }

    // Search results go in here
    $results = array();
    $count = 0;

    // Start building the "where" clause for the sql query to find the interfaces to display
    $where = "";
    $and = "";

    // HOST ID
    if ($form['host_id']) {
        $where .= $and . "host_id = " . $onadb->qstr($form['host_id']);
        $and = " AND ";
    }

    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        $filter = $and . ' interface_name LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
    }
    list ($status, $rows, $results) =
        db_get_records(
            $onadb,
            'interfaces',
            $where . $filter,
            "ip_addr ASC",
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
                'interfaces',
                $where . $filter,
                "",
                0
            );
    }
    $count = $rows;

    //$js .= "alert('Where: " . str_replace("'", '"', $where) . "');";

    $html .= <<<EOL
        <!-- Interface List -->
        <table id="{$form['form_id']}_interface_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>
                <td class="list-header" align="center" style="{$style['borderR']};">Interface</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Network</td>
                <td class="list-header" align="center" style="{$style['borderR']};">MAC</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">DNS</td>
                <td class="list-header" align="center" style="{$style['borderR']};">PTR</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Last Ping</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;

        // Loop and display each record
        foreach($results as $record) {
            // Get additional info about eash host record //

            // Grab some info from the associated subnet record
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $record['subnet_id']));
            $record['ip_mask'] = $subnet['ip_mask'];
            $record['SUBNET_ID'] = $subnet['id'];
            $record['SUBNET_DESCRIPTION'] = $subnet['name'];

            // Convert IP and Netmask to a presentable format
            $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
            $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
            $record['ip_mask_cidr'] = ip_mangle($record['ip_mask'], 'cidr');
            if ($record['mac_addr']) { $record['mac_addr'] = mac_mangle($record['mac_addr']); }

            // Escape data for display in html
            foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }

            // Format the last ping response (colored red if more than 90 days ago)
            if ((time() - date_mangle($record['LAST_PING_RESPONSE'], 'ts')) > (60 * 60 * 24 * 90) )
                $record['LAST_PING_RESPONSE'] = "<font color=\"red\">{$record['LAST_PING_RESPONSE']}</font>";

            $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

                <td class="list-row">
EOL;

            if (auth('interface_del',$debug_val)) {
                $html .= <<<EOL
                    <a title="Edit interface. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_interface', xajax.getFormValues('{$form['form_id']}_list_interface_{$record['id']}'), 'editor');">
                        {$record['ip_addr']}
                        </a>
EOL;
            }
            else {

                $html .= "                        {$record['ip_addr']}";
            }
            $html .= <<<EOL
                    <span title="{$record['ip_mask']}">/{$record['ip_mask_cidr']}</span>
                </td>

                <td class="list-row" align="left">
                    <a title="View subnet. ID: {$record['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['SUBNET_DESCRIPTION']}</a>
                </td>

                <td class="list-row" align="right">
                    {$record['mac_addr']}&nbsp;
                </td>

                <td class="list-row" align="left">
                    {$record['name']}&nbsp;
                </td>

                <td class="list-row" align="center">
                    {$record['CREATE_DNS_ENTRY']}&nbsp;
                </td>

                <td class="list-row" align="center">
                    {$record['CREATE_REVERSE_DNS_ENTRY']}&nbsp;
                </td>

                <td class="list-row" align="left">
                    {$record['LAST_PING_RESPONSE']}&nbsp;
                </td>

                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_interface_{$record['id']}"
                        ><input type="hidden" name="interface_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>&nbsp;
EOL;

        if (auth('interface_modify',$debug_val)) {
            $html .= <<<EOL

                    <a title="Edit interface. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_interface', xajax.getFormValues('{$form['form_id']}_list_interface_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('interface_del',$debug_val)) {
            $html .= <<<EOL

                    <a title="Delete interface"
                       class="act"
                       onClick="var doit=confirm('Are you sure you want to delete this interface?');
                                if (doit == true)
                                    xajax_window_submit('edit_interface', xajax.getFormValues('{$form['form_id']}_list_interface_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
        }
        $html .= <<<EOL
                </td>

            </tr>
EOL;
        }

        $js .= <<<EOL
            /* Make sure this table is 100% wide */
            el('{$form['form_id']}_interface_list').style.width = el('{$form['form_id']}_table').offsetWidth + 'px';
EOL;

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
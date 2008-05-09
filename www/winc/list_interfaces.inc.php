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

    // Start building the "where" clause for the sql query to find the interfaces to display
    $where = "";
    $and = "";

    // HOST ID
    if ($form['host_id']) {
        $where .= $and . "host_id = " . $onadb->qstr($form['host_id']). "OR id in (select interface_id from interface_clusters where host_id = ".$onadb->qstr($form['host_id']).")";
        $and = " AND ";
    }

    // Do the SQL Query
    $filter = '';
    if ($form['filter']) {
        $form['filter'] = ip_mangle($form['filter']);
        $filter = $and . ' ip_addr LIKE ' . $onadb->qstr('%'.$form['filter'].'%');
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


    $html .= <<<EOL
        <!-- Interface List -->
        <table id="{$form['form_id']}_interface_list" class="list-box" cellspacing="0" border="0" cellpadding="0">

            <!-- Table Header -->
            <tr>

                <td colspan="2" class="list-header" align="center" style="{$style['borderR']};">Interface</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Subnet</td>
                <td class="list-header" align="center" style="{$style['borderR']};">MAC</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Name</td>
                <td class="list-header" align="center" style="{$style['borderR']};">Description</td>
                <td class="list-header" align="center">&nbsp;</td>
            </tr>
EOL;

        // Loop and display each record
        foreach($results as $record) {
            // Get additional info about each host record //

            // Check if this interface has an external NAT
            unset($extnatint, $extnatdisplay);
            if ($record['nat_interface_id'] > 0) {
                list($status, $rows, $extnatint) = ona_get_interface_record(array('id' => $record['nat_interface_id']));
                $extnatint['ip_addr'] = ip_mangle($extnatint['ip_addr'], 'dotted');
                $extnatdisplay = "<span title='Interface is NATed to {$extnatint['ip_addr']}'> &nbsp;&nbsp;=> &nbsp;{$extnatint['ip_addr']}</span>";
            }

            // Check if this interface is an external NAT for another interface
            list ($isnatstatus, $isnatrows, $isnat) = db_get_records($onadb, 'interfaces', "nat_interface_id = {$record['id']}", '', 0 );
            // If the current interface is external NAT for another, dont display it in the list.
            if ($isnatrows > 0) {
                $count = $count - 1; // MP: not sure if this will always total correctly
                continue;
            }

            list ($status, $intclusterrows, $intcluster) = db_get_records($onadb, 'interface_clusters', "interface_id = {$record['id']}");

            // Grab some info from the associated subnet record
            list($status, $rows, $subnet) = ona_get_subnet_record(array('id' => $record['subnet_id']));
            $record['ip_mask'] = $subnet['ip_mask'];
            $record['subnet_id'] = $subnet['id'];
            $record['subnet_description'] = $subnet['name'];

            // Convert IP and Netmask to a presentable format
            $record['ip_addr'] = ip_mangle($record['ip_addr'], 'dotted');
            $record['ip_mask'] = ip_mangle($record['ip_mask'], 'dotted');
            $record['ip_mask_cidr'] = ip_mangle($record['ip_mask'], 'cidr');
            if ($record['mac_addr']) { $record['mac_addr'] = mac_mangle($record['mac_addr']); }

            $record['description_short'] = truncate($record['description'], 40);

            // Escape data for display in html
            foreach(array_keys($record) as $key) { $record[$key] = htmlentities($record[$key], ENT_QUOTES); }


            $html .= <<<EOL
            <tr onMouseOver="this.className='row-highlight'" onMouseOut="this.className='row-normal'">

                <td nowrap="true" class="list-row" style="padding: 0px;" width="16px">
EOL;

            // Display cluster related information
            $clusterhtml='&nbsp;';
            $clusterstyle='';

            if ($intclusterrows>0) {
                $clusterstyle='font-weight: bold';
                $clusterscript= "onMouseOver=\"wwTT(this, event,
                        'id', 'tt_interface_cluster_list_{$record['id']}',
                        'type', 'velcro',
                        'styleClass', 'wwTT_niceTitle',
                        'direction', 'south',
                        'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>interface_cluster_list,id=>tt_interface_cluster_list_{$record['id']},interface_id=>{$record['id']}\');'
                        );\"";
                $clusterhtml .= <<<EOL
                    <img src="{$images}/silk/sitemap.png" border="0" {$clusterscript} />
EOL;
            }
            $html .= $clusterhtml;



            $html .= <<<EOL
                </td>

                <td class="list-row">
EOL;

            // MP: Disabling the display_interface link. I dont think this will be needed
            if (1<0) {
                $html .= <<<EOL
                    <a title="View interface. ID: {$record['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_interface\',\'interface_id=>{$record['id']}\', \'display\')');">
                        {$record['ip_addr']}
                        </a>
EOL;
            }
            else {

                $html .= "<span style='{$clusterstyle}' {$clusterscript}>{$record['ip_addr']}</span>";
            }
            $html .= <<<EOL
                    <span style="{$clusterstyle}" title="{$record['ip_mask']}">/{$record['ip_mask_cidr']}</span> {$extnatdisplay}
                </td>

                <td class="list-row" align="left">
                    <a title="View subnet. ID: {$subnet['id']}"
                       class="nav"
                       onClick="xajax_window_submit('work_space', 'xajax_window_submit(\'display_subnet\', \'subnet_id=>{$subnet['id']}\', \'display\')');"
                    >{$record['subnet_description']}</a>
                </td>

                <td class="list-row" align="right">
                    {$record['mac_addr']}&nbsp;
                </td>

                <td class="list-row" align="left">
                    {$record['name']}&nbsp;
                </td>

                <td class="list-row" align="left" title="{$record['description']}">
                    {$record['description_short']}&nbsp;
                </td>

                <td class="list-row" align="right">
                    <form id="{$form['form_id']}_list_interface_{$record['id']}"
                        ><input type="hidden" name="interface_id" value="{$record['id']}"
                        ><input type="hidden" name="js" value="{$refresh}"
                    ></form>&nbsp;
EOL;

        if (auth('interface_modify')) {
            $html .= <<<EOL

                    <a title="Interface Menu"
                       id="int_menu_button_{$record['id']}"
                       class="act"
                       onmouseover="wwTT(this, event,
                                            'id', 'tt_quick_interface_menu_{$record['id']}',
                                            'type', 'velcro',
                                            'delay', 0,
                                            'styleClass', 'wwTT_int_menu',
                                            'lifetime', 1000,
                                            'direction', 'west',
                                            'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>quick_interface_menu,id=>tt_quick_interface_menu_{$record['id']},interface_id=>{$record['id']},ip_addr=>{$record['ip_addr']},orig_host=>{$record['host_id']},form_id=>{$form['form_id']}_list_interface_{$record['id']},natip=>{$record['nat_interface_id']}\');'
                                           );"
                    ><img src="{$images}/silk/add.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('interface_modify')) {
            $html .= <<<EOL

                    <a title="Edit interface. ID: {$record['id']}"
                       class="act"
                       onClick="xajax_window_submit('edit_interface', xajax.getFormValues('{$form['form_id']}_list_interface_{$record['id']}'), 'editor');"
                    ><img src="{$images}/silk/page_edit.png" border="0"></a>&nbsp;
EOL;
        }

        if (auth('interface_del')) {
            $html .= <<<EOL

                    <a title="Delete interface"
                       class="act"
                       onClick="xajax_window_submit('edit_interface', xajax.getFormValues('{$form['form_id']}_list_interface_{$record['id']}'), 'delete');"
                    ><img src="{$images}/silk/delete.png" border="0"></a>&nbsp;
EOL;
        }
        $html .= <<<EOL
                </td>

            </tr>
EOL;
        }




    if ($count == 0 and $form['host_id'] and !$form['filter']) {
        $html .= <<<EOL
     <tr><td colspan="99" align="center" style="color: red;">Please add an interface to this host, or delete the host</td></tr>
EOL;
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